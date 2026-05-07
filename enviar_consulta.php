<?php

// Incluir PHPMailer
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirigir si se accede por GET (acceso directo)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contacto.php");
    exit();
}

// Verificar que los campos existen en $_POST
$nombre   = $_POST['nombre']   ?? null;
$telefono = $_POST['telefono'] ?? null;
$email    = $_POST['email']    ?? null;
$consulta = $_POST['consulta'] ?? null;

if (!$nombre || !$telefono || !$email || !$consulta) {
    echo "<script>alert('Todos los campos son obligatorios.'); window.location.href = 'contacto.php';</script>";
    exit();
}

try {
    // ENVÍO AL TALLER
    $mail = new PHPMailer(true);

    $mail->SMTPDebug = 2;         // Nivel de depuración: 2 muestra detalles del servidor
    $mail->Debugoutput = 'html'; // Muestra la salida como HTML legible

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'wasporttaller@gmail.com';
    $mail->Password = 'gdkwryakgynsewdl'; // App Password Gmail
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom($email, $nombre);
    $mail->addAddress('wasporttaller@gmail.com', 'WA SPORT');
    $mail->addReplyTo($email, $nombre); // ← para que puedan responder al cliente

    $mail->isHTML(true);
    $mail->Subject = 'Nueva consulta desde la web';
    $mail->Body    = "<strong>Nombre:</strong> $nombre<br>
                      <strong>Teléfono:</strong> $telefono<br>
                      <strong>Email:</strong> $email<br>
                      <strong>Consulta:</strong><br>$consulta";

    $mail->send();

    // ENVÍO DE CONFIRMACIÓN AL CLIENTE
    $cliente = new PHPMailer(true);

    $cliente->SMTPDebug = 2;
    $cliente->Debugoutput = 'html';

    $cliente->isSMTP();
    $cliente->Host = 'smtp.gmail.com';
    $cliente->SMTPAuth = true;
    $mail->Username = 'wasporttaller@gmail.com';
    $mail->Password = 'gdkwryakgynsewdl'; // App Password Gmail
    $cliente->SMTPSecure = 'tls';
    $cliente->Port = 587;

    $cliente->setFrom('wasporttaller@gmail.com', 'WA SPORT');
    $cliente->addAddress($email, $nombre);
    $cliente->isHTML(true);
    $cliente->Subject = 'Consulta recibida - WA SPORT';
    $cliente->Body = "Hola <strong>$nombre</strong>,<br><br>
                      Hemos recibido tu consulta:<br><blockquote>$consulta</blockquote><br>
                      Nos comunicaremos a la brevedad.<br><br>
                      Saludos,<br><strong>WA SPORT</strong>";

    $cliente->send();

    echo "<script>alert('Su consulta fue enviada con éxito.'); window.location.href = 'contacto.php';</script>";

}catch (Exception $e) {
   echo "<script>alert('Error al enviar el correo: {$mail->ErrorInfo}'); window.location.href = 'contacto.php';</script>";
}

//Probar conexión manual
if (!$mail->smtpConnect()) {
    echo "Error de conexión SMTP: ";
    print_r($mail->ErrorInfo);
    exit();
}

?>
