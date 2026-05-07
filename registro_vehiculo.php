<?php
session_start();
require_once 'conexion_base.php';
require_once 'verificar_sesion_cliente.php';

$modalVehiculoGuardado = false;

$dni = $_SESSION['cliente_dni'];

//OBTENER LOS DATOS DEL CLIENTE
$stmt = $conexion->prepare("SELECT cliente_nombre FROM clientes WHERE cliente_DNI = :dni");
$stmt->execute(['dni' => $dni]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['guardar_vehiculo'])) {
    $patente = strtoupper(trim($_POST['patente']));
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $anio = $_POST['anio'];
    $dni_cliente = $_SESSION['cliente_dni'];

    $insert = $conexion->prepare("INSERT INTO vehiculos (vehiculo_patente, vehiculo_marca, vehiculo_modelo, vehiculo_anio, cliente_DNI)
                                  VALUES (:pat, :marca, :modelo, :anio, :dni)");
    $insert->execute([
        'pat'   => $patente,
        'marca' => $marca,
        'modelo'=> $modelo,
        'anio'  => $anio,
        'dni'   => $dni_cliente
    ]);

    $modalVehiculoGuardado = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
    <title>Registrar Vehículo</title>
</head>
<body>
    <?php include("nav_cli.php"); ?>
    <br>
    <section class="reg_veh_cli">
        <img class="modif_img1" src="fondos/reg_vehiculo.jpg" alt="">

        <section class="reg_vehcli_form">
            <h2><?= htmlspecialchars($cliente['cliente_nombre']) ?></h2>
            <h3>Registrar Nuevo Vehículo</h3>
            <form method="post">
                <table>
                    <tr>
                        <th>Patente</th>
                        <td><input type="text" class="datos_modificados" name="patente" required maxlength="10"></td>
                    </tr>
                    <tr>
                        <th>Marca</th>
                        <td><input type="text" class="datos_modificados" name="marca" required></td>
                    </tr>
                    <tr>
                        <th>Modelo</th>
                        <td><input type="text" class="datos_modificados" name="modelo" required></td>
                    </tr>
                    <tr>
                        <th>Año</th>
                        <td><input type="number" class="datos_modificados" name="anio" required min="1900" max="<?= date('Y') ?>"></td>
                    </tr>                
                </table>
                <div class="bot_modf">
                    <input class="solicitar_mod" type="submit" name="guardar_vehiculo" value="Registrar Vehículo">
                    <a class="solicitar_mod" href="http://localhost/tallermecanico/modificacion_cliente.php">Volver</a>
                </div>
            </form>
        </section>

        <img class="modif_img2" src="fondos/reg_vehiculo.jpg" alt="">
    </section>

    <!-- MODAL VEHICULO GUARDADO -->
    <?php if ($modalVehiculoGuardado): ?>
    <dialog open>
        <p><strong>Vehículo registrado con éxito.</strong></p>
        <form method="get" action="vehiculo_cliente.php">
            <button type="submit">Aceptar</button>
        </form>
    </dialog>
    <?php endif; ?>

    <br>
    <?php include("piedepagina.php"); ?>
    <script src="control_inactividad.js"></script>
</body>
</html>
