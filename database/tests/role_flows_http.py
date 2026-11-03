"""HTTP integration checks against an isolated PHP server and synthetic MariaDB only."""
import html, http.cookiejar, os, re, urllib.request, urllib.parse
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

def login(role, c=None):
    if c is None:
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

# Chrome tabs share the cookie jar: do not test isolation with separate clients.
shared = client()
contexts = {role: login(role, shared)[1] for role in range(1, 6)}
for role in (1, 4, 2, 5, 3, 1, 5):
    for path in allowed[role]:
        url, _ = request(shared, path + '?ctx=' + contexts[role])
        assert '/Login.php' not in url, ('shared cookie', role, path)

url, body = request(shared, 'Login.php?ctx=' + contexts[1])
fresh_ctx = fields(body)['ctx']
assert fresh_ctx not in contexts.values(), 'A copied login URL must open a new context'
url, _ = request(shared, 'Principal.php?ctx=' + contexts[1])
assert '/Principal.php' in url, 'Opening another login must preserve the original tab'
url, _ = request(shared, 'Principal.php')
assert '/Login.php' in url, 'Missing ctx must not borrow the last tab identity'

_, body = request(shared, 'MVCsix1.0/index.php?ctx=' + contexts[1])
for target in ('Principal.php', 'MVCsix1.0/create.php'):
    assert re.search(r'href="[^"]*/' + re.escape(target) + r'\?ctx=' + contexts[1] + r'"', body), target
url, _ = request(shared, 'Salir.php?ctx=' + contexts[2])
assert '/Login.php' in url
url, _ = request(shared, 'CAJA.php?ctx=' + contexts[2])
assert '/Login.php' in url, 'Logged out tab was restored'
for role in (1, 3, 4, 5):
    url, _ = request(shared, allowed[role][0] + '?ctx=' + contexts[role])
    assert '/Login.php' not in url, ('logout affected another tab', role)
print('PASS five roles share one cookie; login URLs, navigation and logout stay isolated')

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
