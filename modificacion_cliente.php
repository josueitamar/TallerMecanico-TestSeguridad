<?php
session_start();

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require_once 'conexion_base.php';
require_once 'verificar_sesion_cliente.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Bandera para modales
$modalGuardadoExito = false;
$modalTurnoExito    = false;
$modalErrorMail     = false;

$dni = $_SESSION['cliente_dni'] ?? null;
if (!$dni) {
    header("Location: login.php");
    exit();
}

// Obtener datos del cliente
$stmt = $conexion->prepare("SELECT * FROM clientes WHERE cliente_DNI = :dni");
$stmt->execute(['dni' => $dni]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cliente) {
    // Si no existe, forzamos logout por coherencia
    header("Location: logout.php");
    exit();
}

// ------------------ GUARDAR CAMBIOS ------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['guardar_rec'])) {
    $direccion = $_POST['direccion'] ?? '';
    $localidad = $_POST['localidad'] ?? '';
    $telefono  = $_POST['telefono']  ?? '';
    $correo    = $_POST['correo']    ?? '';
    $clave     = $_POST['clave']     ?? '';

    // Si enviaron una nueva clave, la hasheamos. Si no, dejamos la actual.
    if ($clave !== '') {
        $claveHasheada = password_hash($clave, PASSWORD_DEFAULT);
    } else {
        $claveHasheada = $cliente['cliente_contrasena'];
    }

    $update = $conexion->prepare("
        UPDATE clientes
           SET cliente_direccion = :dir,
               cliente_localidad = :loc,
               cliente_telefono  = :tel,
               cliente_email     = :mail,
               cliente_contrasena= :clave
         WHERE cliente_DNI = :dni
    ");
    $update->execute([
        'dir'   => $direccion,
        'loc'   => $localidad,
        'tel'   => $telefono,
        'mail'  => $correo,
        'clave' => $claveHasheada,
        'dni'   => $dni
    ]);

    // Releer datos para reflejar cambios en el render
    $stmt = $conexion->prepare("SELECT * FROM clientes WHERE cliente_DNI = :dni");
    $stmt->execute(['dni' => $dni]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    $modalGuardadoExito = true;
}

// ------------------ SOLICITAR TURNO ------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['solicitar'])) {
    $clienteCorreo = $cliente['cliente_email']  ?? '';
    $clienteNombre = $cliente['cliente_nombre'] ?? '';

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true; // <- aquí estaba el bug (faltaba el $)
        $mail->Username   = 'wasporttaller@gmail.com';
        $mail->Password   = 'gdkwryakgynsewdl'; // App Password Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->isHTML(true);

        // Mail al cliente
        $mail->setFrom('wasporttaller@gmail.com', 'WA SPORT');
        if ($clienteCorreo !== '') {
            $mail->addAddress($clienteCorreo, $clienteNombre ?: 'Cliente');
        }
        $mail->Subject = 'Solicitud de turno recibida';
        $mail->Body    = 'Hemos recibido su solicitud de turno. A la brevedad será atendido.';
        $mail->send();

        // Mail al taller
        $mail->clearAddresses();
        // Aseguro el mismo correo del taller usado en Username
        $mail->addAddress('wasporttaller@gmail.com', 'WA SPORT');
        $mail->Subject = 'Nuevo turno solicitado';
        $mail->Body    = "El cliente {$clienteNombre} (DNI: {$dni}) ha solicitado un turno.";
        $mail->send();

        $modalTurnoExito = true;
    } catch (Exception $e) {
        $modalErrorMail = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
    <title>Modificar Cliente</title>
</head>
<body>
    <?php include("nav_cli.php"); ?>
    <br>
    <section class="modif_cli">
        <img class="modif_img1" src="fondos/hola.usuario.jpg" alt="">

        <section class="modificar_cliente">
            <h2>Hola <?= htmlspecialchars($cliente['cliente_nombre']) ?></h2>
            <h3>Modificar Datos</h3>
            <form method="post">
                <table>
                    <tr>
                        <th>DNI</th>
                        <td class="datos_unicos"><?= htmlspecialchars($cliente['cliente_DNI']) ?></td>
                    </tr>
                    <tr>
                        <th>NOMBRE</th>
                        <td class="datos_unicos"><?= htmlspecialchars($cliente['cliente_nombre']) ?></td>
                    </tr>
                    <tr>
                        <th>Dirección</th>
                        <td><input type="text" class="datos_modificados" name="direccion" value="<?= htmlspecialchars($cliente['cliente_direccion'] ?? '') ?>"></td>
                    </tr>
                    <tr>
                        <th>Localidad</th>
                        <td><input type="text" class="datos_modificados" name="localidad" value="<?= htmlspecialchars($cliente['cliente_localidad'] ?? '') ?>"></td>
                    </tr>
                    <tr>
                        <th>Teléfono</th>
                        <td><input type="text" class="datos_modificados" name="telefono" value="<?= htmlspecialchars($cliente['cliente_telefono'] ?? '') ?>"></td>
                    </tr>
                    <tr>
                        <th>E-Mail</th>
                        <td><input type="email" class="datos_modificados" name="correo" value="<?= htmlspecialchars($cliente['cliente_email'] ?? '') ?>"></td>
                    </tr>
                    <tr>
                        <th>Contraseña</th>
                        <td>
                            <input
                                type="password"
                                class="datos_modificados"
                                name="clave"
                                minlength="8"
                                maxlength="15"
                                pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$"
                                title="Debe tener entre 8 y 15 caracteres, al menos una mayúscula, un número y un símbolo"
                                placeholder="********">
                            <br>
                            <small>Dejar en blanco para mantener la contraseña actual.</small>
                        </td>
                    </tr>
                </table>
                <div class="bot_modf">
                    <input class="guardar_mod" type="submit" value="Guardar" name="guardar_rec">
                    <a href="registro_vehiculo.php" class="solicitar_mod">Registrar Vehículo</a>
                    <input class="solicitar_mod" type="submit" value="Solicitar Turno" name="solicitar">
                </div>
            </form>
        </section>

        <img class="modif_img2" src="fondos/hola.usuario.jpg" alt="">
    </section>

    <!-- MODAL DATOS GUARDADOS -->
    <?php if ($modalGuardadoExito): ?>
    <dialog open>
        <p><strong>Datos guardados con éxito.</strong></p>
        <form method="get" action="modificacion_cliente.php">
            <button type="submit">Aceptar</button>
        </form>
    </dialog>
    <?php endif; ?>

    <!-- MODAL TURNO SOLICITADO -->
    <?php if ($modalTurnoExito): ?>
    <dialog open>
        <p><strong>Turno solicitado con éxito.</strong></p>
        <form method="get" action="modificacion_cliente.php">
            <button type="submit">Aceptar</button>
        </form>
    </dialog>
    <?php endif; ?>

    <!-- MODAL ERROR ENVÍO DE CORREO -->
    <?php if ($modalErrorMail): ?>
    <dialog open>
        <p><strong>Error al enviar el correo.</strong></p>
        <form method="get" action="modificacion_cliente.php">
            <button type="submit">Volver</button>
        </form>
    </dialog>
    <?php endif; ?>

    <br>
    <?php include("piedepagina.php"); ?>
    <script src="control_inactividad.js"></script>
</body>
</html>

