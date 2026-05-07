<?php
require_once 'conexion_base.php';

$mensajeRegistro = "";
$modalEmpleadoRegistrado = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['registrar_empleado'])) {
    try {
        $dni = $_POST['dni_empleado'];
        $nombre = $_POST['nombre_empleado'];
        $direccion = $_POST['direcc_empleado'];
        $localidad = $_POST['loc_empleado'];
        $telefono = $_POST['tel_empleado'];
        $email = $_POST['email_empleado'];
        $rol = $_POST['rol_empleado'];
        $clave_original = $_POST['clave_empleado'];
        $clave = password_hash($clave_original, PASSWORD_DEFAULT);

        // Verificar si el DNI ya existe
        $check = $conexion->prepare("SELECT * FROM empleados WHERE empleado_DNI = :dni");
        $check->execute(['dni' => $dni]);

        if ($check->rowCount() > 0) {
            $mensajeRegistro = "Ya existe un empleado con ese DNI.";
        } else {
            $insert = $conexion->prepare("INSERT INTO empleados (empleado_DNI, empleado_nombre, empleado_direccion, empleado_localidad, empleado_telefono,empleado_email, empleado_roll, empleado_contrasena)
                VALUES (:dni, :nombre, :direccion, :localidad, :telefono, :email, :rol, :clave)");

            $insert->execute([
                'dni' => $dni,
                'nombre' => $nombre,
                'direccion' => $direccion,
                'localidad' => $localidad,
                'telefono' => $telefono,
                'email' => $email,
                'rol' => $rol,
                'clave' => $clave
            ]);

            $modalEmpleadoRegistrado = true;
            goto fin;
        }

    } catch (PDOException $e) {
        $mensajeRegistro = "Error de conexión: " . $e->getMessage();
    }
}
fin:
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro Empleado</title>
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
</head>

<!-- MODAL REGISTRO EXISTOSO -->
<?php if ($modalEmpleadoRegistrado): ?>
<dialog open id="modal_empleado_registrado">
    <p style="text-align:center;"><strong>Empleado registrado con éxito</strong></p>
    <div style="text-align:center;">
        <form method="get" action="login.php">
            <button type="submit">Ir a login</button>
        </form>
    </div>
</dialog>
<?php endif; ?>

<body>
    <?php include("navegador.php"); ?>

    <br><br>

    <section class="pagina_registro">
        <img src="fondos/registro_cli.jpg" alt="" class="img_registro1">
        
        <section class="registro">
            <img class="logo_registro" src="iconos/form_empleados.png" alt="">
            <h3>Registro de Empleados</h3>

            <form method="post" class="form_registro">
                <label for="dni_empleado">DNI</label>
                <br>
                <input type="text" name="dni_empleado" id="dni_empleado" placeholder="Ej: 12345678" required>
                <br><br>
                <label for="nombre_empleado">Nombre</label>
                <br>
                <input type="text" name="nombre_empleado" id="nombre_empleado" placeholder="Ej: Ana Pérez" required>
                <br><br>
                <label for="direcc_empleado">Direccion</label>
                <br>
                <input type="text" name="direcc_empleado" id="direcc_empleado" placeholder="Ej: Calle Falsa 123">
                <br><br>
                <label for="loc_empleado"> Localidad</label>
                <br>
                <input type="text" name="loc_empleado" id="loc_empleado" placeholder="Ej: CABA">
                <br><br>
                <label for="tel_empleado">Telefono</label>
                <br>
                <input type="tel" name="tel_empleado" id="tel_empleado"placeholder="Ej: 1132658965">
                <br><br>
                <label for="email_empleado">Email</label>
                <br>
                <input type="email" name="email_empleado" id="email_empleado" placeholder="Ej: ana@mail.com" required>
                <br><br>
                <label for="rol_empleado">Rol</label>
                <br>
                <select name="rol_empleado" id="rol_empleado" required>
                    <option value="">Seleccione un rol</option>
                    <option value="recepcionista">Recepcionista</option>
                    <option value="mecanico">Mecánico</option>
                </select>
                <br><br>
                <label for="clave_empleado">Contraseña</label>
                <br>
                <input type="password" name="clave_empleado" id="clave_empleado"
                    placeholder="Ingrese una contraseña segura"
                    minlength="8" maxlength="15"
                    pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$"
                    title="Debe tener entre 8 y 15 caracteres, al menos una mayúscula, un número y un símbolo"
                    required>

                <div class="bot_registro">
                    <button class="boton_registro" type="submit" name="registrar_empleado">Registrar</button>
                </div>
            </form>

            <?php if ($mensajeRegistro): ?>
                <br>
                <p style="color:red; text-align:center; font-weight:bold;"><?= $mensajeRegistro ?></p>
            <?php endif; ?>
        </section>

        <img src="fondos/registro_cli.jpg" alt="" class="img_registro2">
    </section>

    <br><br>
    <?php include("piedepagina.php"); ?>
    <script src="control_inactividad.js"></script>
</body>
</html>

