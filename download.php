<?php
// download.php?name=Factura_B_00000012_Orden_14.pdf
$name = $_GET['name'] ?? '';
if ($name === '' || preg_match('/[\/\\\\]/', $name)) {
  http_response_code(400);
  exit('Nombre de archivo inválido.');
}

$path = __DIR__ . DIRECTORY_SEPARATOR . 'facturas' . DIRECTORY_SEPARATOR . $name;
if (!is_file($path)) {
  http_response_code(404);
  exit('Archivo no encontrado.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.$name.'"');
header('Content-Length: '.filesize($path));
readfile($path);
exit;

