<?php
session_start();
require_once 'verificar_sesion_empleado.php';
require_once 'conexion_base.php';

// Agenda de 7 días (sólo lunes a viernes como en tu código)
$horarios = ["08:00","09:00","10:00","11:00","12:00","13:00","14:00","15:00","16:00","17:00"];
$dias = [];
for ($i = 0; $i < 7; $i++) {
    $fecha = date('Y-m-d', strtotime("+$i day"));
    if ((int)date('N', strtotime($fecha)) < 6) { // 1..5 = lunes..viernes
        $dias[] = $fecha;
    }
}

// Traemos turnos de la semana (como tenías, pero no mostraremos acciones)
try {
    $stmt = $conexion->prepare("
        SELECT 
            t.turno_id, t.turno_fecha, t.turno_hora, t.turno_comentario, t.turno_estado,
            c.cliente_nombre,
            v.vehiculo_marca, v.vehiculo_modelo,
            e.empleado_nombre AS mecanico_nombre,
            s.servicio_nombre
        FROM turnos t
        LEFT JOIN vehiculos v ON t.vehiculo_patente = v.vehiculo_patente
        LEFT JOIN clientes  c ON t.cliente_DNI = c.cliente_DNI
        LEFT JOIN empleados e ON t.mecanico_dni = e.empleado_DNI
        LEFT JOIN orden_trabajo ot ON t.turno_id = ot.turno_id
        LEFT JOIN servicios    s ON ot.servicio_codigo = s.servicio_codigo
        WHERE t.turno_fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)
        ORDER BY t.turno_fecha ASC, t.turno_hora ASC
    ");
    $stmt->execute();
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener turnos: " . $e->getMessage());
}

// Indexamos por día y hora para pintar en grilla sin “disponibles”
$turnosPorDiaHora = [];
foreach ($turnos as $turno) {
    $fecha = $turno['turno_fecha'];
    $hora  = date('H:i', strtotime($turno['turno_hora']));
    $turnosPorDiaHora[$fecha][$hora][] = $turno;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
</head>
<body>
<?php include 'nav_gerente.php'; ?>
  <br>
  <h1 class="turnos_tit">Agenda semanal de turnos</h1>
  <main>
    <?php foreach ($dias as $fecha): ?>
      <section class="turnos">
        <br>
        <h3>Turnos para el día <?= htmlspecialchars($fecha) ?></h3>
        <br>
        <table>
          <thead>
            <tr>
              <th>Hora</th>
              <th>Cliente</th>
              <th>Vehículo</th>
              <th>Servicio</th>
              <th>Mecánico</th>
              <th>Estado</th>
              <th>Comentario</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $hayAlMenosUno = false;
              foreach ($horarios as $hora):
                $tEnHora = $turnosPorDiaHora[$fecha][$hora] ?? [];
                if (!$tEnHora) { continue; } // no mostramos “disponible”
                $hayAlMenosUno = true;
                foreach ($tEnHora as $t):
            ?>
              <tr>
                <td><?= htmlspecialchars($hora) ?></td>
                <td><?= htmlspecialchars($t['cliente_nombre'] ?? '') ?></td>
                <td><?= htmlspecialchars(($t['vehiculo_marca'] ?? '') . ' ' . ($t['vehiculo_modelo'] ?? '')) ?></td>
                <td><?= htmlspecialchars($t['servicio_nombre'] ?? '') ?></td>
                <td><?= htmlspecialchars($t['mecanico_nombre'] ?? '') ?></td>
                <td><?= htmlspecialchars($t['turno_estado'] ?? '') ?></td>
                <td><?= htmlspecialchars($t['turno_comentario'] ?? '') ?></td>
              </tr>
            <?php
                endforeach;
              endforeach;
            ?>
            <?php if (!$hayAlMenosUno): ?>
              <tr>
                <td colspan="7" class="muted">No hay turnos asignados para este día.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>
    <?php endforeach; ?>
  </main>
  <br><br>
  <?php include 'piedepagina.php'; ?>
  <script src="control_inactividad.js"></script>
</body>
</html>

