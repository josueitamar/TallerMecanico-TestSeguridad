<?php
require_once 'conexion_base.php';

$mensaje = "";
$modalClaveRestablecida = false;

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["token"])) {
    $token = $_GET["token"];

    try {
        $stmt = $conexion->prepare("SELECT * FROM empleados WHERE token_recuperacion = :token");
        $stmt->execute(['token' => $token]);

        if ($stmt->rowCount() !== 1) {
            $mensaje = "Token inválido o expirado.";
        } else {
            $empleado = $stmt->fetch();
        }
    } catch (PDOException $e) {
        $mensaje = "Error de conexión: " . $e->getMessage();
    }
}

// GUARDAR NUEVA CONTRASEÑA
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["token"]) && isset($_POST["nueva_contrasena"])) {
    $token = $_POST["token"];
    $nueva = $_POST["nueva_contrasena"];

    if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/", $nueva)) {
        $mensaje = "La contraseña no cumple los requisitos mínimos.";
    } else {
        try {
            $nueva_codificada = password_hash($nueva, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE empleados SET empleado_contrasena = :clave, token_recuperacion = NULL WHERE token_recuperacion = :token");
            $stmt->execute(['clave' => $nueva_codificada, 'token' => $token]);

            $modalClaveRestablecida = true;
            goto fin;
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar la contraseña: " . $e->getMessage();
        }
    }
}
fin:
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña - Empleado</title>
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
</head>

<!-- MODAL CONTRASEÑA RESTABLECIDA -->
<?php if ($modalClaveRestablecida): ?>
<dialog open id="modal_clave_ok">
    <p style="text-align:center;"><strong>Contraseña restablecida con éxito</strong></p>
    <div style="text-align:center;">
        <form method="get" action="login.php">
            <button type="submit">Ir a login</button>
        </form>
    </div>
</dialog>
<?php endif; ?>

<body>
<?php include("navegador.php"); ?>

<section class="pagina_registro">
    <section class="registro">
        <h3>Restablecer Contraseña de Empleado</h3>

        <?php if (!empty($mensaje)): ?>
            <p style="color:red; font-weight:bold; text-align:center;"><?= $mensaje ?></p>
        <?php elseif (isset($empleado)): ?>
            <form method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <label for="nueva_contrasena">Nueva Contraseña</label><br><br>
                <input type="password" id="nueva_contrasena" name="nueva_contrasena"
                       placeholder="Ej: Abc123!@" required
                       pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$"
                       title="Debe tener entre 8 y 15 caracteres, una mayúscula, un número y un símbolo."><br><br>

                <button type="submit" class="boton_registro">Guardar nueva contraseña</button>
            </form>
        <?php else: ?>
            <p style="text-align:center;">Enlace inválido o ya utilizado.</p>
        <?php endif; ?>
    </section>
</section>

<?php include("piedepagina.php"); ?>
</body>
</html>

