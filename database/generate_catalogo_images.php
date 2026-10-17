<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este comando solo puede ejecutarse desde la linea de comandos.' . PHP_EOL);
}

$catalogo = array(
    array('pizza-muzzarella', 'Pizza Muzzarella', 'Comida', '#e95d38', 'pizza'),
    array('pasta-pesto', 'Pasta al Pesto', 'Comida', '#58a85c', 'pasta_pesto'),
    array('pasta-bolognesa', 'Pasta Bolognesa', 'Comida', '#c84d3c', 'pasta_roja'),
    array('lasagna-carne', 'Lasagna de Carne', 'Comida', '#d99143', 'lasagna'),
    array('milanesa-completa', 'Milanesa Completa', 'Comida', '#d6a04b', 'milanesa'),
    array('chivito-plato', 'Chivito al Plato', 'Comida', '#9e6034', 'chivito'),
    array('bife-lomo', 'Bife de Lomo', 'Comida', '#963f32', 'bife'),
    array('pollo-parrilla', 'Pollo a la Parrilla', 'Comida', '#d59642', 'pollo'),
    array('pescado-dia', 'Pescado del Dia', 'Comida', '#67a6ae', 'pescado'),
    array('risotto-pollo', 'Risotto de Pollo', 'Comida', '#d9b56b', 'risotto'),
    array('coca-cola-600', 'Coca-Cola 600 ml', 'Bebida', '#d62e2e', 'cola'),
    array('coca-cola-zero-600', 'Coca-Cola Zero 600 ml', 'Bebida', '#40434a', 'cola_zero'),
    array('sprite-600', 'Sprite 600 ml', 'Bebida', '#7dad45', 'sprite'),
    array('fanta-naranja-600', 'Fanta Naranja 600 ml', 'Bebida', '#e8752a', 'fanta'),
    array('agua-mineral-500', 'Agua Mineral 500 ml', 'Bebida', '#66b6d1', 'agua'),
    array('agua-gas-500', 'Agua con Gas 500 ml', 'Bebida', '#5495bd', 'agua_gas'),
    array('limonada-natural', 'Limonada Natural', 'Bebida', '#bdd34a', 'limonada'),
    array('jugo-naranja', 'Jugo de Naranja', 'Bebida', '#ee8a2e', 'jugo'),
    array('cafe-espresso', 'Cafe Espresso', 'Bebida', '#805039', 'espresso'),
    array('cafe-leche', 'Cafe con Leche', 'Bebida', '#b58a61', 'cafe_leche'),
    array('papas-fritas', 'Papas Fritas', 'Acompanamiento', '#e9b64f', 'papas'),
    array('papas-rusticas', 'Papas Rusticas', 'Acompanamiento', '#c48a3d', 'rusticas'),
    array('pure-papas', 'Pure de Papas', 'Acompanamiento', '#e0c58c', 'pure'),
    array('pure-calabaza', 'Pure de Calabaza', 'Acompanamiento', '#e98b35', 'calabaza'),
    array('arroz-blanco', 'Arroz Blanco', 'Acompanamiento', '#e5dfc7', 'arroz'),
    array('verduras-salteadas', 'Verduras Salteadas', 'Acompanamiento', '#67a75b', 'verduras'),
    array('ensalada-verde', 'Ensalada Verde', 'Acompanamiento', '#74a852', 'ensalada_verde'),
    array('ensalada-mixta', 'Ensalada Mixta', 'Acompanamiento', '#91ac4f', 'ensalada_mixta'),
    array('empanadas', 'Empanadas', 'Acompanamiento', '#c8904d', 'empanadas'),
    array('bastones-mozzarella', 'Bastones de Mozzarella', 'Acompanamiento', '#e9c96b', 'bastones'),
    array('tiramisu', 'Tiramisu', 'Postre', '#8c573a', 'tiramisu'),
    array('torta-chocolate', 'Torta de Chocolate', 'Postre', '#6e392d', 'torta'),
    array('mousse-chocolate', 'Mousse de Chocolate', 'Postre', '#704137', 'mousse'),
    array('helado', 'Helado', 'Postre', '#edb3a1', 'helado'),
    array('flan-casero', 'Flan Casero', 'Postre', '#d99a38', 'flan'),
    array('copa-dulce-leche', 'Copa de Dulce de Leche', 'Postre', '#a86636', 'dulce_leche'),
    array('chocotorta', 'Chocotorta', 'Postre', '#68433a', 'chocotorta'),
    array('cheesecake', 'Cheesecake', 'Postre', '#d9c59a', 'cheesecake'),
    array('brownie-helado', 'Brownie con Helado', 'Postre', '#75402f', 'brownie'),
    array('ensalada-frutas', 'Ensalada de Frutas', 'Postre', '#89ae52', 'frutas'),
);

