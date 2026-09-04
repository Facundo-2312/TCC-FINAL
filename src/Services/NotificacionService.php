<?php

namespace App\Services;

use App\Repositories\ReservaRepository;

class NotificacionService
{
    private $repositorio;
    private $configEmail = array();
    private $configSMS = array();

    public function __construct(ReservaRepository $repositorio = null)
    {
        $this->repositorio = $repositorio ?: new ReservaRepository();
        
        // Configurar desde .env
        $this->configEmail = array(
            'habilitado' => getenv('NOTIF_EMAIL_ENABLED') === 'true',
            'de' => getenv('NOTIF_EMAIL_FROM') ?: 'reservas@restaurante.com',
            'asunto' => getenv('NOTIF_EMAIL_SUBJECT') ?: 'Confirmación de Reserva - Restaurant Magnus'
        );

        $this->configSMS = array(
            'habilitado' => getenv('NOTIF_SMS_ENABLED') === 'true',
            'provider' => getenv('NOTIF_SMS_PROVIDER') ?: 'twilio',
            'api_key' => getenv('NOTIF_SMS_API_KEY') ?: ''
        );
    }

    /**
     * Enviar notificación de confirmación de reserva
     */
    public function notificarConfirmacion($idReserva)
    {
        $reserva = $this->repositorio->obtener($idReserva);
        if (!$reserva) {
            return array('ok' => false, 'error' => 'Reserva no encontrada');
        }

        $resultado = array('email' => false, 'sms' => false);

        if ($this->configEmail['habilitado']) {
            $resultado['email'] = $this->enviarEmailConfirmacion($reserva);
        }

        if ($this->configSMS['habilitado'] && !empty($reserva['telefono'])) {
            $resultado['sms'] = $this->enviarSMSConfirmacion($reserva);
        }

        return array('ok' => true, 'resultado' => $resultado);
    }

    /**
     * Enviar email de confirmación
     */
    private function enviarEmailConfirmacion($reserva)
    {
        $para = $reserva['email'] ?? '';
        if (empty($para)) return false;

        $asunto = $this->configEmail['asunto'];
        
        $mensaje = $this->generarEmailHTML($reserva);

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $this->configEmail['de'] . "\r\n";

        return @mail($para, $asunto, $mensaje, $headers);
    }

    /**
     * Generar HTML del email
     */
    private function generarEmailHTML($reserva)
    {
        $fechaInicio = date('d/m/Y H:i', strtotime($reserva['hora_inicio']));
        $fechaFin = date('d/m/Y H:i', strtotime($reserva['hora_fin']));
        $horas = round((strtotime($reserva['hora_fin']) - strtotime($reserva['hora_inicio'])) / 3600, 1);

        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #ff006e, #fb5607); color: white; padding: 20px; border-radius: 8px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-top: 20px; }
                .info { background: #f5f5f5; padding: 15px; border-radius: 6px; margin: 10px 0; }
                .info-row { display: flex; justify-content: space-between; margin: 8px 0; }
                .label { font-weight: bold; color: #666; }
                .value { color: #333; }
                .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>✓ Reserva Confirmada</h2>
                </div>
                
                <div class='content'>
                    <p>Hola <strong>" . htmlspecialchars($reserva['nombre_cliente']) . "</strong>,</p>
                    
                    <p>Tu reserva ha sido confirmada exitosamente.</p>
                    
                    <div class='info'>
                        <div class='info-row'>
                            <span class='label'>Mesa:</span>
                            <span class='value'>Mesa " . (int)$reserva['id_mesa'] . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='label'>Personas:</span>
                            <span class='value'>" . (int)$reserva['cantidad_personas'] . " comensales</span>
                        </div>
                        <div class='info-row'>
                            <span class='label'>Entrada:</span>
                            <span class='value'>" . $fechaInicio . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='label'>Salida:</span>
                            <span class='value'>" . $fechaFin . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='label'>Duración:</span>
                            <span class='value'>" . $horas . " horas</span>
                        </div>
                    </div>

                    " . (!empty($reserva['notas']) ? "<p><strong>Notas:</strong> " . htmlspecialchars($reserva['notas']) . "</p>" : "") . "

                    <p style='color: #666; font-size: 12px; margin-top: 20px;'>
                        Si necesitas cancelar o modificar tu reserva, contacta directamente al restaurante.
                    </p>
                </div>
                
                <div class='footer'>
                    <p>Restaurant Magnus - Reservas</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Enviar SMS de confirmación
     */
    private function enviarSMSConfirmacion($reserva)
    {
        if (!$this->configSMS['habilitado'] || empty($this->configSMS['api_key'])) {
            return false;
        }

        $telefono = $reserva['telefono'];
        $horaInicio = date('H:i', strtotime($reserva['hora_inicio']));
        $horaFin = date('H:i', strtotime($reserva['hora_fin']));

        $mensaje = "Restaurant Magnus: Reserva confirmada para " . $reserva['cantidad_personas'] . 
                   " personas. Mesa " . $reserva['id_mesa'] . ". Entrada: " . $horaInicio . 
                   " - Salida: " . $horaFin . ". Gracias!";

        // Implementar según proveedor (Twilio, AWS SNS, etc)
        // Por ahora solo retorna true simulando envío
        return true;
    }

    /**
     * Obtener próximas reservas que requieren notificación (próximas 30 min)
     */
    public function obtenerProximas($minutos = 30)
    {
        $connection = $this->repositorio->connection();
        $stmt = mysqli_prepare($connection,
            'SELECT r.*, m.numero as mesa_numero FROM reservas r
             INNER JOIN mesas m ON m.id_mesa = r.id_mesa
             WHERE r.estado = "Confirmada"
             AND r.hora_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? MINUTE)
             ORDER BY r.hora_inicio ASC'
        );
        
        mysqli_stmt_bind_param($stmt, 'i', $minutos);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reservas = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
        mysqli_stmt_close($stmt);
        return $reservas;
    }

    /**
     * Obtener reservas próximas a terminar (próximas 30 min)
     */
    public function obtenerProximasASalir($minutos = 30)
    {
        $connection = $this->repositorio->connection();
        $stmt = mysqli_prepare($connection,
            'SELECT r.*, m.numero as mesa_numero FROM reservas r
             INNER JOIN mesas m ON m.id_mesa = r.id_mesa
             WHERE r.estado = "Confirmada"
             AND r.hora_fin BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? MINUTE)
             ORDER BY r.hora_fin ASC'
        );
        
        mysqli_stmt_bind_param($stmt, 'i', $minutos);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reservas = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
        mysqli_stmt_close($stmt);
        return $reservas;
    }
}
