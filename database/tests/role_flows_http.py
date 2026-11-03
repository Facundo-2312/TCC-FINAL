"""HTTP integration checks against an isolated PHP server and synthetic MariaDB only."""
import html, http.cookiejar, os, re, urllib.request, urllib.parse

if os.environ.get('APP_DB_NAME') != 'ci_test':
    raise RuntimeError('HTTP integration checks require the isolated ci_test database')

BASE = 'http://127.0.0.1:8080/'
PASSWORD = os.environ['FLOW_TEST_PASSWORD']

def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def request(c, path, data=None):
    payload = None if data is None else urllib.parse.urlencode(data).encode()
    with c.open(BASE + path, payload, timeout=20) as r:
        body = r.read().decode()
        assert 'Ocurrio un error inesperado' not in body, path
        return r.url, body

def fields(body):
    return {name: html.unescape(value) for name, value in re.findall(r'name="(_csrf|ctx)" value="([^"]*)"', body)}

def input_value(body, name):
    match = re.search(r'<input\b[^>]*\bname="' + re.escape(name) + r'"[^>]*\bvalue="([^"]*)"', body)
    assert match is not None, name
    return html.unescape(match.group(1))

def product_form(body, name):
    row = re.search(r'<tr>\s*<td>\s*(\d+)\s*</td>\s*<td>' + re.escape(name) + r'</td>.*?</tr>', body, re.S)
    assert row is not None, 'Synthetic product not found: ' + name
    return row.group(1), fields(row.group(0))

def login(role):
    c = client()
    _, body = request(c, 'Login.php')
    data = fields(body)
    data.update(Usuario='flow_role_' + str(role), Pass=PASSWORD)
    url, body = request(c, 'interfazlog.php', data)
    expected = {1:'Principal.php',2:'CAJA.php',3:'inicio_rol.php',4:'inicio_rol.php',5:'cliente.php'}[role]
    assert urllib.parse.urlparse(url).path == '/' + expected, (role, url)
    return c, urllib.parse.parse_qs(urllib.parse.urlparse(url).query)['ctx'][0]

allowed = {
    1: ['Principal.php','EmpleadoI.php','crear.php','productos.php','informes.php','CAJA.php','mesas.php','centro_reservas.php','Cocina2.php','cliente.php'],
    2: ['CAJA.php','inicio_rol.php','mesas.php','centro_reservas.php'],
    3: ['inicio_rol.php','pedidos.php','Cocina2.php','productos_consulta.php','mesas.php'],
    4: ['inicio_rol.php','Cocina2.php','productos_consulta.php'],
    5: ['cliente.php'],
}
for role in range(1,6):
    c, ctx = login(role)
    for path in allowed[role]:
        url, body = request(c, path + '?ctx=' + ctx)
        assert '/Login.php' not in url, (role,path)
        # Every emitted app_url PHP link must preserve context before a fragment.
        for href in re.findall(r'href="([^"]+)"', body):
            href = html.unescape(href)
            if '.php' in href and '#' in href:
                assert 'ctx=' not in urllib.parse.urlparse(href).fragment, href
    if role != 1:
        url, _ = request(c, 'EmpleadoI.php?ctx=' + ctx)
        assert '/Login.php' in url, role
    c, ctx = login(role)
    if role not in (1,2):
        url, _ = request(c, 'CAJA.php?ctx=' + ctx)
        assert '/Login.php' in url, role
    c, ctx = login(role)
    url, _ = request(c, 'Salir.php?ctx=' + ctx)
    assert '/Login.php' in url
    url, _ = request(c, 'Principal.php')
    assert '/Login.php' in url
    print('PASS role', role, 'login, allowed pages, denied pages, logout')

c=client()
_, body=request(c,'Login.php')
data=fields(body);data.update(Usuario='nonexistent_synthetic_account',Pass=PASSWORD)
url,body=request(c,'interfazlog.php',data)
assert '/Login.php' in url and 'incorrectos' in body
_,body=request(c,'Login.php')
data=fields(body);data.update(cliente_login='1',nombre_cliente='Cliente de prueba')
url,body=request(c,'interfazlog.php',data)
assert '/cliente.php' in url
print('PASS unknown account and guest client')

c, ctx = login(1)
ci = '87654321'
created_user = 'qa_funcionario_creado'
updated_user = 'qa_funcionario_editado'
_, body = request(c, 'EmpleadoI.php?ctx=' + ctx)
assert created_user not in body and updated_user not in body

