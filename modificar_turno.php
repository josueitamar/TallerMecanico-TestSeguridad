<?php
session_start();
include 'verificar_sesion_empleado.php';
include 'conexion_base.php';

$turno_id = $_POST['turno_id'] ?? $_GET['turno_id'] ?? null;

if (!$turno_id) {
    die("Turno no especificado.");
}

try {
    // Obtener datos actuales del turno
    $stmt = $conexion->prepare("SELECT t.*, 
                                       ot.servicio_codigo, ot.mecanico_DNI AS mecanico_DNI_OT,
                                       c.cliente_nombre, v.vehiculo_patente, v.vehiculo_marca, v.vehiculo_modelo,
                                       s.servicio_nombre
                                FROM turnos t
                                LEFT JOIN orden_trabajo ot ON t.turno_id = ot.turno_id
                                LEFT JOIN clientes c ON t.cliente_DNI = c.cliente_DNI
                                LEFT JOIN vehiculos v ON t.vehiculo_patente = v.vehiculo_patente
                                LEFT JOIN servicios s ON ot.servicio_codigo = s.servicio_codigo
                                WHERE t.turno_id = ?");
    $stmt->execute([$turno_id]);
    $turno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$turno) {
        die("Turno no encontrado.");
    }

    // Obtener lista de mecánicos
    $stmt = $conexion->prepare("SELECT empleado_DNI, empleado_nombre FROM empleados WHERE empleado_roll = 'mecanico'");
    $stmt->execute();
    $mecanicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener lista de servicios
    $stmt = $conexion->prepare("SELECT servicio_codigo, servicio_nombre FROM servicios");
    $stmt->execute();
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar envío del formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
        $nuevo_mecanico = $_POST['mecanico_DNI'];
        $nuevo_comentario = $_POST['turno_comentario'];
        $nuevo_servicio = $_POST['servicio_codigo'];

        $fecha = $turno['turno_fecha'];
        $hora = $turno['turno_hora'];

        // Validar conflictos
        $conflicto_hora = $conexion->prepare("SELECT * FROM turnos 
            WHERE mecanico_DNI = ? AND turno_fecha = ? AND turno_hora = ? AND turno_id != ?");
        $conflicto_hora->execute([$nuevo_mecanico, $fecha, $hora, $turno_id]);

        if ($conflicto_hora->rowCount() > 0) {
            $modal = "conflicto_hora";
        } else {
            $conflicto_dia = $conexion->prepare("SELECT * FROM turnos 
                WHERE mecanico_DNI = ? AND turno_fecha = ? AND turno_estado = 'pendiente' AND turno_id != ?");
            $conflicto_dia->execute([$nuevo_mecanico, $fecha, $turno_id]);

            if ($conflicto_dia->rowCount() > 0) {
                $modal = "conflicto_dia";
            } else {
                // Sin conflictos, actualizar
                $conexion->beginTransaction();

                $stmt = $conexion->prepare("UPDATE turnos SET mecanico_DNI = ?, turno_comentario = ? WHERE turno_id = ?");
                $stmt->execute([$nuevo_mecanico, $nuevo_comentario, $turno_id]);

                $stmt = $conexion->prepare("UPDATE orden_trabajo SET servicio_codigo = ?, mecanico_DNI = ? WHERE turno_id = ?");
                $stmt->execute([$nuevo_servicio, $nuevo_mecanico, $turno_id]);

                $conexion->commit();

                $modal = "exito";
            }
        }
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Turno</title>
    <link rel="stylesheet" href="estilopagina.css">
</head>
<body>
<?php include("nav_rec.php"); ?>

<section class="turno_mod">
    <h1>Modificar Turno</h1>
    <form method="post">
        <input type="hidden" name="turno_id" value="<?= htmlspecialchars($turno_id) ?>">

        <!-- Campos informativos -->
        <div class="turno_mod-tit"><strong>Cliente</strong> <?= htmlspecialchars($turno['cliente_nombre']) ?></div>
        <div class="turno_mod-tit"><strong>Patente</strong> <?= htmlspecialchars($turno['vehiculo_patente']) ?></div>
        <div class="turno_mod-tit"><strong>Vehículo</strong> <?= htmlspecialchars($turno['vehiculo_marca'] . ' ' . $turno['vehiculo_modelo']) ?></div>
        <div class="turno_mod-tit"><strong>Fecha</strong> <?= htmlspecialchars($turno['turno_fecha']) ?></div>
        <div class="turno_mod-tit"><strong>Hora</strong> <?= substr($turno['turno_hora'], 0, 5) ?></div>
        <div class="turno_mod-tit"><strong>Estado</strong> <?= htmlspecialchars($turno['turno_estado']) ?></div>

        <!-- Campos modificables -->
        <div class="turno_mod-tit">
            <label for="mecanico_DNI">Mecánico</label>
            <select class="turno_mod-sel" name="mecanico_DNI" id="mecanico_DNI" required>
                <?php foreach ($mecanicos as $mec): ?>
                    <option value="<?= $mec['empleado_DNI'] ?>" <?= ($mec['empleado_DNI'] == $turno['mecanico_DNI_OT']) ? 'selected' : '' ?>>
                        <?= $mec['empleado_nombre'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="turno_mod-tit">
            <label for="servicio_codigo">Servicio</label>
            <select class="turno_mod-sel" name="servicio_codigo" id="servicio_codigo" required>
                <?php foreach ($servicios as $serv): ?>
                    <option value="<?= $serv['servicio_codigo'] ?>" <?= ($serv['servicio_codigo'] == $turno['servicio_codigo']) ? 'selected' : '' ?>>
                        <?= $serv['servicio_nombre'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="turno_mod-tit">
            <label for="turno_comentario">Comentario </label>
            <input type="text" name="turno_comentario" id="turno_comentario" value="<?= htmlspecialchars($turno['turno_comentario']) ?>">
        </div>

        <div class="turno_mod-bot">
            <button type="submit" name="guardar">Guardar Cambios</button>
            <br>
            <a href="turnos.php" >Cancelar</a>
        </div>
    </form>
</section>

<?php if (isset($modal)): ?>
    <dialog open id="modal_mensaje">
        <?php if ($modal == "exito"): ?>
            <p>✅ El turno fue modificado correctamente.</p>
            <form method="get" action="turnos.php">
                <button type="submit">Volver</button>
            </form>
        <?php elseif ($modal == "conflicto_dia"): ?>
            <p>⚠️ El mecánico seleccionado ya tiene un turno pendiente ese día.</p>
            <button onclick="document.getElementById('modal_mensaje').close()">Volver</button>
        <?php elseif ($modal == "conflicto_hora"): ?>
            <p>⚠️ El mecánico seleccionado ya tiene un turno en ese horario.</p>
            <button onclick="document.getElementById('modal_mensaje').close()">Volver</button>
        <?php endif; ?>
    </dialog>
<?php endif; ?>
<?php include("piedepagina.php"); ?>
</body>
</html>

