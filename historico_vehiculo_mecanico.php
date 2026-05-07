<?php
require_once("conexion_base.php");
require_once 'verificar_sesion_empleado.php';


$mostrarModalError = false;
$mensajeModal = "";
$datosVehiculo = [];
$ordenesVehiculo = [];

try {
    $patente = strtoupper(trim($_GET['patente'] ?? ''));

    if (empty($patente)) {
        $mostrarModalError = true;
        $mensajeModal = "No se proporcionó una patente.";
    } else {
        // VERIFICAR QUE EL VEHICULO EXISTE
        $stmt = $conexion->prepare("SELECT * FROM vehiculos WHERE vehiculo_patente = :patente");
        $stmt->execute(['patente' => $patente]);

        if ($stmt->rowCount() === 0) {
            $mostrarModalError = true;
            $mensajeModal = "Vehículo no registrado.";
        } else {
            $datosVehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

            // OBTENER HISTORIAL DE ORDENES
            $stmt = $conexion->prepare("SELECT o.orden_numero, o.orden_fecha, s.servicio_nombre, ot.orden_estado, ot.servicio_codigo, 
                       ot.orden_kilometros, ot.complejidad, ot.orden_comentario
                FROM ordenes o
                JOIN orden_trabajo ot ON o.orden_numero = ot.orden_numero
                JOIN servicios s ON ot.servicio_codigo = s.servicio_codigo
                WHERE o.vehiculo_patente = :patente
                ORDER BY o.orden_numero
            ");
            $stmt->execute(['patente' => $patente]);
            $ordenesVehiculo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    $mostrarModalError = true;
    $mensajeModal = "Error en la base de datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial del Vehículo</title>
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
</head>
<body>

    <?php include("nav_mecanico.php"); ?>
    <br>

    <!-- MODAL ERROR -->
    <?php if ($mostrarModalError): ?>
    <dialog open id="modal_error_historial">
        <p style="text-align:center;"><strong><?= htmlspecialchars($mensajeModal) ?></strong></p>
        <div style="text-align:center;">
            <button onclick="document.getElementById('modal_error_historial').close()">Cerrar</button>
        </div>
    </dialog>
    <?php elseif (!empty($datosVehiculo)): ?>
        <section class="his_vehiculo_mec">
            <h2 >
                <?= htmlspecialchars($datosVehiculo['vehiculo_patente']) ?> - 
                <?= htmlspecialchars($datosVehiculo['vehiculo_marca']) ?> - 
                <?= htmlspecialchars($datosVehiculo['vehiculo_modelo']) ?> - 
                <?= htmlspecialchars($datosVehiculo['vehiculo_anio']) ?>
            </h2>

            <table class="his_vehimec_tab" style="width: 95%;">
                <thead>
                    <tr>
                        <th>Orden Nº</th>
                        <th>Fecha</th>
                        <th>Servicio</th>
                        <th>Estado</th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordenesVehiculo as $orden): ?>
                    <tr>
                        <td><?= htmlspecialchars($orden['orden_numero']) ?></td>
                        <td><?= htmlspecialchars($orden['orden_fecha']) ?></td>
                        <td><?= htmlspecialchars($orden['servicio_nombre']) ?></td>
                        <td><?= $orden['orden_estado'] == 1 ? "Finalizada" : "Pendiente" ?></td>
                        <td style="text-align: center;">
                            <button onclick="document.getElementById('modal_<?= $orden['orden_numero'] ?>').showModal()">🔍</button>
                            <button>
                            <a href="exportar_pdf_orden.php?orden=<?= $orden['orden_numero'] ?>" target="_blank" class="boton_historial">                                🖨️
                            </a></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- MODALES DE CADA ORDEN -->
        <?php foreach ($ordenesVehiculo as $orden): ?>
        <dialog id="modal_<?= $orden['orden_numero'] ?>">
            <h3 style="text-align: center;">Orden Nº <?= htmlspecialchars($orden['orden_numero']) ?></h3>
            <p><strong>Servicio:</strong> <?= htmlspecialchars($orden['servicio_nombre']) ?></p>
            <p><strong>Kilometraje:</strong> <?= htmlspecialchars($orden['orden_kilometros']) ?> km</p>
            <p><strong>Complejidad:</strong> <?= htmlspecialchars($orden['complejidad']) ?></p>
            <p><strong>Comentario:</strong> <?= htmlspecialchars($orden['orden_comentario']) ?></p>
            <div style="text-align:center;">
                <button onclick="document.getElementById('modal_<?= $orden['orden_numero'] ?>').close()">Cerrar</button>
            </div>
        </dialog>
        <?php endforeach; ?>
    <?php endif; ?>
    <br>
    <?php include("piedepagina.php"); ?>
    <script src="control_inactividad.js"></script>
</body>
</html>
