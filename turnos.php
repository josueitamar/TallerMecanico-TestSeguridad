<?php
session_start();
include 'verificar_sesion_empleado.php';
include 'conexion_base.php';

try {
    $consultaMec = $conexion->prepare("SELECT empleado_DNI, empleado_nombre FROM empleados WHERE empleado_roll = 'mecanico'");
    $consultaMec->execute();
    $mecanicos = $consultaMec->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conexion->prepare("
        SELECT t.turno_id, t.turno_fecha, t.turno_hora, t.turno_comentario, t.turno_estado,
               c.cliente_nombre,
               v.vehiculo_marca, v.vehiculo_modelo,
               e.empleado_nombre AS mecanico_nombre,
               s.servicio_nombre
        FROM turnos t
        LEFT JOIN vehiculos v ON t.vehiculo_patente = v.vehiculo_patente
        LEFT JOIN clientes c ON t.cliente_DNI = c.cliente_DNI
        LEFT JOIN empleados e ON t.mecanico_DNI = e.empleado_DNI
        LEFT JOIN orden_trabajo ot ON t.turno_id = ot.turno_id
        LEFT JOIN servicios s ON ot.servicio_codigo = s.servicio_codigo
        WHERE t.turno_fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)
        ORDER BY t.turno_fecha ASC, t.turno_hora ASC
    ");

    $stmt->execute();
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al obtener turnos: " . $e->getMessage());
}


$turnosPorDiaHora = [];
foreach ($turnos as $turno) {
    $fecha = $turno['turno_fecha'];
    $hora = date('H:i', strtotime($turno['turno_hora']));
    $turnosPorDiaHora[$fecha][$hora][] = $turno;    
}

$horarios = ["08:00", "09:00", "10:00", "11:00", "12:00", "13:00", "14:00", "15:00", "16:00", "17:00"];
$dias = [];
for ($i = 0; $i < 7; $i++) {
    $fecha = date('Y-m-d', strtotime("+$i day"));
    if (date('N', strtotime($fecha)) < 6) {
        $dias[] = $fecha;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Turnos - WA SPORT</title>
    <link rel="stylesheet" href="estilopagina.css">
</head>
<body>
<?php include("nav_rec.php"); ?>
    <h1 class="turnos_tit">Agenda semanal de turnos</h1>

<!-- ✅ MODALES DE MENSAJES DE RESULTADO -->
<?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'ok'): ?>
<dialog open>
    <p><strong>✅ Turno eliminado correctamente.</strong></p>
    <form method="get" action="turnos.php">
        <button class="btn-ok" type="submit">Aceptar</button>
    </form>
</dialog>
<?php elseif (isset($_GET['mensaje']) && $_GET['mensaje'] === 'error'): ?>
<dialog open>
    <p><strong>❌ Ocurrió un error al eliminar el turno.</strong></p>
    <form method="get" action="turnos.php">
        <button class="btn-cancelar" type="submit">Cerrar</button>
    </form>
</dialog>
<?php endif; ?>

<main>
    <?php foreach ($dias as $fecha): ?>
        <section class="turnos">
            <br>
            <h3>Turnos para el día <?= $fecha ?></h3>
            <br>
            <table>
                <tr>
                    <th>Hora</th>
                    <th>Cliente</th>
                    <th>Vehículo</th>
                    <th>Servicio</th>
                    <th>Mecánico</th>
                    <th>Estado</th>
                    <th>Comentario</th>
                    <th>Acciones</th>
                </tr>
                <?php foreach ($horarios as $hora): ?>
                    <?php
                    $hayTurnos = isset($turnosPorDiaHora[$fecha][$hora]);
                    $turnosEnHorario = $hayTurnos ? $turnosPorDiaHora[$fecha][$hora] : [];
                    $slotsDisponibles = 4 - count($turnosEnHorario);
                    ?>

                    <?php if ($hayTurnos): ?>
                        <?php foreach ($turnosEnHorario as $turno): ?>
                            <tr>
                                <td><?= $hora ?></td>
                                <td><?= htmlspecialchars($turno['cliente_nombre'] ?? '') ?></td>
                                <td><?= htmlspecialchars(($turno['vehiculo_marca'] ?? '') . ' ' . ($turno['vehiculo_modelo'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($turno['servicio_nombre'] ?? '') ?></td>
                                <td><?= htmlspecialchars($turno['mecanico_nombre'] ?? '') ?></td>
                                <td><?= htmlspecialchars($turno['turno_estado']) ?></td>
                                <td><?= htmlspecialchars($turno['turno_comentario']) ?></td>
                                <td class="acciones">
                                    <form method="post" action="modificar_turno.php">
                                        <input type="hidden" name="turno_id" value="<?= $turno['turno_id'] ?>">
                                        <button type="submit">Modificar</button>
                                    </form>
                                    <form method="post" action="eliminar_turno.php" onsubmit="return confirm('¿Deseás eliminar este turno?');">
                                        <input type="hidden" name="turno_id" value="<?= $turno['turno_id'] ?>">
                                        <button type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($slotsDisponibles > 0): ?>
                        <tr>
                            <td><?= $hora ?></td>
                            <td colspan="6">Turno disponible</td>
                            <td class="acciones">
                                <form method="post" action="asignar_turno.php">
                                    <input type="hidden" name="fecha" value="<?= $fecha ?>">
                                    <input type="hidden" name="hora" value="<?= $hora ?>">
                                    <button type="submit">Asignar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </table>
        </section>
    <?php endforeach; ?>
</main>

<!-- ✅ MODAL DE CONFIRMACIÓN DE ELIMINACIÓN -->
<dialog id="modalEliminar">
    <p><strong>¿Deseás eliminar este turno?</strong></p>
    <form method="post" action="eliminar_turno.php">
        <input type="hidden" name="turno_id" id="turno_id_eliminar">
        <button type="submit" class="btn-ok">Sí, eliminar</button>
        <button type="button" class="btn-cancelar" onclick="document.getElementById('modalEliminar').close();">Cancelar</button>
    </form>
</dialog>

<script>
function abrirModalEliminar(turnoId) {
    document.getElementById('turno_id_eliminar').value = turnoId;
    document.getElementById('modalEliminar').showModal();
}
</script>

</body>
</html>

