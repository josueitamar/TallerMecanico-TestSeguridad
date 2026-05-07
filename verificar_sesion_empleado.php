<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['empleado_dni'])) {
    header("Location: login_empleado.php");
    exit();
}
?>
