const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.resolve(__dirname, '..', '..');
const cliente = fs.readFileSync(path.join(root, 'cliente.php'), 'utf8');
const caja = fs.readFileSync(path.join(root, 'CAJA.php'), 'utf8');
function scriptDePagina(source) {
    const match = source.match(/<script>\s*([\s\S]*?)<\/script>/);
    assert.ok(match, 'La pagina debe incluir su script');
    return match[1].replace(/<\?php[\s\S]*?\?>/g, 'null');
}
new vm.Script(scriptDePagina(cliente));
new vm.Script(scriptDePagina(caja));
assert.match(cliente, /setInterval\(loadCustomerOrders, 5000\)/);
assert.match(caja, /setInterval\(actualizarSolicitudesCobro, 5000\)/);

async function main() {
    const tracking = {innerHTML: ''};
    const account = {innerHTML: '', contains: () => false};
    const status = {textContent: ''};
    let data = {
        ok: true,
        pedidos: [{id_pedido: 1, estado: 'Entregado', fecha: '2026-10-21', total: '270.00', productos: [{}]}],
        cuenta_html: '<p>Pago solicitado</p>'
    };
    let requests = 0;
    let fail = false;
    const context = vm.createContext({
        document: {
            visibilityState: 'visible',
            getElementById: id => ({
                'order-tracking': tracking,
                'customer-account': account,
                'tracking-refresh-status': status
            })[id]
        },
        fetch: async () => {
            requests++;
            if (fail) throw new Error('Conexion interrumpida');
            return {ok: true, json: async () => data};
        },
        escapeHtml: value => String(value).replace(/</g, '&lt;')
    });
    const script = scriptDePagina(cliente);
    vm.runInContext(script.slice(script.indexOf('const orderTracking ='), script.indexOf('function renderTableLive(')), context);
    await vm.runInContext('loadCustomerOrders()', context);
    assert.match(tracking.innerHTML, /Entregado/);
    assert.equal(account.innerHTML, '<p>Pago solicitado</p>');
    data = {...data, cuenta_html: '<p>Pagado - Transferencia</p>'};
    await vm.runInContext('loadCustomerOrders()', context);
    assert.equal(account.innerHTML, data.cuenta_html, 'La confirmacion de Caja actualiza la cuenta');
    account.contains = () => true;
    data = {...data, cuenta_html: '<p>Otro pedido</p>'};
    await vm.runInContext('loadCustomerOrders()', context);
    assert.match(account.innerHTML, /Pagado/, 'No reemplaza un formulario que el cliente esta usando');
    account.contains = () => false;
    fail = true;
    await vm.runInContext('loadCustomerOrders()', context);
    assert.match(status.textContent, /No se pudo actualizar/);
    assert.match(account.innerHTML, /Pagado/, 'Una falla no borra el ultimo estado confirmado');
    fail = false;
    await vm.runInContext('loadCustomerOrders()', context);
    assert.equal(status.textContent, '', 'Se recupera despues de una falla');
    context.document.visibilityState = 'hidden';
    const before = requests;
    await vm.runInContext('loadCustomerOrders()', context);
    assert.equal(requests, before, 'No consulta una pestana oculta');

    const cards = new Map();
    function makeCard(html) {
        const id = html.match(/data-pedido="(\d+)"/)[1];
        const select = {dataset: {}, value: html.includes('Transferencia') ? 'Transferencia' : ''};
        return {
            dataset: {pedido: id}, html,
            contains: element => element === select,
            querySelector: () => select,
            remove: () => cards.delete(id),
            replaceWith: nueva => cards.set(id, nueva)
        };
    }
    const empty = {hidden: false};
    const history = {innerHTML: ''};
    const pending = {
        addEventListener() {},
        contains: element => [...cards.values()].some(card => card.contains(element)),
        querySelectorAll: () => [...cards.values()],
        querySelector: selector => cards.get(selector.match(/"(\d+)"/)[1]),
        insertBefore: card => cards.set(card.dataset.pedido, card)
    };
    const cajaStatus = {textContent: ''};
    const elements = {
        'cobros-status': cajaStatus, 'caja-pendientes': pending,
        'caja-sin-pendientes': empty, 'caja-historial': history
    };
    for (const id of ['recaudado', 'pendiente', 'pedidos', 'solicitudes', 'pagos']) {
        elements['caja-' + id] = {textContent: ''};
    }
    let snapshot = {
        tarjetas: [{id_pedido: 1, html: '<article data-pedido="1">Transferencia</article>'}],
        resumen: {recaudadoHoy: '620.00', pendienteCobro: 270, pedidosPendientes: 1, solicitudes: 1, pagosHoy: 2},
        pagos_html: '<table><tr><td>Pago confirmado</td></tr></table>'
    };
    let cajaFail = false;
    const cajaContext = vm.createContext({
        document: {
            hidden: false, activeElement: null,
            getElementById: id => elements[id],
            addEventListener() {},
            createElement: () => ({
                content: {},
                set innerHTML(html) { this.content.firstElementChild = makeCard(html); }
            })
        },
        fetch: async () => {
            if (cajaFail) throw new Error('Conexion interrumpida');
            return {ok: true, json: async () => snapshot};
        },
        setInterval() {}
    });
    vm.runInContext(scriptDePagina(caja), cajaContext);
    await vm.runInContext('actualizarSolicitudesCobro()', cajaContext);
    assert.equal(cards.size, 1, 'Inserta pedidos nuevos sin recargar');
    assert.equal(empty.hidden, true);
    assert.equal(elements['caja-recaudado'].textContent, '$620,00');
    assert.equal(elements['caja-pendiente'].textContent, '$270,00');
    assert.equal(elements['caja-solicitudes'].textContent, 1);
    assert.equal(history.innerHTML, snapshot.pagos_html);
    const select = cards.get('1').querySelector();
    select.dataset.editado = '1';
    select.value = 'Efectivo';
    snapshot.tarjetas[0].html = '<article data-pedido="1">Transferencia actualizada</article>';
    await vm.runInContext('actualizarSolicitudesCobro()', cajaContext);
    assert.equal(cards.get('1').querySelector().value, 'Efectivo', 'No pisa la eleccion manual del cajero');
    const focused = cards.get('1');
    cajaContext.document.activeElement = focused.querySelector();
    snapshot.tarjetas[0].html = '<article data-pedido="1">Detalle actualizado</article>';
    await vm.runInContext('actualizarSolicitudesCobro()', cajaContext);
    assert.equal(cards.get('1'), focused, 'No interrumpe el formulario activo');
    cajaContext.document.activeElement = null;
    cajaFail = true;
    await vm.runInContext('actualizarSolicitudesCobro()', cajaContext);
    assert.match(cajaStatus.textContent, /No se pudieron actualizar/);
    assert.equal(history.innerHTML, snapshot.pagos_html, 'Una falla no borra el historial');
    cajaFail = false;
    snapshot = {
        tarjetas: [],
        resumen: {recaudadoHoy: '890.00', pendienteCobro: 0, pedidosPendientes: 0, solicitudes: 0, pagosHoy: 3},
        pagos_html: '<table><tr><td>Nuevo pago confirmado</td></tr></table>'
    };
    await vm.runInContext('actualizarSolicitudesCobro()', cajaContext);
    assert.equal(cards.size, 0, 'Retira pedidos ya pagados');
    assert.equal(empty.hidden, false);
    assert.equal(elements['caja-recaudado'].textContent, '$890,00');
    assert.equal(elements['caja-pagos'].textContent, 3);
    assert.equal(history.innerHTML, snapshot.pagos_html);
    console.log('OK: scripts validos, actualizacion automatica, formularios preservados, errores y recuperacion.');
}
main().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
