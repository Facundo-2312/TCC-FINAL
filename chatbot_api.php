<?php

require_once __DIR__ . '/app_bootstrap.php';
app_require_login('Login.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'reply' => 'Metodo no permitido.'));
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$message = trim((string) ($data['message'] ?? ''));
if ($message === '') {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'reply' => 'Escribe una consulta para ayudarte.'));
    exit;
}

if (strlen($message) > 280) {
    $message = substr($message, 0, 280);
}

$role = (int) ($_SESSION['Rol'] ?? 0);

function chatbot_normalize_text($text)
{
    $text = strtolower((string) $text);
    $map = array(
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n'
    );
    return strtr($text, $map);
}

function chatbot_connect()
{
    $con = app_db_connect();
    if (!$con) {
        return null;
    }

    return $con;
}

function chatbot_recaudado_hoy($con)
{
    $sql = 'SELECT COALESCE(SUM(monto), 0) AS recaudado, COUNT(*) AS pagos FROM pagos WHERE fecha_dia = CURDATE()';
    $res = mysqli_query($con, $sql);
    if (!$res) {
        return null;
    }

    $row = mysqli_fetch_assoc($res);
    if (!$row) {
        return null;
    }

    return array(
        'recaudado' => (float) ($row['recaudado'] ?? 0),
        'pagos' => (int) ($row['pagos'] ?? 0)
    );
}

function chatbot_propina_hoy($con)
{
    $sql = 'SELECT COALESCE(SUM(propina), 0) AS propina FROM pagos WHERE fecha_dia = CURDATE()';
    $res = mysqli_query($con, $sql);
    if (!$res) {
        $dbError = mysqli_error($con);
        if (stripos($dbError, 'Unknown column') !== false && stripos($dbError, 'propina') !== false) {
            return array('migration_needed' => true);
        }
        return null;
    }

    $row = mysqli_fetch_assoc($res);
    if (!$row) {
        return null;
    }

    return array(
        'propina' => (float) ($row['propina'] ?? 0)
    );
}

$text = chatbot_normalize_text($message);

$address = 'Av.Brasil 1024';
$schedule = 'Lunes a Domingo de 11:00 a 23:30';
$contactPhone = '+1 234 567 890';

$reply = 'Puedo ayudarte con ubicacion, horarios, telefono y recaudado de hoy.';

if (preg_match('/(donde|ubicacion|direccion|local)/', $text)) {
    $reply = 'El local esta en ' . $address . '.';
} elseif (preg_match('/(horario|horarios|abre|abren|cierra|cierre)/', $text)) {
    $reply = 'El horario laboral es: ' . $schedule . '.';
} elseif (preg_match('/(telefono|contacto|llamar)/', $text)) {
    $reply = 'Telefono de contacto: ' . $contactPhone . '.';
} elseif (preg_match('/(recaudado|ventas|cobrado|caja hoy|pagos hoy)/', $text)) {
    if (!in_array($role, array(1, 2), true)) {
        $reply = 'No tienes permisos para ver montos de caja.';
    } else {
        $con = chatbot_connect();
        if (!$con) {
            http_response_code(500);
            echo json_encode(array('ok' => false, 'reply' => 'No pude conectar con la base de datos.'));
            exit;
        }

        $resumen = chatbot_recaudado_hoy($con);
        mysqli_close($con);

        if ($resumen === null) {
            http_response_code(500);
            echo json_encode(array('ok' => false, 'reply' => 'No pude consultar la recaudacion de hoy.'));
            exit;
        }

        $reply = 'Hoy se registran $' . number_format((float) $resumen['recaudado'], 2, ',', '.')
            . ' en ' . (int) $resumen['pagos'] . ' pagos.';
    }
} elseif (preg_match('/(propina|propinas|tip)/', $text)) {
    if (!in_array($role, array(1, 2), true)) {
        $reply = 'No tienes permisos para ver datos de propina.';
    } else {
        $con = chatbot_connect();
        if (!$con) {
            http_response_code(500);
            echo json_encode(array('ok' => false, 'reply' => 'No pude conectar con la base de datos.'));
            exit;
        }

        $resumenPropina = chatbot_propina_hoy($con);
        mysqli_close($con);

        if (is_array($resumenPropina) && !empty($resumenPropina['migration_needed'])) {
            $reply = 'Necesito una migracion de base de datos para habilitar propina (columna pagos.propina).';
        } elseif ($resumenPropina === null) {
            http_response_code(500);
            echo json_encode(array('ok' => false, 'reply' => 'No pude consultar la propina de hoy.'));
            exit;
        } else {
            $reply = 'La propina recaudada hoy es $' . number_format((float) $resumenPropina['propina'], 2, ',', '.') . '.';
        }
    }
}

echo json_encode(array(
    'ok' => true,
    'reply' => $reply,
    'suggestions' => array(
        'Donde esta el local?',
        'Cual es el horario?',
        'Telefono de contacto',
        'Recaudado de hoy',
        'Propina recaudada'
    )
));
