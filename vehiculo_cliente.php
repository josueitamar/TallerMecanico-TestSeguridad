<?php
session_start();
require_once 'conexion_base.php';
require_once 'verificar_sesion_cliente.php';

$dni = $_SESSION['cliente_dni'];

// Obtener vehículos del cliente
$stmt = $conexion->prepare("SELECT * FROM vehiculos WHERE cliente_DNI = :dni");
$stmt->execute(['dni' => $dni]);
$vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$sinVehiculos = count($vehiculos) === 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
    <title>Mis Vehículos</title>
</head>
<body>
    <?php include("nav_cli.php"); ?>
    <br>
    <section class="veh_cli">
        <br>
            <h2>Mis Vehículos</h2>

        <section  >
            <?php if ($sinVehiculos): ?>
                <dialog open>
                    <p><strong>No tiene vehículos registrados.</strong></p>
                    <form method="get" action="modificacion_cliente.php">
                        <button type="submit">Volver</button>
                    </form>
                    <form method="get" action="registro_vehiculo.php">
                        <button type="submit">Registrar Vehículo</button>
                    </form>
                </dialog>
            <?php else: ?>
            <br>
            
                <table class="vehcli_tab">
                    <thead>
                        <tr>
                            <th>PATENTE</th>
                            <th>MARCA</th>
                            <th>MODELO</th>
                            <th>AÑO</th>
                            <th>KILOMETRAJE</th>
                            <th>HISTORICO</th>
                        </tr>
                    </thead>
                    <?php foreach ($vehiculos as $vehiculo): ?>
                        <?php
                        // Último kilometraje del vehículo
                        $stmtKm = $conexion->prepare(" SELECT orden_kilometros 
                            FROM orden_trabajo ot
                            JOIN ordenes o ON ot.orden_numero = o.orden_numero
                            WHERE o.vehiculo_patente = :patente
                            ORDER BY o.orden_fecha DESC 
                            LIMIT 1
                        ");
                        $stmtKm->execute(['patente' => $vehiculo['vehiculo_patente']]);
                        $km = $stmtKm->fetchColumn();
                        ?>
                        <tr>
                        <!-- <tr class="resu_vehcli"> -->
                            <td><?= htmlspecialchars($vehiculo['vehiculo_patente']) ?></td>
                            <td><?= htmlspecialchars($vehiculo['vehiculo_marca']) ?></td>
                            <td><?= htmlspecialchars($vehiculo['vehiculo_modelo']) ?></td>
                            <td><?= htmlspecialchars($vehiculo['vehiculo_anio']) ?></td>
                            <td><?= $km ? htmlspecialchars($km) . ' km' : 'Sin datos' ?></td>
                            <td>
                                <a href="historico_vehiculo_cliente.php?patente=<?= urlencode($vehiculo['vehiculo_patente']) ?>" 
                                class="boton_historial" title="Ver histórico"> 🔍
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <br>
        </section>
                <div style="text-align:center;">
                    <a href="modificacion_cliente.php" class="solicitar_mod">Volver</a>
                </div>
            <?php endif; ?>

    </section>
    <br>
    <?php include("piedepagina.php"); ?>
    <script src="control_inactividad.js"></script>
</body>
</html>
