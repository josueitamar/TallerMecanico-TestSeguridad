<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexion_base.php';
$mensajeErrorCliente = "";
$mensajeErrorEmpleado = "";

// LOGIN CLIENTE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login_cliente'])) {
    try {
        $dni = $_POST['dni'];
        $claveIngresada = $_POST['clave'];

        $sql = "SELECT * FROM clientes WHERE cliente_DNI = :dni";
        $stmt = $conexion->prepare($sql);
        $stmt->execute(['dni' => $dni]);

        if ($stmt->rowCount() === 1) {
            $cliente = $stmt->fetch();

            if (password_verify($claveIngresada, $cliente['cliente_contrasena'])) {
                $_SESSION['cliente_dni'] = $cliente['cliente_DNI'];
                $_SESSION['cliente_nombre'] = $cliente['cliente_nombre'];
                header("Location: modificacion_cliente.php");
                exit();
            } else {
                $mensajeErrorCliente = "Contraseña incorrecta (Cliente).";
            }
        } else {
            $mensajeErrorCliente = "DNI no registrado (Cliente).";
        }

    } catch (PDOException $e) {
        $mensajeErrorCliente = "Error de conexión: " . $e->getMessage();
    }
}

// LOGIN EMPLEADO
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login_empleado'])) {
    try {
        $dni_emp = $_POST['dni_empleado'];
        $claveIngresada = $_POST['clave_empleado'];

        $sql = "SELECT * FROM empleados WHERE empleado_DNI = :dni";
        $stmt = $conexion->prepare($sql);
        $stmt->execute(['dni' => $dni_emp]);

        if ($stmt->rowCount() === 1) {
            $empleado = $stmt->fetch();

            if (password_verify($claveIngresada, $empleado['empleado_contrasena'])) {
                $_SESSION['empleado_dni'] = $empleado['empleado_DNI'];
                $_SESSION['empleado_nombre'] = $empleado['empleado_nombre'];
                $_SESSION['empleado_rol'] = $empleado['empleado_roll'];

                if ($empleado['empleado_roll'] === 'recepcionista') {
                    header("Location: recepcionista.php");
                } elseif ($empleado['empleado_roll'] === 'mecanico') {
                    header("Location: mecanico.php");
                } elseif ($empleado['empleado_roll'] === 'gerente') {
                    header("Location: gerente.php");
                } else {
                    $mensajeErrorEmpleado = "Rol no reconocido.";
                }
                exit();
            } else {
                $mensajeErrorEmpleado = "Contraseña incorrecta (Empleado).";
            }
        } else {
            $mensajeErrorEmpleado = "DNI no registrado (Empleado).";
        }

    } catch (PDOException $e) {
        $mensajeErrorEmpleado = "Error de conexión: " . $e->getMessage();
    }
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
<body>
    <?php 
        include("navegador.php");
    ?>
    
    <section class="fondo_login"> 
        <div class="formu_login">
            <!-- FORMULARIO LOGIN EMPLEADO -->
            <div class="formu_empleados"> 
                <img src="iconos/form_empleados.png" alt="">
                <h2>EMPLEADOS</h2>
                <br> <br>
                <form action="" method="post">
                    <label for="us_empleado">Usuario</label>
                    <br><br>
                    <input type="text" id="us_empleado" name="dni_empleado" placeholder="Ingrese su usuario" required>
                    <br><br><br><br>
                    <label for="password_empleado">Contraseña</label>
                    <br><br>
                    <input type="password" id="password_empleado" name="clave_empleado" placeholder="Ingrese su clave" required>
                    <br><br>
                    <button class="ingreso1" type="submit" name="login_empleado">Ingresar</button>
                    <br><br>
                </form>

                <!-- FORMULARIO OLVIDÉ MI CONTRASEÑA EMPLEADO -->
                <form action="olvido_contrasena_empleado.php" method="post" onsubmit="return validarDNIEmpleado()" style="display:inline;">
                    <input type="hidden" name="dni_empleado" id="dni_empleado_hidden">
                    <button class="olvido" type="submit">Olvidé mi contraseña</button>
                </form>

                <?php if (!empty($mensajeErrorEmpleado)): ?>
                    <p style="color:red; text-align:center; font-weight:bold;"><?= $mensajeErrorEmpleado ?></p>
                <?php endif; ?>
            </div>

            <!-- SCRIPT PARA TOMAR EL DNI DEL INPUT EMPLEADO-->
            <script>
            function validarDNIEmpleado() {
                const dni = document.getElementById("us_empleado").value;
                if (!dni) {
                    alert("Debe ingresar su DNI para recuperar la contraseña.");
                    return false;
                }
                document.getElementById("dni_empleado_hidden").value = dni;
                return true;
            }
            </script>

            <!-- FORMULARIO LOGIN CLIENTE -->
            <div class="formu_clientes"> 
                <img src="iconos/form_clientes.png" alt="">
                <h2>CLIENTES</h2>
                <br><br>
                <form action="" class="clientes" method="post">
                    <label for="us">Usuario</label>
                    <br><br>
                    <input type="text" id="us" name="dni" placeholder="Ingrese su usuario" required>
                    <br><br><br><br>
                    <label for="password">Contraseña</label>
                    <br><br>
                    <input type="password" id="password" name="clave" placeholder="Ingrese su clave" required>
                    <br><br><br><br>
                    <button class="ingreso2" name="login_cliente" type="submit">Ingresar</button>
                </form>

                <!-- FORMULARIO PARA OLVIDÉ MI CONTRASEÑA -->
                <form action="olvido_contrasena.php" method="post" onsubmit="return validarDNI()" style="display:inline;">
                    <input type="hidden" name="dni" id="dni_hidden">
                    <button class="olvido" type="submit">Olvidé mi contraseña</button>
                </form>

                <!-- BOTÓN DE REGISTRO -->
                <br><br>
                <a class="registro2" href="registro_cliente.php">Registrarme</a>

                <?php if (!empty($mensajeErrorCliente)): ?>
                    <p style="color:red; text-align:center; font-weight:bold;"><?= $mensajeErrorCliente ?></p>
                <?php endif; ?>
            </div>

            <!-- SCRIPT PARA TOMAR EL DNI DEL INPUT CLIENTE-->
            <script>
            function validarDNI() {
                const dni = document.getElementById("us").value;
                if (!dni) {
                    alert("Debe ingresar su DNI para recuperar la contraseña.");
                    return false;
                }
                document.getElementById("dni_hidden").value = dni;
                return true;
            }
            </script>
     </section>


    <?php 
        include("piedepagina.php");
    ?>


</body>
</html>
