<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['empleado_dni'])) { header("Location: login.php"); exit; }

require_once 'conexion_base.php';

$ids = isset($_POST['ids']) ? array_filter((array)$_POST['ids'], 'is_numeric') : [];
if (empty($ids)) {
    echo "<p>No seleccionaste productos.</p><p><a href='productos.php'>Volver</a></p>";
    exit;
}

if (!isset($_POST['paso']) || $_POST['paso'] === 'confirmar') {
    ?>
    <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Eliminar productos</title></head><body>
    <h3>Eliminar (lógico) <?= count($ids) ?> producto(s)</h3>
    <p>Esto marcará los productos como <strong>No disponibles</strong>. No se borrarán de la base.</p>
    <form method="post" action="eliminar_productos.php">
        <?php foreach ($ids as $id): ?>
            <input type="hidden" name="ids[]" value="<?= (int)$id ?>">
        <?php endforeach; ?>
        <button type="submit" name="paso" value="aplicar">Sí, eliminar</button>
        <a href="productos.php">No, cancelar</a>
    </form>
    </body></html>
    <?php
    exit;
}

if ($_POST['paso'] === 'aplicar') {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "UPDATE productos SET prod_disponible = 0 WHERE prod_id IN ($placeholders)";
    $stmt = $conexion->prepare($sql);
    $stmt->execute(array_map('intval', $ids));

    header("Location: productos.php?msg=eliminar_ok");
    exit;
}

header("Location: productos.php");

