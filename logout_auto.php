?php
session_start();

$estado = trim(file_get_contents("php://input"));

if ($estado === "inactivo") {
    session_unset();
    session_destroy();
    exit;
}
?>