function catalogo_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function catalogo_artwork($tipo, $color)
{
    switch ($tipo) {
        case 'pizza':
            return '<circle cx="320" cy="215" r="122" fill="#d9a55f"/><circle cx="320" cy="215" r="108" fill="#f5d78b"/><circle cx="320" cy="215" r="94" fill="#d95c3f"/><path d="M320 121v188M226 215h188" stroke="#f3d985" stroke-width="8"/><g fill="#f6e6a9"><circle cx="275" cy="170" r="17"/><circle cx="362" cy="178" r="17"/><circle cx="276" cy="257" r="17"/><circle cx="362" cy="257" r="17"/></g><g fill="#8a382e"><circle cx="320" cy="168" r="9"/><circle cx="251" cy="213" r="9"/><circle cx="389" cy="215" r="9"/><circle cx="320" cy="263" r="9"/></g>';
        case 'pasta_pesto':
            return '<ellipse cx="320" cy="241" rx="150" ry="70" fill="#fff7e7"/><path d="M205 225c55-75 90 76 145-4s79 58 96-17M218 254c42-59 69 43 111 0s67 30 94-26M236 205c42-48 67 55 102 1s49 34 72-5" fill="none" stroke="#e4bd59" stroke-width="13" stroke-linecap="round"/><g fill="#4d9a4e"><ellipse cx="277" cy="202" rx="18" ry="8" transform="rotate(-28 277 202)"/><ellipse cx="360" cy="245" rx="18" ry="8" transform="rotate(25 360 245)"/><ellipse cx="316" cy="274" rx="17" ry="8" transform="rotate(-18 316 274)"/></g>';
        case 'pasta_roja':
            return '<ellipse cx="320" cy="245" rx="150" ry="70" fill="#fff7e7"/><path d="M205 224c55-75 90 76 145-4s79 58 96-17M218 254c42-59 69 43 111 0s67 30 94-26M236 205c42-48 67 55 102 1s49 34 72-5" fill="none" stroke="#e5bf68" stroke-width="13" stroke-linecap="round"/><g fill="#ad3c31"><circle cx="267" cy="207" r="13"/><circle cx="337" cy="192" r="12"/><circle cx="380" cy="230" r="14"/><circle cx="301" cy="263" r="12"/></g>';
        case 'lasagna':
            return '<path d="M205 166l108-46 135 48-3 111-114 43-128-45z" fill="#d99a4c" stroke="#9d5835" stroke-width="8"/><path d="M205 166l127 45 116-43M203 203l128 46 116-44M203 240l128 44 116-44" fill="none" stroke="#f4d58a" stroke-width="15"/><path d="M224 175l105 37 90-34M222 213l108 38 91-35" fill="none" stroke="#b94334" stroke-width="10"/><g fill="#f4e2a7"><circle cx="262" cy="182" r="7"/><circle cx="362" cy="197" r="7"/><circle cx="288" cy="225" r="7"/><circle cx="382" cy="240" r="7"/></g>';
        case 'milanesa':
            return '<path d="M210 224c12-57 74-88 132-65 50-35 108-1 95 48 21 48-37 91-85 68-56 28-128 9-142-51z" fill="#d99b4d" stroke="#a76633" stroke-width="8"/><g fill="#f3d58d"><circle cx="268" cy="185" r="6"/><circle cx="300" cy="221" r="7"/><circle cx="348" cy="181" r="6"/><circle cx="383" cy="221" r="7"/><circle cx="333" cy="260" r="6"/></g><path d="M409 275l41-28m-29 42l40-26m-25 40l38-26" stroke="#e6b94e" stroke-width="15" stroke-linecap="round"/>';
        case 'chivito':
            return '<path d="M218 201c23-65 159-83 205-8z" fill="#c88949" stroke="#8b552e" stroke-width="7"/><path d="M221 207h194l-15 22H234z" fill="#63a64f"/><path d="M230 226h176l-11 20H240z" fill="#f0d56f"/><path d="M240 245h160l-8 18H247z" fill="#a83e32"/><path d="M251 263h140l-28 41H273z" fill="#d49a51" stroke="#8b552e" stroke-width="7"/><ellipse cx="320" cy="191" rx="15" ry="10" fill="#fff0c8"/><circle cx="320" cy="191" r="6" fill="#e7ad3d"/>';
        case 'bife':
            return '<path d="M217 225c-13-53 38-93 91-67 49-33 115-4 114 48 21 42-37 83-83 61-51 27-107 12-122-42z" fill="#a84939" stroke="#733329" stroke-width="9"/><path d="M260 175l50 94m2-105l50 97m-108-43l113-46" stroke="#f1c07b" stroke-width="8" stroke-linecap="round"/><g fill="#6b3029"><circle cx="273" cy="218" r="7"/><circle cx="359" cy="193" r="7"/><circle cx="342" cy="253" r="7"/></g>';
        case 'pollo':
            return '<path d="M238 221c-26-53 18-95 76-75 42-37 95-9 88 39-3 38-42 71-82 63-38 22-69 11-82-27z" fill="#d6994e" stroke="#a35f31" stroke-width="8"/><path d="M246 235l-48 44m-14-12l21 21m-15-36l21 21" stroke="#f0d7b2" stroke-width="16" stroke-linecap="round"/><g fill="#f4d58e"><circle cx="282" cy="188" r="6"/><circle cx="345" cy="203" r="7"/><circle cx="315" cy="239" r="6"/></g>';
        case 'pescado':
            return '<path d="M211 215c55-79 151-79 215 0-64 79-160 79-215 0z" fill="#78aeb0" stroke="#3e777f" stroke-width="8"/><path d="M422 215l57-47v94z" fill="#d79254" stroke="#3e777f" stroke-width="8"/><circle cx="271" cy="204" r="8" fill="#243844"/><path d="M300 180c25 20 25 48 0 69m42-74c25 20 25 48 0 69m38-64c18 17 18 42 0 58" fill="none" stroke="#d9e6d7" stroke-width="6"/>';
        case 'risotto':
            return '<ellipse cx="320" cy="242" rx="142" ry="65" fill="#fff4d9"/><ellipse cx="320" cy="223" rx="125" ry="55" fill="#e9d49a"/><g fill="#fff5d5"><ellipse cx="253" cy="210" rx="10" ry="6"/><ellipse cx="290" cy="238" rx="10" ry="6"/><ellipse cx="338" cy="200" rx="10" ry="6"/><ellipse cx="374" cy="229" rx="10" ry="6"/><ellipse cx="312" cy="264" rx="10" ry="6"/></g><g fill="#b97946"><circle cx="270" cy="230" r="8"/><circle cx="355" cy="249" r="8"/><circle cx="390" cy="204" r="7"/></g><g fill="#5f9b51"><ellipse cx="315" cy="182" rx="12" ry="6" transform="rotate(-28 315 182)"/><ellipse cx="368" cy="185" rx="12" ry="6" transform="rotate(25 368 185)"/></g>';
        case 'cola':
        case 'cola_zero':
        case 'sprite':
        case 'fanta':
            $body = $tipo === 'cola' ? '#df3432' : ($tipo === 'cola_zero' ? '#272a32' : ($tipo === 'sprite' ? '#70a948' : '#ee7a2e'));
            $mark = $tipo === 'cola' ? 'Coca-Cola' : ($tipo === 'cola_zero' ? 'ZERO' : ($tipo === 'sprite' ? 'Sprite' : 'Fanta'));
            return '<path d="M268 147h104l-8 25 14 19v106c0 15-10 25-25 25h-67c-15 0-25-10-25-25V191l14-19z" fill="' . $body . '" stroke="#e8e9e7" stroke-width="8"/><path d="M269 213h102v53H269z" fill="#fff" opacity=".94"/><text x="320" y="247" text-anchor="middle" font-family="Arial,sans-serif" font-size="22" font-weight="700" fill="' . $body . '">' . $mark . '</text><path d="M282 160h76" stroke="#fff" stroke-width="8" opacity=".6"/>';
        case 'agua':
        case 'agua_gas':
            return '<path d="M288 141h64v27l20 26v116c0 13-10 23-23 23h-58c-13 0-23-10-23-23V194l20-26z" fill="#85c6da" fill-opacity=".72" stroke="#e4f4f5" stroke-width="8"/><path d="M269 216h102v47H269z" fill="#eaf8f5"/><text x="320" y="246" text-anchor="middle" font-family="Arial,sans-serif" font-size="16" font-weight="700" fill="#4b91ab">' . ($tipo === 'agua_gas' ? 'CON GAS' : 'AGUA') . '</text><g fill="#eafcff"><circle cx="297" cy="287" r="5"/><circle cx="323" cy="275" r="4"/><circle cx="344" cy="296" r="5"/></g>';
        case 'limonada':
        case 'jugo':
            $liquid = $tipo === 'limonada' ? '#d0d84d' : '#ef922f';
            return '<path d="M253 170h134l-13 163H266z" fill="#f4f1e3" fill-opacity=".65" stroke="#fff" stroke-width="8"/><path d="M266 222h108l-8 102h-91z" fill="' . $liquid . '"/><path d="M332 151l22 128" stroke="#f8f8f4" stroke-width="9"/><circle cx="371" cy="198" r="25" fill="#d9df54" stroke="#fff4cb" stroke-width="7"/><path d="M371 174v48m-24-24h48" stroke="#fff4cb" stroke-width="5"/>';
        case 'espresso':
        case 'cafe_leche':
            $coffee = $tipo === 'espresso' ? '#503323' : '#9c704c';
            return '<path d="M254 206h123v79c0 31-21 48-61 48s-62-17-62-48z" fill="#fff4df" stroke="#bd9b71" stroke-width="8"/><path d="M265 219h101v48H265z" fill="' . $coffee . '"/><path d="M378 224h23c31 0 27 52-4 52h-20" fill="none" stroke="#bd9b71" stroke-width="11"/><path d="M281 196c-12-19 13-20 2-39m39 39c-12-19 13-20 2-39m38 39c-12-19 13-20 2-39" fill="none" stroke="#f0dfc0" stroke-width="7" stroke-linecap="round"/>';
        case 'papas':
        case 'rusticas':
            if ($tipo === 'papas') {
                return '<path d="M221 215h198l-25 105H244z" fill="#dc7151" stroke="#aa4539" stroke-width="8"/><path d="M256 204l-14-82m43 88l5-100m28 105l24-104m7 103l45-85m-115 91l-17-77" stroke="#efbc50" stroke-width="22" stroke-linecap="round"/><path d="M253 278h132" stroke="#fff" stroke-width="8" opacity=".7"/>';
            }
            return '<g fill="#c98d3e" stroke="#a6662f" stroke-width="7"><path d="M218 258l61-105 64 105z"/><path d="M282 286l64-116 73 116z"/><path d="M230 303l49-82 56 82z"/></g><g stroke="#f3cc75" stroke-width="8"><path d="M246 251l18-31m54 47l18-31m-77 78l13-22"/></g>';
        case 'pure':
        case 'calabaza':
        case 'arroz':
            $fill = $tipo === 'pure' ? '#e0c991' : ($tipo === 'calabaza' ? '#ed9842' : '#f1eee2');
            return '<path d="M205 218h230c-8 73-50 116-115 116s-107-43-115-116z" fill="#e8e1d1" stroke="#c2b7a2" stroke-width="8"/><ellipse cx="320" cy="218" rx="115" ry="38" fill="' . $fill . '"/>' . ($tipo === 'arroz' ? '<g fill="#fff"><ellipse cx="276" cy="208" rx="8" ry="4"/><ellipse cx="309" cy="226" rx="8" ry="4"/><ellipse cx="348" cy="208" rx="8" ry="4"/><ellipse cx="376" cy="225" rx="8" ry="4"/><ellipse cx="291" cy="235" rx="8" ry="4"/></g>' : '<path d="M240 220c32-31 57 22 86-4s55 22 77-2" fill="none" stroke="#fff2d4" stroke-width="8" opacity=".8"/>');
        case 'verduras':
        case 'ensalada_verde':
        case 'ensalada_mixta':
            $green = '<path d="M244 255c-44-50-2-83 35-48 4-60 55-62 58-10 36-46 76-22 46 22 52-5 62 40 12 50-41 30-106 26-151-14z" fill="#72a951" stroke="#487b43" stroke-width="7"/>';
            $toppings = $tipo === 'ensalada_verde' ? '' : '<circle cx="282" cy="225" r="15" fill="#dc5842"/><circle cx="351" cy="258" r="15" fill="#e1b74c"/><circle cx="377" cy="218" r="13" fill="#dc5842"/>';
            return '<ellipse cx="320" cy="260" rx="142" ry="67" fill="#fff9e9"/>' . $green . $toppings . '<path d="M225 260h191" stroke="#e8dfc9" stroke-width="5"/>';
        case 'empanadas':
            return '<g fill="#dcad64" stroke="#a76a3b" stroke-width="8"><path d="M207 251c26-79 150-105 221-30-35 70-153 97-221 30z"/><path d="M254 294c17-55 102-73 150-22-24 49-103 67-150 22z"/></g><path d="M222 245c45-25 130-38 188-22m-141 75c30-19 81-27 116-18" fill="none" stroke="#f5dfad" stroke-width="8"/><g fill="#a76536"><circle cx="245" cy="255" r="5"/><circle cx="274" cy="270" r="5"/><circle cx="300" cy="283" r="5"/></g>';
        case 'bastones':
            return '<g stroke="#bd8a43" stroke-width="6"><rect x="231" y="166" width="43" height="147" rx="20" fill="#f3d381" transform="rotate(-22 252 239)"/><rect x="294" y="147" width="43" height="158" rx="20" fill="#fff0bd" transform="rotate(8 315 226)"/><rect x="352" y="168" width="43" height="147" rx="20" fill="#f3d381" transform="rotate(24 373 241)"/></g><path d="M250 195l30 9m30-4l31 5m31 25l31 8" stroke="#fff9e5" stroke-width="7"/>';
        case 'tiramisu':
        case 'chocotorta':
            $top = $tipo === 'tiramisu' ? '#8a563c' : '#603a32';
            return '<path d="M228 169h184l-10 149H238z" fill="#ead6b4" stroke="#b89b75" stroke-width="8"/><path d="M237 184h166v34H237zm-3 48h166v34H234zm-3 48h165v27H231z" fill="' . $top . '"/><path d="M238 218h165m-169 48h166" stroke="#f4e8d1" stroke-width="9"/><g fill="#fff3dc"><circle cx="273" cy="176" r="8"/><circle cx="320" cy="176" r="8"/><circle cx="367" cy="176" r="8"/></g>';
        case 'torta':
        case 'cheesecake':
            $top = $tipo === 'torta' ? '#62352c' : '#d9bd87';
            $cream = $tipo === 'torta' ? '#9a6042' : '#f4e5be';
            return '<path d="M213 272l97-122 124 122-110 58z" fill="' . $cream . '" stroke="#a27c54" stroke-width="7"/><path d="M213 272l111 58v27l-111-58z" fill="#bd7b4e"/><path d="M324 330l110-58v28l-110 57z" fill="#8a513b"/><path d="M213 272l97-122 124 122-110 58z" fill="' . $top . '" opacity=".92"/><path d="M278 274l32-40 42 41-35 20z" fill="#fff1d0" opacity=".88"/>';
        case 'mousse':
        case 'dulce_leche':
            $fill = $tipo === 'mousse' ? '#704236' : '#b56c3c';
            return '<path d="M252 178h136l-15 158H267z" fill="#fff4dd" fill-opacity=".55" stroke="#e7d8bc" stroke-width="8"/><path d="M265 221h110l-10 103h-90z" fill="' . $fill . '"/><path d="M272 214c24-43 72-43 96 0" fill="none" stroke="' . ($tipo === 'mousse' ? '#8c5c4c' : '#e1a46c') . '" stroke-width="20" stroke-linecap="round"/><g fill="#f5e3be"><circle cx="294" cy="200" r="7"/><circle cx="325" cy="189" r="7"/><circle cx="350" cy="201" r="7"/></g>';
        case 'helado':
            return '<path d="M258 243l62 112 63-112z" fill="#d8a45d" stroke="#a86d3c" stroke-width="7"/><path d="M272 273l80-8m-66 31l51-5" stroke="#f1d7a5" stroke-width="6"/><circle cx="281" cy="220" r="45" fill="#e7a697"/><circle cx="343" cy="213" r="47" fill="#f4d58a"/><circle cx="313" cy="173" r="45" fill="#f7efe0"/><path d="M297 162c14-19 31-14 40 0" fill="none" stroke="#d7864b" stroke-width="9"/>';
        case 'flan':
            return '<path d="M250 199h140l-20 128c-21 28-79 28-100 0z" fill="#e3b254" stroke="#ae703a" stroke-width="8"/><ellipse cx="320" cy="199" rx="70" ry="24" fill="#f0c66f" stroke="#ae703a" stroke-width="8"/><path d="M260 188c27-23 91-27 120-3" fill="none" stroke="#9b5633" stroke-width="11"/><ellipse cx="320" cy="331" rx="80" ry="16" fill="#a75a36" opacity=".8"/>';
        case 'brownie':
            return '<path d="M221 222l123-54 89 56-119 61z" fill="#633729" stroke="#452820" stroke-width="8"/><path d="M221 222v67l93 57v-61z" fill="#8a4b32"/><path d="M314 285l119-61v66l-119 56z" fill="#5a3029"/><circle cx="348" cy="155" r="38" fill="#f2d995"/><circle cx="389" cy="155" r="37" fill="#f7eee0"/><path d="M365 145c11-16 22-16 34 0" fill="none" stroke="#7f472e" stroke-width="8"/>';
        case 'frutas':
            return '<path d="M203 221h234c-8 69-52 110-117 110s-109-41-117-110z" fill="#fff5df" stroke="#d6c8aa" stroke-width="8"/><ellipse cx="320" cy="220" rx="117" ry="43" fill="#e6d3b3"/><g><circle cx="267" cy="207" r="23" fill="#d84c42"/><circle cx="316" cy="197" r="22" fill="#e6bd4e"/><circle cx="360" cy="213" r="23" fill="#83ae4f"/><circle cx="293" cy="234" r="22" fill="#e99042"/><circle cx="344" cy="243" r="21" fill="#aa5d9c"/><circle cx="384" cy="238" r="18" fill="#d8e1ca"/></g>';
        default:
            throw new RuntimeException('No existe una ilustracion para el tipo: ' . $tipo);
    }
}