_, body = request(c, 'crear.php?ctx=' + ctx)
data = fields(body)
data.update(
    crud='1', CI=ci, Nombre='Prueba', Apellido='Inicial',
    Direccion='Calle Prueba 123', Rol='Mozo', Usuario=created_user,
    Pass=PASSWORD, direccion_latitud='-34.9011', direccion_longitud='-56.1645'
)
url, body = request(c, 'InterfazE.php', data)
assert urllib.parse.urlparse(url).path == '/EmpleadoI.php' and created_user in body

url, body = request(c, 'Actualizar.php?ID=' + ci + '&ctx=' + ctx)
assert urllib.parse.urlparse(url).path == '/Actualizar.php'
assert input_value(body, 'Usuario') == created_user
assert float(input_value(body, 'direccion_latitud')) == -34.9011
assert float(input_value(body, 'direccion_longitud')) == -56.1645
data = fields(body)
data.update(
    crud='2', CI=ci, Nombre='Prueba', Apellido='Editado',
    Direccion='Calle Prueba 456', Rol='Caja', Usuario=updated_user, Pass='',
    direccion_latitud='-34.902', direccion_longitud='-56.165'
)
url, body = request(c, 'InterfazE.php', data)
assert urllib.parse.urlparse(url).path == '/EmpleadoI.php'
assert updated_user in body and created_user not in body

_, body = request(c, 'Actualizar.php?ID=' + ci + '&ctx=' + ctx)
assert input_value(body, 'Usuario') == updated_user
assert float(input_value(body, 'direccion_latitud')) == -34.902
assert float(input_value(body, 'direccion_longitud')) == -56.165

employee = client()
_, body = request(employee, 'Login.php')
data = fields(body)
data.update(Usuario=updated_user, Pass=PASSWORD)
url, _ = request(employee, 'interfazlog.php', data)
assert urllib.parse.urlparse(url).path == '/CAJA.php'

url, body = request(c, 'Eliminar.php', {'ID': ci, 'ctx': ctx, '_csrf': 'incorrecto'})
assert urllib.parse.urlparse(url).path == '/EmpleadoI.php' and updated_user in body
data = fields(body)
data.update(ID='0')
url, body = request(c, 'Eliminar.php', data)
assert urllib.parse.urlparse(url).path == '/EmpleadoI.php'
assert updated_user in body and 'Selecciona un funcionario valido' in body
data = fields(body)
data.update(ID=ci)
url, body = request(c, 'Eliminar.php', data)
assert urllib.parse.urlparse(url).path == '/EmpleadoI.php' and updated_user not in body
assert 'Funcionario eliminado correctamente' in body

employee = client()
_, body = request(employee, 'Login.php')
data = fields(body)
data.update(Usuario=updated_user, Pass=PASSWORD)
url, body = request(employee, 'interfazlog.php', data)
assert urllib.parse.urlparse(url).path == '/Login.php' and 'incorrectos' in body
print('PASS employee create, edit, location, login, CSRF and delete')

product_name = 'Producto QA Eliminacion'
_, body = request(c, 'MVCsix1.0/index.php?ctx=' + ctx)
product_id, data = product_form(body, product_name)
query = urllib.parse.urlencode({'ID': product_id, '_csrf': data['_csrf'], 'ctx': ctx})
url, body = request(c, 'MVCsix1.0/delete.php?' + query)
assert urllib.parse.urlparse(url).path == '/MVCsix1.0/index.php' and product_name in body
url, body = request(c, 'MVCsix1.0/delete.php', {'ID': product_id, 'ctx': ctx, '_csrf': 'incorrecto'})
assert urllib.parse.urlparse(url).path == '/MVCsix1.0/index.php' and product_name in body
data = fields(body)
data.update(ID=product_id)
url, body = request(c, 'MVCsix1.0/delete.php', data)
assert urllib.parse.urlparse(url).path == '/MVCsix1.0/index.php'
assert product_name not in body and 'Producto eliminado correctamente' in body
print('PASS product deletion requires POST and CSRF')

historical_name = 'Producto QA Historial'
historical_id, data = product_form(body, historical_name)
data.update(ID=historical_id)
url, body = request(c, 'MVCsix1.0/delete.php', data)
assert urllib.parse.urlparse(url).path == '/MVCsix1.0/index.php'
assert historical_name in body and 'se desactivo y ya no aparece en el menu' in body
customer, customer_ctx = login(5)
_, body = request(customer, 'cliente.php?ctx=' + customer_ctx)
assert historical_name not in body
print('PASS product with order history is deactivated and removed from the menu')
