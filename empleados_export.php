<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['empleado_dni'])) { header("Location: login.php"); exit; }

require_once 'conexion_base.php';

// ====== Helpers ======
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function get($key){ return isset($_GET[$key]) ? trim($_GET[$key]) : ''; }
function yn($v){ return $v ? 1 : 0; }

// ====== Filtros (mismos que empleados.php) ======
$dni    = get('dni');
$nombre = get('nombre');
$email  = get('email');
$roll   = get('roll');
$estado = get('estado');
$incluir_bajas = isset($_GET['incluir_bajas']) ? 1 : 0;

$where=[]; $params=[];
if ($dni    !== '') { $where[]="empleado_DNI LIKE :dni";       $params[':dni'] = "%{$dni}%"; }
if ($nombre !== '') { $where[]="empleado_nombre LIKE :nom";     $params[':nom'] = "%".mb_strtoupper($nombre,'UTF-8')."%"; }
if ($email  !== '') { $where[]="empleado_email LIKE :mail";     $params[':mail']="%{$email}%"; }
if ($roll   !== '') { $where[]="empleado_roll = :roll";         $params[':roll']= mb_strtolower($roll,'UTF-8'); }
if ($estado !== '') { $where[]="empleado_estado = :estado";     $params[':estado']=$estado; }
if (!$incluir_bajas){ $where[]="empleado_estado <> 'baja'"; }

$sql = "SELECT empleado_DNI, empleado_nombre, empleado_roll, empleado_email,
               empleado_direccion, empleado_localidad, empleado_telefono,
               empleado_habilitado, empleado_estado, licencia_desde, licencia_hasta
        FROM empleados";
if ($where) { $sql .= " WHERE ".implode(' AND ',$where); }
$sql .= " ORDER BY empleado_nombre ASC, empleado_DNI ASC";

$st = $conexion->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// ====== Formato ======
$format = strtolower(get('format') ?: 'csv');

// ====== Export CSV (sin librerías) ======
if ($format === 'csv') {
    $filename = "empleados_".date('Ymd_His').".csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    // BOM UTF-8 para Excel
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    // Encabezados
    fputcsv($out, [
        'DNI','Nombre','Rol','Email','Direccion','Localidad','Telefono',
        'Habilitado','Estado','Licencia Desde','Licencia Hasta'
    ], ';');

    // Filas
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['empleado_DNI'],
            $r['empleado_nombre'],
            $r['empleado_roll'],
            $r['empleado_email'],
            $r['empleado_direccion'],
            $r['empleado_localidad'],
            $r['empleado_telefono'],
            $r['empleado_habilitado'] ? 'SI' : 'NO',
            $r['empleado_estado'],
            $r['licencia_desde'],
            $r['licencia_hasta'],
        ], ';');
    }
    fclose($out);
    exit;
}

// ====== Export PDF si TCPDF está disponible; si no, vista imprimible ======
$hasTcpdf = file_exists(__DIR__.'/tcpdf_min/tcpdf.php') || file_exists(__DIR__.'/tcpdf/tcpdf.php');
if ($format === 'pdf' && $hasTcpdf) {
    // Intentar require en ubicaciones comunes
    if (file_exists(__DIR__.'/tcpdf_min/tcpdf.php')) {
        require_once __DIR__.'/tcpdf_min/tcpdf.php';
    } else {
        require_once __DIR__.'/tcpdf/tcpdf.php';
    }

    // Armo HTML
    ob_start();
    ?>
    <style>
      h2 { text-align:center; margin: 0 0 8px; }
      table { width:100%; border-collapse:collapse; font-size:10pt; }
      th, td { border:1px solid #777; padding:4px; }
      th { background:#efefef; }
    </style>
    <h2>Listado de Empleados</h2>
    <table>
      <thead>
      <tr>
        <th>DNI</th><th>Nombre</th><th>Rol</th><th>Email</th>
        <th>Dirección</th><th>Localidad</th><th>Teléfono</th>
        <th>Hab.</th><th>Estado</th><th>Lic.Desde</th><th>Lic.Hasta</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['empleado_DNI']) ?></td>
          <td><?= e($r['empleado_nombre']) ?></td>
          <td><?= e($r['empleado_roll']) ?></td>
          <td><?= e($r['empleado_email']) ?></td>
          <td><?= e($r['empleado_direccion']) ?></td>
          <td><?= e($r['empleado_localidad']) ?></td>
          <td><?= e($r['empleado_telefono']) ?></td>
          <td><?= $r['empleado_habilitado'] ? 'SI' : 'NO' ?></td>
          <td><?= e($r['empleado_estado']) ?></td>
          <td><?= e($r['licencia_desde']) ?></td>
          <td><?= e($r['licencia_hasta']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php
    $html = ob_get_clean();

    // Crear PDF
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('WA SPORT');
    $pdf->SetAuthor('WA SPORT');
    $pdf->SetTitle('Empleados');
    $pdf->SetMargins(10, 12, 10);
    $pdf->SetAutoPageBreak(TRUE, 12);
    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->lastPage();

    $pdf->Output('empleados_'.date('Ymd_His').'.pdf', 'I');
    exit;
}

// ====== Fallback: Vista imprimible (si no hay TCPDF o format != pdf) ======
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Empleados – Vista imprimible</title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; }
  .actions { margin:12px 0; }
  button { padding:8px 12px; }
  h2 { text-align:center; margin: 0 0 8px; }
  table { width:100%; border-collapse:collapse; font-size:12px; }
  th, td { border:1px solid #777; padding:6px; }
  th { background:#efefef; }
  @media print {
    .actions { display:none; }
  }
</style>
</head>
<body>
<div class="actions">
  <button onclick="window.print()">Imprimir</button>
  <a href="empleados_export.php?<?= e(($incluir_bajas? 'incluir_bajas=1&' : '').http_build_query([
        'dni'=>$dni, 'nombre'=>$nombre, 'email'=>$email, 'roll'=>$roll, 'estado'=>$estado
    ])) ?>&format=csv">
    <button>Descargar CSV</button>
  </a>
</div>

<h2>Listado de Empleados</h2>
<table>
  <thead>
  <tr>
    <th>DNI</th><th>Nombre</th><th>Rol</th><th>Email</th>
    <th>Dirección</th><th>Localidad</th><th>Teléfono</th>
    <th>Habilitado</th><th>Estado</th><th>Lic.Desde</th><th>Lic.Hasta</th>
  </tr>
  </thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="11" style="text-align:center;">Sin resultados</td></tr>
  <?php else: foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['empleado_DNI']) ?></td>
      <td><?= e($r['empleado_nombre']) ?></td>
      <td><?= e($r['empleado_roll']) ?></td>
      <td><?= e($r['empleado_email']) ?></td>
      <td><?= e($r['empleado_direccion']) ?></td>
      <td><?= e($r['empleado_localidad']) ?></td>
      <td><?= e($r['empleado_telefono']) ?></td>
      <td><?= $r['empleado_habilitado'] ? 'SI' : 'NO' ?></td>
      <td><?= e($r['empleado_estado']) ?></td>
      <td><?= e($r['licencia_desde']) ?></td>
      <td><?= e($r['licencia_hasta']) ?></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</body>
</html>

