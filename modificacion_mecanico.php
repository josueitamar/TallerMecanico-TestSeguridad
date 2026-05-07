<?php
session_start();

require_once 'conexion_base.php';
require_once 'verificar_sesion_empleado.php';

// VARIABLES DE MODAL
$modalGuardadoExito = false;

$mensaje = "";

$dni = $_SESSION['empleado_dni'];

//OBTENER LOS DATOS DEL CLIENTE
$stmt = $conexion->prepare("SELECT * FROM empleados WHERE empleado_DNI = :dni");
$stmt->execute(['dni' => $dni]);
$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

// GUARDAR CAMBIOS
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['guardar_rec'])) {
    $direccion = $_POST['direccion'];
    $localidad = $_POST['localidad'];
    $telefono  = $_POST['telefono'];
    $clave     = $_POST['clave'];
    
    // GUARDAR CONTRASEÑA SI NO HUBO CAMBIOS
    if (!empty($clave)) {
        $claveHasheada = password_hash($clave, PASSWORD_DEFAULT);
    } else {
        $claveHasheada = $empleado['empleado_contrasena'];
    }

    $update = $conexion->prepare("UPDATE empleados SET empleado_direccion = :dir, empleado_localidad = :loc, empleado_telefono = :tel, empleado_contrasena = :clave WHERE empleado_DNI = :dni");
    $update->execute([
        'dir'   => $direccion,
        'loc'   => $localidad,
        'tel'   => $telefono,
        'clave' => $claveHasheada,
        'dni'   => $dni
    ]);

    $modalGuardadoExito = true;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
    <title>Modificar Empleado</title>
</head>
<body>
    <?php include("nav_mecanico.php"); ?>
    <br>
    <section class="modif_mec">
        <img class="modif_img1" src="fondos/hola.usuario.jpg" alt="">

        <section class="modificar_mecanico">
            <h2>Hola <?= htmlspecialchars($empleado['empleado_nombre']) ?></h2>
            <h3>Modificar Datos</h3>
            <form method="post">
                <table>
                    <tr>
                        <th>DNI</th>
                        <td class="datos_unicos"><?= htmlspecialchars($empleado['empleado_DNI']) ?></td>
                    </tr>
                    <tr>
                        <th>NOMBRE</th>
                        <td class="datos_unicos"><?= htmlspecialchars($empleado['empleado_nombre']) ?></td>
                    </tr>
                    <tr>
                        <th>E-Mail</th>
                        <td class="datos_unicos"><?= htmlspecialchars($empleado['empleado_email']) ?></td>
                    </tr>
                    <tr>
                        <th>Dirección</th>
                        <td><input type="text" class="datos_modificados" name="direccion" value="<?= htmlspecialchars($empleado['empleado_direccion']) ?>"></td>
                    </tr>
                    <tr>
                        <th>Localidad</th>
                        <td><input type="text" class="datos_modificados" name="localidad" value="<?= htmlspecialchars($empleado['empleado_localidad']) ?>"></td>
                    </tr>
                    <tr>
                        <th>Teléfono</th>
                        <td><input type="text" class="datos_modificados" name="telefono" value="<?= htmlspecialchars($empleado['empleado_telefono']) ?>"></td>
                    </tr>
                    <tr>
                        <th>Contraseña</th>
                        <td>
                            <input type="password" class="datos_modificados" name="clave"
                                minlength="8" maxlength="15"
                                pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$"
                                title="Debe tener entre 8 y 15 caracteres, al menos una mayúscula, un número y un símbolo"
                                placeholder="********">
                        </td>
                    </tr>
                </table>
                <div class="bot_modf">
                    <input class="guardar_mod" type="submit" value="Guardar" name="guardar_rec">                   
                </div>
            </form>
        </section>

        <img class="modif_img2" src="fondos/hola.usuario.jpg" alt="">
    </section>

    <!-- MODAL DATOS GUARDADOS -->
    <?php if ($modalGuardadoExito): ?>
    <dialog open>
        <p><strong>Datos guardados con éxito.</strong></p>
        <form method="get" action="mecanico.php">
            <button type="submit">Aceptar</button>
        </form>
    </dialog>
    <?php endif; ?>
    <br>
    <?php include("piedepagina.php"); ?>
    <script src="control_inactividad.js"></script>
</body>
</html>