$outputDirectory = __DIR__ . '/../MVCsix1.0/img/catalogo';
foreach ($catalogo as $producto) {
    list($archivo, $nombre, $categoria, $color, $tipo) = $producto;
    $titulo = catalogo_escape($nombre);
    $etiqueta = catalogo_escape(strtoupper($categoria));
    $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 420" role="img" aria-label="' . $titulo . '">'
        . '<defs><linearGradient id="fondo" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#303848"/><stop offset="1" stop-color="#151922"/></linearGradient><radialGradient id="luz"><stop stop-color="' . $color . '" stop-opacity=".42"/><stop offset="1" stop-color="' . $color . '" stop-opacity="0"/></radialGradient><filter id="sombra" x="-.2" y="-.2" width="1.4" height="1.5"><feDropShadow dx="0" dy="12" stdDeviation="12" flood-color="#000" flood-opacity=".42"/></filter></defs>'
        . '<rect width="640" height="420" rx="28" fill="url(#fondo)"/><circle cx="500" cy="75" r="190" fill="url(#luz)"/><text x="38" y="48" fill="' . $color . '" font-family="Arial,sans-serif" font-size="16" font-weight="700" letter-spacing="3">' . $etiqueta . '</text>'
        . '<g filter="url(#sombra)">' . catalogo_artwork($tipo, $color) . '</g>'
        . '<path d="M38 355h564" stroke="#ffffff" stroke-opacity=".12"/>'
        . '<text x="38" y="390" fill="#fff" font-family="Arial,sans-serif" font-size="25" font-weight="700">' . $titulo . '</text>'
        . '</svg>';
    $path = $outputDirectory . '/' . $archivo . '.svg';
    if (file_put_contents($path, $svg . "\n") === false) {
        fwrite(STDERR, 'No se pudo generar ' . $path . PHP_EOL);
        exit(1);
    }
}

echo 'Ilustraciones generadas: ' . count($catalogo) . PHP_EOL;
