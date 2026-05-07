<?php
include 'conexion_base.php';
if (isset($_GET['id'])) {
    $stmt = $conexion->prepare("UPDATE turnos SET turno_estado = 'confirmado' WHERE turno_id = ?");
    $stmt->execute([$_GET['id']]);
    echo "Turno confirmado correctamente.";
}
?>

