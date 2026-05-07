<?php
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require_once 'conexion_base.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// VARIABLES DE MODALES
$modalDniVacio = false;
$modalDniNoRegistrado = false;
$modalCorreoEnviado = false;
$modalErrorEnvio = false;
$modalAccesoInvalido = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $dni = $_POST['dni_empleado'] ?? '';

    if (empty($dni)) {
        $modalDniVacio = true;
        goto fin;
    }

    try {
        $sql = "SELECT empleado_nombre, empleado_email FROM empleados WHERE empleado_DNI = :dni";
        $stmt = $conexion->prepare($sql);
        $stmt->execute(['dni' => $dni]);

        if ($stmt->rowCount() === 1) {
            $empleado = $stmt->fetch();
            $nombre = $empleado['empleado_nombre'];
            $email = $empleado['empleado_email'];

            $token = bin2hex(random_bytes(32));

            $update = $conexion->prepare("UPDATE empleados SET token_recuperacion = :token WHERE empleado_DNI = :dni");
            $update->execute(['token' => $token, 'dni' => $dni]);

            $link = "http://localhost/tallermecanico/restablecer_contrasena_empleado.php?token=$token";

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'wasporttaller@gmail.com';
            $mail->Password = 'gdkwryakgynsewdl'; // App Password Gmail
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('wasporttaller@gmail.com', 'WA SPORT');
            $mail->addAddress($email, $nombre);
            $mail->isHTML(true);
            $mail->Subject = 'Recuperar contraseña - WA SPORT (Empleado)';
            $mail->Body = "Hola <strong>$nombre</strong>,<br><br>
                Para restablecer su contraseña de empleado, haga clic en el siguiente enlace:<br><br>
                <a href='$link'>$link</a><br><br>
                Si usted no solicitó este cambio, ignore este correo.";

            $mail->send();

            $modalCorreoEnviado = true;
        } else {
            $modalDniNoRegistrado = true;
        }
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    } catch (Exception $e) {
        $modalErrorEnvio = true;
    }
} else {
    $modalAccesoInvalido = true;
}

fin:
?>

<!-- MODAL DEBE INGRESAR DNI -->
<?php if ($modalDniVacio): ?>
<dialog open>
    <p><strong>Debe ingresar su DNI para recuperar la contraseña.</strong></p>
    <form method="get" action="login.php">
        <button type="submit">Volver</button>
    </form>
</dialog>
<?php endif; ?>

<!-- MODAL DNI INGRESADO NO REGISTRADO -->
<?php if ($modalDniNoRegistrado): ?>
<dialog open>
    <p><strong>El DNI ingresado no está registrado.</strong></p>
    <form method="get" action="login.php">
        <button type="submit">Volver</button>
    </form>
</dialog>
<?php endif; ?>

<!-- MODAL ENLACE ENVIADO -->
<?php if ($modalCorreoEnviado): ?>
<dialog open>
    <p><strong>Le enviamos un enlace para restablecer su contraseña.</strong></p>
    <form method="get" action="login.php">
        <button type="submit">Ir al login</button>
    </form>
</dialog>
<?php endif; ?>

<!-- MODAL ENLACE NO ENVIADO -->
<?php if ($modalErrorEnvio): ?>
<dialog open>
    <p><strong>No se pudo enviar el correo. Verifique Datos.</strong></p>
    <form method="get" action="login.php">
        <button type="submit">Volver</button>
    </form>
</dialog>
<?php endif; ?>

<!-- MODAL NO VALIDO -->
<?php if ($modalAccesoInvalido): ?>
<dialog open>
    <p><strong>Acceso no válido.</strong></p>
    <form method="get" action="login.php">
        <button type="submit">Volver</button>
    </form>
</dialog>
<?php endif; ?>
