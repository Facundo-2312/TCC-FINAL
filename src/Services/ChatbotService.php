<?php

namespace App\Services;

class ChatbotService
{
    private $reportes;

    public function __construct(ReporteService $reportes = null)
    {
        $this->reportes = $reportes ?: new ReporteService();
    }

    public function responder($mensaje, $rol)
    {
        $texto = $this->normalizar($mensaje);
        $respuesta = 'Puedo ayudarte con ubicacion, horarios, telefono y recaudado de hoy.';
        if (preg_match('/(donde|ubicacion|direccion|local)/', $texto)) {
            $respuesta = 'El local esta en Av.Brasil 1024.';
        } elseif (preg_match('/(horario|horarios|abre|abren|cierra|cierre)/', $texto)) {
            $respuesta = 'El horario laboral es: Lunes a Domingo de 11:00 a 23:30.';
        } elseif (preg_match('/(telefono|contacto|llamar)/', $texto)) {
            $respuesta = 'Telefono de contacto: +1 234 567 890.';
        } elseif (preg_match('/(recaudado|ventas|cobrado|caja hoy|pagos hoy)/', $texto)) {
            if (!in_array((int) $rol, array(1, 2), true)) return array('ok' => true, 'reply' => 'No tienes permisos para ver montos de caja.');
            $resumen = $this->reportes->resumenPagosHoy();
            if ($resumen === null) return array('ok' => false, 'reply' => 'No pude consultar la recaudacion de hoy.');
            $respuesta = 'Hoy se registran $' . number_format($resumen['recaudado'], 2, ',', '.') . ' en ' . $resumen['pagos'] . ' pagos.';
        } elseif (preg_match('/(propina|propinas|tip)/', $texto)) {
            if (!in_array((int) $rol, array(1, 2), true)) return array('ok' => true, 'reply' => 'No tienes permisos para ver datos de propina.');
            $resumen = $this->reportes->resumenPagosHoy();
            if ($resumen === null) return array('ok' => false, 'reply' => 'No pude consultar la propina de hoy.');
            $respuesta = 'La propina recaudada hoy es $' . number_format($resumen['propina'], 2, ',', '.') . '.';
        }
        return array('ok' => true, 'reply' => $respuesta);
    }

    private function normalizar($texto)
    {
        return strtr(strtolower((string) $texto), array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'));
    }
}