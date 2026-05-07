<?php
session_start();
require_once 'conexion_base.php';
require_once 'verificar_sesion_empleado.php';

// PHPMailer
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// VARIABLES PARA MODALES
$modalTurnoOK = false;
$modalTurnoError = false;
$modalFaltanDatos = false;
$modalVehiculoNoEncontrado = false;
$modalClienteNoEncontrado = false;
$modalNoCoincideDuenio = false;
$modalErrorKilometraje = false;
$modalErrorKilometrajeMenor = false;
$mailEnviado = false;
$mailError   = '';
$mailDest    = '';

// DATOS RECIBIDOS DESDE TURNOS.php
$fecha = $_POST['fecha'] ?? '';
$hora  = $_POST['hora']  ?? '';

// AL ENVIAR FORMULARIO DE ASIGNAR TURNO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar'])) {
    $cliente_dni = trim($_POST['cliente_dni'] ?? '');
    $patente     = strtoupper(trim($_POST['vehiculo_patente'] ?? ''));
    $servicio    = $_POST['servicio_codigo'] ?? '';
    $mecanico    = $_POST['mecanico_dni'] ?? '';
    $km          = trim($_POST['orden_kilometros'] ?? '');
    $comentario  = trim($_POST['orden_comentario'] ?? '');

    // VALIDACION DE CAMPOS OBLIGATORIOS
    if ($cliente_dni === '' || $patente === '' || $servicio === '' || $mecanico === '' || $km === '') {
        $modalFaltanDatos = true;
    } else {
        try {
            // VERIFICA CLIENTE EXISTA
            $stmtCli = $conexion->prepare("SELECT 1 FROM clientes WHERE cliente_DNI = :dni");
            $stmtCli->execute([':dni' => $cliente_dni]);
            if ($stmtCli->rowCount() === 0) {
                $modalClienteNoEncontrado = true;
                goto saltar_insercion;
            }

            // VERIFICA VEHICULO EXISTA Y TRAE DUEÑO
            $stmtVeh = $conexion->prepare("SELECT cliente_DNI FROM vehiculos WHERE vehiculo_patente = :patente");
            $stmtVeh->execute([':patente' => $patente]);
            $veh = $stmtVeh->fetch(PDO::FETCH_ASSOC);
            if (!$veh) {
                $modalVehiculoNoEncontrado = true;
                goto saltar_insercion;
            }

            // CONCUERDA PATENTE-DUEÑO
            if ($veh['cliente_DNI'] !== $cliente_dni) {
                $modalNoCoincideDuenio = true;
                goto saltar_insercion;
            }

            // VALIDA KM 
            if (!ctype_digit($km)) {
                $modalErrorKilometraje = true;
                goto saltar_insercion;
            }
            $kmInt = (int)$km;

            // 4) TRAE ULTIMO REGISTRADO
            $stmtMaxKm = $conexion->prepare("
                SELECT MAX(ot.orden_kilometros) AS max_km
                FROM orden_trabajo ot
                JOIN ordenes o ON o.orden_numero = ot.orden_numero
                WHERE o.vehiculo_patente = :patente
            ");
            $stmtMaxKm->execute([':patente' => $patente]);
            $maxKm = (int)($stmtMaxKm->fetch(PDO::FETCH_ASSOC)['max_km'] ?? 0);

            if ($kmInt < $maxKm) {
                $modalErrorKilometrajeMenor = true;
                goto saltar_insercion;
            }

            $conexion->beginTransaction();

            // OBTENER NUEVO NUMERO ORDEN
            $stmtMax = $conexion->query("SELECT COALESCE(MAX(orden_numero),0) FROM ordenes");
            $nuevoNumero = (int)$stmtMax->fetchColumn() + 1;

            // INSERTAR ORDEN
            $stmtOrden = $conexion->prepare("
                INSERT INTO ordenes (orden_numero, orden_fecha, vehiculo_patente)
                VALUES (?, ?, ?)
            ");
            $stmtOrden->execute([$nuevoNumero, $fecha, $patente]);

            // INSERTAR TURNO
            $stmtTurno = $conexion->prepare("
                INSERT INTO turnos (turno_fecha, turno_hora, turno_estado, turno_comentario, mecanico_DNI, cliente_DNI, vehiculo_patente)
                VALUES (?, ?, 'pendiente', ?, ?, ?, ?)
            ");
            $stmtTurno->execute([$fecha, $hora, $comentario, $mecanico, $cliente_dni, $patente]);
            $turno_id = (int)$conexion->lastInsertId();

            // INSERTAR orden_trabajo (POR DEFECTO complejidad: 1, estado: 0)            
            $stmtTrabajo = $conexion->prepare("
                INSERT INTO orden_trabajo
                    (orden_numero, orden_kilometros, orden_comentario, turno_id, servicio_codigo, complejidad, mecanico_DNI, orden_estado)
                VALUES
                    (?, ?, ?, ?, ?, 1, ?, 0)
            ");
            $stmtTrabajo->execute([$nuevoNumero, $kmInt, $comentario, $turno_id, $servicio, $mecanico]);

            // ENVIAR MAIL DE CONFIRMACIÓN 
            $queryMail = $conexion->prepare("
                SELECT c.cliente_nombre, c.cliente_email,
                       v.vehiculo_marca, v.vehiculo_modelo,
                       s.servicio_nombre, s.servicio_descripcion,
                       t.turno_fecha, t.turno_hora
                FROM turnos t
                JOIN clientes c ON t.cliente_DNI = c.cliente_DNI
                JOIN vehiculos v ON t.vehiculo_patente = v.vehiculo_patente
                JOIN orden_trabajo ot ON t.turno_id = ot.turno_id
                JOIN servicios s ON ot.servicio_codigo = s.servicio_codigo
                WHERE t.turno_id = :turno_id
            ");
            $queryMail->execute([':turno_id' => $turno_id]);
            $datos = $queryMail->fetch(PDO::FETCH_ASSOC);

            if ($datos && !empty($datos['cliente_email'])) {
                $mailDest = $datos['cliente_email'];
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'wasporttaller@gmail.com';
                    $mail->Password = 'gdkwryakgynsewdl'; // App Password Gmail
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('wasporttaller@gmail.com', 'WA SPORT');
                    $mail->addAddress($datos['cliente_email'], $datos['cliente_nombre']);
                    $mail->isHTML(true);
                    $mail->Subject = 'Confirmación de turno asignado - WA SPORT';
                    $mail->Body = "
                        <h2>Hola {$datos['cliente_nombre']},</h2>
                        <p>Te confirmamos que tu turno fue asignado con éxito.</p>
                        <p><strong>Fecha:</strong> {$datos['turno_fecha']}<br>
                        <strong>Hora:</strong> {$datos['turno_hora']}<br>
                        <strong>Vehículo:</strong> {$datos['vehiculo_marca']} {$datos['vehiculo_modelo']}<br>
                        <strong>Servicio:</strong> {$datos['servicio_nombre']}<br>
                        <strong>Descripción:</strong> {$datos['servicio_descripcion']}</p>
                        <p><em>Por favor, concurrí 10 minutos antes del horario establecido.</em></p>
                        <br>
                        <p>Gracias por elegirnos.<br>Equipo de <strong>WA SPORT</strong>.</p>
                    ";
                    $mail->send();
                    $mailEnviado = true;
                } catch (Exception $e) {
                    $mailEnviado = false;
                    $mailError   = $mail->ErrorInfo ?: $e->getMessage();
                    error_log("Error al enviar email: " . $mail->ErrorInfo);
                }
            } else {
                // CLIENTE NO TIENE MAIL
                $mailEnviado = false;
                $mailDest    = '';
            }

            $conexion->commit();
            $modalTurnoOK = true;

        } catch (PDOException $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            error_log("Error BD asignar_turno: " . $e->getMessage());
            $modalTurnoError = true;
        }
    }
}
saltar_insercion:

// TRAER DATOS DESPLEGABLES
$servicios = $conexion->query("SELECT servicio_codigo, servicio_nombre FROM servicios")->fetchAll(PDO::FETCH_ASSOC);
$mecanicos = $conexion->query("SELECT empleado_DNI, empleado_nombre FROM empleados WHERE empleado_roll = 'mecanico'")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Turno</title>
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
</head>

<!-- MODALES -->

<?php if ($modalTurnoOK): ?>
<dialog open>
    <p style="text-align:center;"><strong>Turno registrado con éxito</strong></p>

    <?php if ($mailEnviado && $mailDest !== ''): ?>
        <p style="text-align:center;">
            Se envió el email de confirmación a:
            <strong><?= htmlspecialchars($mailDest) ?></strong>
        </p>
    <?php elseif (!$mailEnviado && $mailDest !== '' && $mailError !== ''): ?>
        <p style="text-align:center; color:#a00;">
            El turno se registró, pero <strong>no se pudo enviar el email</strong>.<br>
            Detalle: <?= htmlspecialchars($mailError) ?>
        </p>
    <?php else: ?>
        <p style="text-align:center;">
            El cliente no tiene email registrado o el correo no se envió.
        </p>
    <?php endif; ?>

    <div style="text-align:center;">
        <form method="post" action="turnos.php">
            <button type="submit">Aceptar</button>
        </form>
    </div>
</dialog>
<?php endif; ?>

<?php if ($modalTurnoError): ?>
<dialog open>
    <p style="text-align:center;"><strong>Error al registrar el turno</strong></p>
    <div style="text-align:center;">
        <form method="post" action="turnos.php">
            <button type="submit">Volver</button>
        </form>
    </div>
</dialog>
<?php endif; ?>

<?php if ($modalFaltanDatos): ?>
<dialog open>
    <p style="text-align:center;"><strong>Debe completar todos los campos obligatorios</strong></p>
    <div style="text-align:center;">
        <form method="post" action="turnos.php">
            <button type="submit">Volver</button>
        </form>
    </div>
</dialog>
<?php endif; ?>

<?php if ($modalClienteNoEncontrado): ?>
<dialog open>
    <p style="text-align:center;"><strong>Cliente inexistente</strong></p>
    <p style="text-align:center;">No existe un cliente con el DNI ingresado.</p>
    <div style="text-align:center; display:flex; gap:10px; justify-content:center;">
        <a href="registro_cliente_recepcionista.php"><button type="button">Registrar cliente</button></a>
        <a href="turnos.php"><button type="button">Volver</button></a>
    </div>
</dialog>
<?php endif; ?>

<?php if ($modalVehiculoNoEncontrado): ?>
<dialog open>
    <p style="text-align:center;"><strong>Vehículo no encontrado</strong></p>
    <p style="text-align:center;">No se ha encontrado un vehículo con la patente ingresada.</p>
    <div style="text-align:center; display:flex; gap:10px; justify-content:center;">
        <a href="recepcionista.php"><button type="button">Registrar vehículo</button></a>
        <a href="turnos.php"><button type="button">Volver</button></a>
    </div>
</dialog>
<?php endif; ?>

<?php if ($modalNoCoincideDuenio): ?>
<dialog open>
    <p style="text-align:center;"><strong>No coincide el dueño del vehículo</strong></p>
    <p style="text-align:center;">La patente ingresada pertenece a otro cliente distinto del DNI proporcionado.</p>
    <div style="text-align:center;">
        <form method="post" action="asignar_turno.php">
            <button type="submit">Volver</button>
        </form>
    </div>
</dialog>
<?php endif; ?>

<?php if ($modalErrorKilometraje): ?>
<dialog open>
    <p style="text-align:center;"><strong>Error en el campo Kilometraje</strong></p>
    <p style="text-align:center;">Ingrese un número entero válido (sin puntos, comas ni letras).</p>
    <div style="text-align:center;">
        <form method="post" action="asignar_turno.php">
            <button type="submit">Volver</button>
        </form>
    </div>
</dialog>
<?php endif; ?>

<?php if ($modalErrorKilometrajeMenor): ?>
<dialog open>
    <p style="text-align:center;"><strong>Kilometraje inválido</strong></p>
    <p style="text-align:center;">El kilometraje ingresado es menor al último registrado para esta patente.</p>
    <div style="text-align:center;">
        <form method="post" action="asignar_turno.php">
            <button type="submit">Volver</button>
        </form>
    </div>
</dialog>
<?php endif; ?>

<body>
<?php include("nav_rec.php"); ?>
<section class="turno_asig">
     <img class="turno-asig_img" src="fondos/mecanico_fond2.jpg" alt="">

    <form method="post" class="turnos_form_asig">
        <h2>Asignar nuevo turno</h2>
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
        <input type="hidden" name="hora" value="<?= htmlspecialchars($hora) ?>">

        <label for="cliente_dni">DNI Cliente</label>
        <input type="text" name="cliente_dni" id="cliente_dni" required>

        <label for="vehiculo_patente">Patente Vehículo</label>
        <input type="text" name="vehiculo_patente" id="vehiculo_patente" required>

        <label for="servicio_codigo">Servicio</label>
        <select name="servicio_codigo" id="servicio_codigo" required>
            <option value="">Seleccione</option>
            <?php foreach ($servicios as $s): ?>
                <option value="<?= htmlspecialchars($s['servicio_codigo']) ?>">
                    <?= htmlspecialchars($s['servicio_nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="mecanico_dni">Mecánico</label>
        <select name="mecanico_dni" id="mecanico_dni" required>
            <option value="">Seleccione</option>
            <?php foreach ($mecanicos as $m): ?>
                <option value="<?= htmlspecialchars($m['empleado_DNI']) ?>">
                    <?= htmlspecialchars($m['empleado_nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="orden_kilometros">Kilometraje</label>
        <input type="number" name="orden_kilometros" id="orden_kilometros" inputmode="numeric" min="0" required>

        <label for="orden_comentario">Comentario</label>
        <textarea name="orden_comentario" id="orden_comentario" class="turnos_area"></textarea>

        <div class="boton-turno_asig">
            <input type="submit" name="asignar" value="Guardar Turno">
            <br>
            <a href="turnos.php">Cancelar</a>
        </div>
    </form>

    <img class="turno-asig_img" src="fondos/mecanico_fond2.jpg" alt="">
</section>
<?php include("piedepagina.php"); ?>
<script src="control_inactividad.js"></script>
</body>
</html>

