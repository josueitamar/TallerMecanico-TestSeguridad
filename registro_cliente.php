<?php
require 'conexion_base.php';

$mensajeRegistro = "";
$modalRegistroExitoso = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['registrar_cliente'])) {
    try {
        $dni = $_POST['dni_registro'];
        $nombre = $_POST['nombre_registro'];
        $direccion = $_POST['direcc_registro'];
        $localidad = $_POST['loc_registro'];
        $telefono = $_POST['tel_registro'];
        $email = $_POST['email_registro'];
        $clave_original = $_POST['contra_registro'];
        $clave = password_hash($clave_original, PASSWORD_DEFAULT);

        // Verificar si el DNI ya existe
        $check = $conexion->prepare("SELECT * FROM clientes WHERE cliente_DNI = :dni");
        $check->execute(['dni' => $dni]);

        if ($check->rowCount() > 0) {
            $mensajeRegistro = "Ya existe un cliente registrado con ese DNI.";
        } else {
            $insert = $conexion->prepare("INSERT INTO clientes (cliente_DNI, cliente_nombre, cliente_direccion, cliente_localidad, cliente_telefono, cliente_email, cliente_contrasena)
                VALUES (:dni, :nombre, :direccion, :localidad, :telefono, :correo, :clave)");

            $insert->execute([
                'dni' => $dni,
                'nombre' => $nombre,
                'direccion' => $direccion,
                'localidad' => $localidad,
                'telefono' => $telefono,
                'correo' => $email,
                'clave' => $clave
            ]);

            $modalRegistroExitoso = true;
            goto fin;
        }

    } catch (PDOException $e) {
        $mensajeRegistro = "Error de conexión: " . $e->getMessage();
    }
}
fin:
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>" > 
    <title>Document</title>
</head>

<!-- MODAL REGISTRO EXISTOSO -->
<?php if ($modalRegistroExitoso): ?>
<dialog open id="modal_registro_exitoso">
    <p style="text-align:center;"><strong>Registro exitoso</strong></p>
    <p style="text-align:center;">Ya puede iniciar sesión.</p>
    <div style="text-align:center;">
        <form method="get" action="login.php">
            <button type="submit">Iniciar sesión</button>
        </form>
    </div>
</dialog>
<?php endif; ?>



<body>
    <?php 
        include("navegador.php");
    ?>

    <br><br>


    <section class="pagina_registro">
        <img src="fondos/registro_cli.jpg" alt="" class="img_registro1">
        <section class="registro">
            
            <img class="logo_registro" src="iconos/form_clientes.png" alt="">
            <h3>Ingrese sus datos</h3>

            <form action="" class="form_registro" method="post" >
                <label for="dni_registro" class="" > DNI</label>
                <br><br>
                <input type="text" name="dni_registro" id="dni_registro" class="" placeholder="Ej: 23456879">
                <br><br>
                <label for="nombre_registro" class="" >Nombre</label>
                <br><br>
                <input type="text" name="nombre_registro" id="nombre_registro" class="" placeholder="Ej: Juan Perez">
                <br><br>
                <label for="direcc_registro">Direccion</label>
                <br><br>
                <input type="text" name="direcc_registro" id="direcc_registro" placeholder="Ej: Calle Falsa 123">
                <br><br>
                <label for="loc_registro"> Localidad</label>
                <br><br>
                <input type="text" name="loc_registro" id="loc_registro" placeholder="Ej: CABA">
                <br><br>
                <label for="tel_registro">Telefono</label>
                <br><br>
                <input type="tel" name="tel_registro" id="tel_registro"placeholder="Ej: 1132658965">
                <br><br>
                <label for="email_registro">E-Mail</label>
                <br><br>
                <input type="email" name="email_registro" id="email_registro" placeholder="Ej: mail@falso.com">
                <br><br>
                <label for="contra_registro">Contraseña</label>
                <br><br>
                <input type="password" name="contra_registro" id="contra_registro" placeholder="Ingrese una contraseña segura"
                    minlength="8" maxlength="15"
                    pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$"
                    title="Debe tener entre 8 y 15 caracteres, al menos una mayúscula, un número y un símbolo"
                    required>
                
            <div class="bot_registro">
                <button class="boton_registro" value="Registrar" type="submit" name="registrar_cliente" >Registrar</button>
            </div>
            </form>

            <!-- MENSAJE DE ERROR SI EXISTE EL DNI -->
                <?php if ($mensajeRegistro): ?>
                    <br>
                    <p style="color:red; text-align:center; font-weight:bold;"><?= $mensajeRegistro ?></p>
                <?php endif; ?>

        </section>
        <img src="fondos/registro_cli.jpg" alt="" class="img_registro2">

    </section>    

    <br><br>
    <?php include("piedepagina.php");?>
    <script src="control_inactividad.js"></script>
</body>
</html>
