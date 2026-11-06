<?php

if (PHP_SAPI !== 'cli' || getenv('APP_DB_NAME') !== 'ci_test') exit(1);

$password = getenv('FLOW_TEST_PASSWORD');
if (!$password) throw new RuntimeException('Missing temporary test password');

function checkCustomer($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
}

function customerRequest($client, $path, $data = null)
{
    $base = 'http://127.0.0.1:8080/';
    curl_setopt_array($client, array(
        CURLOPT_URL => strpos($path, $base) === 0 ? $path : $base . ltrim($path, '/'),
        CURLOPT_POST => $data !== null,
        CURLOPT_POSTFIELDS => $data === null ? null : http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 8,
        CURLOPT_TIMEOUT => 20
    ));
    // CURLOPT_POSTFIELDS also enables POST, so set the method last.
    curl_setopt($client, CURLOPT_POST, $data !== null);
    $body = curl_exec($client);
    checkCustomer($body !== false, 'HTTP request failed: ' . curl_error($client));
    checkCustomer(curl_getinfo($client, CURLINFO_HTTP_CODE) === 200, 'Unexpected HTTP status: ' . $path);
    checkCustomer(strpos($body, 'Ocurrio un error inesperado') === false, 'Application error: ' . $path);
    return array(curl_getinfo($client, CURLINFO_EFFECTIVE_URL), $body);
}

function customerFields($body)
{
    preg_match_all('/name="(_csrf|ctx)" value="([^"]*)"/', $body, $matches, PREG_SET_ORDER);
    $fields = array();
    foreach ($matches as $match) $fields[$match[1]] = html_entity_decode($match[2], ENT_QUOTES, 'UTF-8');
    checkCustomer(isset($fields['_csrf'], $fields['ctx']), 'Missing customer form fields');
    return $fields;
}

function checkCustomerPage($url, $page)
{
    checkCustomer(parse_url($url, PHP_URL_PATH) === '/' . $page, 'Unexpected destination: ' . $url);
}

$client = curl_init();
curl_setopt($client, CURLOPT_COOKIEFILE, '');

try {
    list($url, $body) = customerRequest($client, 'cliente.php');
    checkCustomerPage($url, 'cliente_publico.php');
    checkCustomer(preg_match('/^[a-f0-9]{64}$/', customerFields($body)['ctx']) === 1,
        'Direct customer access must have a tab context');

    list($loginUrl, $body) = customerRequest($client, 'Login.php');
    $loginFields = customerFields($body);
    checkCustomer(preg_match('/href="([^"]*cliente_publico\.php[^"]*)"/', $body, $link) === 1,
        'Missing public customer link');
    list($url, $body) = customerRequest($client, html_entity_decode($link[1], ENT_QUOTES, 'UTF-8'));
    checkCustomerPage($url, 'cliente_publico.php');
    $data = customerFields($body);
    $data['nombre_cliente'] = '123';
    list($url, $body) = customerRequest($client, $url, $data);
    checkCustomerPage($url, 'cliente_publico.php');
    checkCustomer(strpos($body, 'Ingresa un nombre valido') !== false, 'Invalid names must be rejected');

    $data = customerFields($body);
    $data['nombre_cliente'] = 'Ana Invitada';
    $data['_csrf'] = 'invalid';
    list($url, $body) = customerRequest($client, $url, $data);
    checkCustomerPage($url, 'cliente_publico.php');
    checkCustomer(strpos($body, 'formulario expiro') !== false, 'Invalid CSRF must be rejected');

    $data = customerFields($body);
    $data['nombre_cliente'] = 'Ana Invitada';
    list($customerUrl, $body) = customerRequest($client, $url, $data);
    checkCustomerPage($customerUrl, 'cliente.php');
    checkCustomer(strpos($body, 'Ana Invitada') !== false, 'Guest name is missing from the order page');
    checkCustomer(strpos($body, 'id="order-form"') !== false && strpos($body, 'name="crear_pedido"') !== false,
        'Guest cannot access the order form');
    parse_str(parse_url($customerUrl, PHP_URL_QUERY), $customerQuery);
    checkCustomer($customerQuery['ctx'] !== $loginFields['ctx'], 'Guest reused the login tab context');

    customerRequest($client, $loginUrl);
    list($url, $body) = customerRequest($client, $customerUrl);
    checkCustomerPage($url, 'cliente.php');
    checkCustomer(strpos($body, 'Ana Invitada') !== false, 'Returning to login erased the guest');
    list($url) = customerRequest($client, 'cliente_publico.php?ctx=' . $customerQuery['ctx']);
    checkCustomerPage($url, 'cliente.php');

    list(, $body) = customerRequest($client, 'Login.php');
    $data = customerFields($body);
    $data['Usuario'] = 'flow_role_1';
    $data['Pass'] = $password;
    list($adminUrl) = customerRequest($client, 'interfazlog.php', $data);
    checkCustomerPage($adminUrl, 'Principal.php');
    parse_str(parse_url($adminUrl, PHP_URL_QUERY), $adminQuery);
    list($url, $body) = customerRequest($client, 'cliente_publico.php?ctx=' . $adminQuery['ctx']);
    checkCustomerPage($url, 'cliente_publico.php');
    $data = customerFields($body);
    checkCustomer($data['ctx'] !== $adminQuery['ctx'], 'Public access reused the staff context');
    $data['nombre_cliente'] = 'Bruno Invitado';
    list($secondCustomerUrl, $body) = customerRequest($client, $url, $data);
    checkCustomerPage($secondCustomerUrl, 'cliente.php');
    checkCustomer(strpos($body, 'Bruno Invitado') !== false, 'Second guest could not access the order page');
    list($url) = customerRequest($client, $adminUrl);
    checkCustomerPage($url, 'Principal.php');
    list($url, $body) = customerRequest($client, $customerUrl);
    checkCustomerPage($url, 'cliente.php');
    checkCustomer(strpos($body, 'Ana Invitada') !== false, 'Second guest changed the first guest');
    echo "PASS public customer entry, validation, order page and isolation from login/staff tabs\n";
} finally {
    curl_close($client);
}
