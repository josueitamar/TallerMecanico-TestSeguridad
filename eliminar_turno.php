<?php
include 'conexion_base.php';
include 'verificar_sesion_empleado.php';

// Crear log de la eliminacion de los turnos
function registrar_log_eliminacion($mensaje) {
    $log = "[" . date('Y-m-d H:i:s') . "] " . $mensaje . PHP_EOL;
    file_put_contents('log_turnos.txt', $log, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['turno_id'])) {
    $turno_id = $_POST['turno_id'];
    $empleado_dni = $_SESSION['empleado_dni'] ?? 'Desconocido';

    try {
        // Iniciar transacción
        $conexion->beginTransaction();

        // Obtener orden vinculada antes de borrar
        $stmtOrden = $conexion->prepare("SELECT orden_numero FROM orden_trabajo WHERE turno_id = :turno_id");
        $stmtOrden->execute([':turno_id' => $turno_id]);
        $orden = $stmtOrden->fetch(PDO::FETCH_ASSOC);    

        // Eliminar de orden_trabajo (si existe)
        $stmt1 = $conexion->prepare("DELETE FROM orden_trabajo WHERE turno_id = :turno_id");
        $stmt1->execute([':turno_id' => $turno_id]);

        // Eliminar orden si existe
        if ($orden) {
            $stmt2 = $conexion->prepare("DELETE FROM ordenes WHERE orden_numero = :orden_numero");
            $stmt2->execute([':orden_numero' => $orden['orden_numero']]);
        }

        // Eliminar el turno
        $stmt3 = $conexion->prepare("DELETE FROM turnos WHERE turno_id = :turno_id");
        $stmt3->execute([':turno_id' => $turno_id]);

        // Confirmar transacción
        $conexion->commit();

        registrar_log_eliminacion("Empleado $empleado_dni eliminó el turno ID $turno_id. Resultado: Eliminado con éxito.");
        header("Location: turnos.php?mensaje=ok");
        exit();

    } catch (PDOException $e) {
        $conexion->rollBack();
        error_log("Error al eliminar turno: " . $e->getMessage());
        header("Location: turnos.php?error=1");
        exit();
    }
} else {
    header("Location: turnos.php?mensaje=error");
    exit();
}
?>
