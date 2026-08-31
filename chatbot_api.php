<?php

require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'reply' => 'Metodo no permitido.'));
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$message = trim((string) ($data['message'] ?? ''));
if ($message === '') {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'reply' => 'Escribe una consulta para ayudarte.'));
    exit;
}

$resultado = (new App\Services\ChatbotService())->responder(substr($message, 0, 280), (int) ($_SESSION['Rol'] ?? 0));
if (!$resultado['ok']) {
    http_response_code(500);
}

echo json_encode(array(
    'ok' => $resultado['ok'],
    'reply' => $resultado['reply'],
    'suggestions' => array(
        'Donde esta el local?',
        'Cual es el horario?',
        'Telefono de contacto',
        'Recaudado de hoy',
        'Propina recaudada'
    )
));
