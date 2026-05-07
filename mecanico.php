<?php
require 'conexion_base.php';
require_once 'verificar_sesion_empleado.php';

// DNI DEL MECANICO LOGEADO
$mecDni = $_SESSION['empleado_DNI'] ?? $_SESSION['empleado_dni'] ?? null;

// FLAGS DE MODALES DE VALIDACION
$modalVehiculoNoRegistrado      = false;
$modalOrdenNoExiste             = false;
$modalOrdenFinalizada           = false;

// ÓRDENES PENDIENTES (del mecánico logueado)
$ordenesPendientes = [];
if ($mecDni) {
    $stmtPend = $conexion->prepare("
        SELECT 
            o.orden_fecha,
            v.vehiculo_marca, v.vehiculo_modelo, v.vehiculo_anio,
            o.orden_numero,
            s.servicio_nombre
        FROM ordenes o
        JOIN orden_trabajo ot ON o.orden_numero = ot.orden_numero
        JOIN servicios s      ON s.servicio_codigo = ot.servicio_codigo
        JOIN vehiculos v      ON v.vehiculo_patente = o.vehiculo_patente
        WHERE ot.orden_estado = 0
          AND ot.mecanico_DNI = :dni
        ORDER BY o.orden_fecha DESC, o.orden_numero DESC
    ");
    $stmtPend->execute([':dni' => $mecDni]);
    $ordenesPendientes = $stmtPend->fetchAll(PDO::FETCH_ASSOC);
}

// TURNOS PENDIENTES (del mecánico logueado, desde hoy en adelante)
$turnosPendientes = [];
if ($mecDni) {
    $stmtTurn = $conexion->prepare("
        SELECT 
            t.turno_id,
            t.turno_fecha,
            t.turno_hora,
            c.cliente_nombre,
            v.vehiculo_patente, v.vehiculo_marca, v.vehiculo_modelo,
            s.servicio_nombre,
            t.turno_comentario,
            ot.orden_numero
        FROM turnos t
        LEFT JOIN clientes c       ON c.cliente_DNI      = t.cliente_DNI
        LEFT JOIN vehiculos v      ON v.vehiculo_patente = t.vehiculo_patente
        LEFT JOIN orden_trabajo ot ON ot.turno_id        = t.turno_id
        LEFT JOIN servicios s      ON s.servicio_codigo  = ot.servicio_codigo
        WHERE t.mecanico_dni = :dni
          AND t.turno_estado = 'pendiente'
          AND t.turno_fecha >= CURDATE()
        ORDER BY t.turno_fecha ASC, t.turno_hora ASC
    ");
    $stmtTurn->execute([':dni' => $mecDni]);
    $turnosPendientes = $stmtTurn->fetchAll(PDO::FETCH_ASSOC);
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['buscar_rec'])) {
        $patente = strtoupper(trim($_GET['historial'] ?? ''));
        $ordenNum = trim($_GET['trabajos_realizar'] ?? '');

        if (!empty($patente)) {
            $stmt = $conexion->prepare("SELECT * FROM vehiculos WHERE vehiculo_patente = :patente");
            $stmt->execute(['patente' => $patente]);

            if ($stmt->rowCount() > 0) {
                header("Location: historico_vehiculo_mecanico.php?patente=" . urlencode($patente));
                exit();
            } else {
                $modalVehiculoNoRegistrado = true;
            }
        } elseif (!empty($ordenNum)) {
            $stmt = $conexion->prepare("SELECT ot.orden_estado
                FROM orden_trabajo ot
                JOIN ordenes o ON o.orden_numero = ot.orden_numero
                WHERE o.orden_numero = :orden
                LIMIT 1
            ");
            $stmt->execute(['orden' => $ordenNum]);

            if ($stmt->rowCount() === 0) {
                $modalOrdenNoExiste = true;
            } else {
                $estado = $stmt->fetchColumn();
                if ($estado == 1) {
                    $modalOrdenFinalizada = true;
                } else {
                    header("Location: ordenes_pendientes.php?orden=" . urlencode($ordenNum));
                    exit();
                }
            }
        }
    }
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>"> 
    <title>Document</title>
</head>

<!-- MODAL VEHICULO NO REGISTRADO -->
<?php if ($modalVehiculoNoRegistrado): ?>
<dialog open id="modal_vehiculo_no_registrado">
    <p style="text-align:center;"><strong>Vehículo no registrado</strong></p>
    <div style="text-align:center;">
        <button onclick="document.getElementById('modal_vehiculo_no_registrado').close()">Cerrar</button>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL ORDEN NO EXISTE -->
<?php if ($modalOrdenNoExiste): ?>
<dialog open id="modal_orden_inexistente">
    <p style="text-align:center;"><strong>Orden inexistente</strong></p>
    <div style="text-align:center;">
        <button onclick="document.getElementById('modal_orden_inexistente').close()">Cerrar</button>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL ORDEN FINALIZADA -->
<?php if ($modalOrdenFinalizada): ?>
<dialog open id="modal_orden_finalizada" class= "modal_orden_finalizada">
    <p><strong>La orden ya fue finalizada</strong></p>
    <div>
        <button onclick="document.getElementById('modal_orden_finalizada').close()">Cerrar</button>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL ÓRDENES PENDIENTES -->
<dialog id="modal_ordenes_pendientes" class="modal_ordenes_pendientes">
    <h3>Órdenes Pendientes</h3>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Año</th>
                <th>N° Orden</th>
                <th>Servicio</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$ordenesPendientes): ?>
                <tr><td colspan="7" style="font-style:italic; color:#555;">No tenés órdenes pendientes.</td></tr>
            <?php else: foreach ($ordenesPendientes as $orden): ?>
                <tr>
                    <td><?= htmlspecialchars($orden['orden_fecha']) ?></td>
                    <td><?= htmlspecialchars($orden['vehiculo_marca']) ?></td>
                    <td><?= htmlspecialchars($orden['vehiculo_modelo']) ?></td>
                    <td><?= htmlspecialchars($orden['vehiculo_anio']) ?></td>
                    <td><?= htmlspecialchars($orden['orden_numero']) ?></td>
                    <td><?= htmlspecialchars($orden['servicio_nombre']) ?></td>
                    <td>
                        <a href="ordenes_pendientes.php?orden=<?= urlencode($orden['orden_numero']) ?>" title="Ver Orden">
                            🔍
                        </a>
                    </td>    
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <div style="text-align:center; margin-top:20px;">
        <button onclick="document.getElementById('modal_ordenes_pendientes').close()">Cerrar</button>
    </div>
</dialog>

<!-- MODAL TURNOS PENDIENTES -->
<dialog id="modal_turnos_pendientes" class="modal_turnos_pendientes">
    <h3>Turnos Pendientes</h3>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Cliente</th>
                <th>Vehículo</th>
                <th>Servicio</th>
                <th>Comentario</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$turnosPendientes): ?>
                <tr><td colspan="7" style="font-style:italic; color:#555;">No tenés turnos pendientes próximos.</td></tr>
            <?php else: foreach ($turnosPendientes as $turno): ?>
            <tr>
                <td><?= htmlspecialchars($turno['turno_fecha']) ?></td>
                <td><?= htmlspecialchars(substr($turno['turno_hora'],0,5)) ?></td>
                <td><?= htmlspecialchars($turno['cliente_nombre'] ?? '') ?></td>
                <!-- acá estaban los $t[...] -->
                <td><?= htmlspecialchars($turno['vehiculo_marca'].' '.$turno['vehiculo_modelo'].' ('.$turno['vehiculo_patente'].')') ?></td>
                <td><?= htmlspecialchars($turno['servicio_nombre'] ?? '-') ?></td>
                <td><?= htmlspecialchars($turno['turno_comentario'] ?? '') ?></td>
                <td>
                    <?php if (!empty($turno['orden_numero'])): ?>
                        <a href="ordenes_pendientes.php?orden=<?= urlencode($turno['orden_numero']) ?>" title="Ir a la orden">🔍</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>    
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <div style="text-align:center; margin-top:20px;">
        <button onclick="document.getElementById('modal_turnos_pendientes').close()">Cerrar</button>
    </div>
</dialog>

<body>
    <?php include("nav_mecanico.php"); ?>

    <section class="form_consulta">
        <div class="imagen_cons1">
            <img src="iconos/form_empleados.png" alt="">
        </div>
        <h2 class="consulta">Consultas</h2>
        <form action="" class="consulta">
            <div class="cons_historial">
                <br>
                <label for="historial">HISTORIAL DEL VEHICULO</label>
                <h3>Patente del vehiculo</h3>
                <input type="text" name="historial" id="historial">
            </div>
            <br>
            <div class="cons_trabajo">
                <br>
                <label for="trabajos_realizar">TRABAJOS A REALIZAR</label>
                <h3>Número de Orden</h3>
                <input type="text" name="trabajos_realizar" id="trabajos_realizar">
            </div>
            <br>
            <input class="buscar_mec" type="submit" value="Buscar" name="buscar_rec" class="form2_orden2">
        </form>
        <div class="imagen_cons2">
            <img src="iconos/form_empleados.png" alt="">
        </div>
    </section>

    <?php include("piedepagina.php");?>
    <script src="control_inactividad.js"></script>
</body>
</html>

