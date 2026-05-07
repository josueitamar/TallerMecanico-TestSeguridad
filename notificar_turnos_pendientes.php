<?php
include 'conexion_base.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mañana = date('Y-m-d', strtotime('+1 day'));

$query = $conexion->prepare("
    SELECT t.turno_id, t.turno_fecha, t.turno_hora,
           c.cliente_nombre, c.cliente_email,
           v.vehiculo_marca, v.vehiculo_modelo,
           s.servicio_nombre, s.servicio_descripcion
    FROM turnos t
    JOIN clientes c ON t.cliente_DNI = c.cliente_DNI
    JOIN vehiculos v ON t.vehiculo_patente = v.vehiculo_patente
    JOIN orden_trabajo ot ON t.turno_id = ot.turno_id
    JOIN servicios s ON ot.servicio_codigo = s.servicio_codigo
    WHERE t.turno_fecha = :fecha AND t.turno_estado = 'pendiente'
");
$query->execute([':fecha' => $mañana]);
$turnos = $query->fetchAll(PDO::FETCH_ASSOC);

foreach ($turnos as $t) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tucorreo@gmail.com';
        $mail->Password = 'tu_contraseña';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('taller@wasport.com', 'Taller WA SPORT');
        $mail->addAddress($t['cliente_email'], $t['cliente_nombre']);
        $mail->isHTML(true);

        $mail->Subject = 'Recordatorio de Turno - WA SPORT';
        $mail->Body = "
            <h2>Hola {$t['cliente_nombre']},</h2>
            <p>Te recordamos que tenés un turno asignado para mañana en nuestro taller <strong>WA SPORT</strong>.</p>
            <p>
                <strong> Fecha:</strong> {$mañana}<br>
                <strong> Hora:</strong> {$t['turno_hora']}<br>
                <strong> Vehículo:</strong> {$t['vehiculo_marca']} {$t['vehiculo_modelo']}<br>
                <strong> Servicio:</strong> {$t['servicio_nombre']}<br>
                <strong> Descripción:</strong> {$t['servicio_descripcion']}
            </p>
            <p>
                Si podés asistir, por favor <strong>respondé a este correo con la palabra "CONFIRMO".</strong><br>
                Si no podés venir, respondé con la palabra <strong>"CANCELO"</strong> para poder reagendar tu turno.
            </p>
            <p>
                ¡Gracias por confiar en nosotros!<br>
                <em>Equipo de WA SPORT</em>
            </p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar recordatorio de turno (ID {$t['turno_id']}): {$mail->ErrorInfo}");
    }
}

