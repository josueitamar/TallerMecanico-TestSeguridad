<?php
require_once 'verificar_sesion_empleado.php';
require_once 'conexion_base.php';
require_once 'tcpdf/tcpdf.php';

// ========= Helpers =========
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nfmt($n){ return number_format((float)$n, 2, ',', '.'); }

// ========= Inputs del modal (solo para fallback UI; cálculos NO confían en esto) =========
$tipo              = $_POST['tipo'] ?? 'B';                 // A | B | C
$accion            = $_POST['accion'] ?? 'imprimir';        // imprimir | email
$email_destino     = trim($_POST['email_destino'] ?? '');
$orden_numero      = (int)($_POST['orden_numero'] ?? 0);

// (Estos datos se van a refrescar desde la BD)
$empleado_dni = $_SESSION['empleado_dni'] ?? null;

// ========= Datos del taller =========
$taller_nombre = "WA SPORT - Taller Mecánico";
$taller_dir    = "Paso 1418 - Ciudadela";
$taller_tel    = "Tel: 11-5717-2522";
$taller_mail   = "wasporttaller@gmail.com";
$logo_path     = 'iconos/WA_Sport.jpg';

// ========= Traer datos FRESCOS de la orden/cliente/vehículo/servicio =========
$st = $conexion->prepare("
    SELECT 
        o.orden_numero, o.orden_fecha,
        v.vehiculo_patente, v.vehiculo_marca, v.vehiculo_modelo, v.vehiculo_anio,
        c.cliente_DNI, c.cliente_nombre, c.cliente_direccion, c.cliente_telefono, c.cliente_email,
        s.servicio_codigo, s.servicio_nombre,
        ot.orden_comentario, ot.costo_ajustado, ot.factura_id
    FROM ordenes o
    JOIN vehiculos v      ON v.vehiculo_patente = o.vehiculo_patente
    JOIN clientes c       ON c.cliente_DNI      = v.cliente_DNI
    JOIN orden_trabajo ot ON ot.orden_numero    = o.orden_numero
    JOIN servicios s      ON s.servicio_codigo  = ot.servicio_codigo
    WHERE o.orden_numero = :n
    LIMIT 1
");
$st->execute([':n' => $orden_numero]);
$orden = $st->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
    http_response_code(404);
    die("No existe la orden.");
}
if ((int)$orden['factura_id'] > 0) {
    die("La orden ya está facturada (Factura ID: ".e($orden['factura_id']).").");
}

// ========= Recalcular SUBTOTAL de PRODUCTOS y listar ítems (para PDF) =========
$st = $conexion->prepare("
    SELECT prod_codigo, prod_descripcion, cantidad, precio_unitario
    FROM orden_productos
    WHERE orden_numero = :n
    ORDER BY id ASC
");
$st->execute([':n' => $orden_numero]);
$items = $st->fetchAll(PDO::FETCH_ASSOC);

$subtotal_productos = 0.0;
foreach ($items as $it) {
    $subtotal_productos += (float)$it['cantidad'] * (float)$it['precio_unitario'];
}

// Subtotal servicio desde BD
$subtotal_servicio = (float)$orden['costo_ajustado'];

// Total final
$total_factura = $subtotal_servicio + $subtotal_productos;

// ========= Numeración + Insert factura + Enlace a OT =========
try {
  $conexion->beginTransaction();

  // Numerador por tipo (lock optimista)
  $stmt = $conexion->prepare("SELECT proximo FROM factura_numeradores WHERE tipo = ? FOR UPDATE");
  $stmt->execute([$tipo]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    $conexion->prepare("INSERT INTO factura_numeradores (tipo, proximo) VALUES (?,1)")
             ->execute([$tipo]);
    $nro_comprobante = 1;
    $conexion->prepare("UPDATE factura_numeradores SET proximo = proximo + 1 WHERE tipo = ?")
             ->execute([$tipo]);
  } else {
    $nro_comprobante = (int)$row['proximo'];
    $conexion->prepare("UPDATE factura_numeradores SET proximo = proximo + 1 WHERE tipo = ?")
             ->execute([$tipo]);
  }

  // Insertar factura (usa tu esquema actual: 'total' = servicio + productos)
  $ins = $conexion->prepare("
    INSERT INTO facturas
      (tipo, nro_comprobante, orden_numero, servicio_codigo, cliente_dni, vehiculo_patente,
       total, pdf_nombre, email_destino, email_enviado, empleado_emisor)
    VALUES
      (:tipo, :nro, :orden, :serv, :dni, :patente, :total, NULL, :email, 0, :emp)
  ");
  $ins->execute([
    ':tipo'    => $tipo,
    ':nro'     => $nro_comprobante,
    ':orden'   => $orden_numero,
    ':serv'    => $orden['servicio_codigo'],
    ':dni'     => $orden['cliente_DNI'],
    ':patente' => $orden['vehiculo_patente'],
    ':total'   => $total_factura, // <<<<<< TOTAL = servicio + productos
    ':email'   => ($accion === 'email') ? ($email_destino ?: ($orden['cliente_email'] ?? null)) : null,
    ':emp'     => $empleado_dni
  ]);
  $factura_id = (int)$conexion->lastInsertId();

  // Marcar OT como facturada
  $up = $conexion->prepare("
    UPDATE orden_trabajo
       SET factura_id = :fid
     WHERE orden_numero = :orden AND servicio_codigo = :serv AND factura_id IS NULL
  ");
  $up->execute([
    ':fid'   => $factura_id,
    ':orden' => $orden_numero,
    ':serv'  => $orden['servicio_codigo']
  ]);
  if ($up->rowCount() === 0) {
    throw new Exception("El trabajo ya estaba facturado o no existe.");
  }

  $conexion->commit();

} catch (Throwable $e) {
  $conexion->rollBack();
  die("Error al numerar o vincular factura: " . e($e->getMessage()));
}

// ========= Clase PDF (TCPDF) =========
class PDF_FACT extends TCPDF {
    public $tipo;
    public $nro_comprobante;
    public $taller_nombre;
    public $taller_dir;
    public $taller_tel;
    public $taller_mail;
    public $logo_path;

    public function Header() {
        if (is_file($this->logo_path)) {
            $this->Image($this->logo_path, 10, 8, 35);
        }
        // Caja A/B/C
        $xBox = 60; $yBox = 10; $wBox = 18; $hBox = 18;
        $this->SetDrawColor(0,0,0);
        $this->Rect($xBox, $yBox, $wBox, $hBox);
        $this->SetFont('dejavusans','B',16);
        $this->SetXY($xBox, $yBox+2);
        $this->Cell($wBox, 8, $this->tipo, 0, 2, 'C');
        $this->SetFont('dejavusans','',8);
        $this->Cell($wBox, 6, 'Código N° 06', 0, 0, 'C');

        // Membrete
        $this->SetXY(0, 8);
        $this->SetFont('dejavusans','B',14);
        $this->Cell(0,6,$this->taller_nombre,0,1,'R');
        $this->SetFont('dejavusans','',9);
        $this->Cell(0,5,$this->taller_dir,0,1,'R');
        $this->Cell(0,5,$this->taller_tel.' - '.$this->taller_mail,0,1,'R');

        // Título + Número
        $this->Ln(2);
        $this->SetFont('dejavusans','B',16);
        $this->Cell(0,10,'FACTURA',0,1,'C');
        $this->SetFont('dejavusans','',11);
        $this->Cell(0,8,'Comprobante Nº '.str_pad($this->nro_comprobante, 8, '0', STR_PAD_LEFT), 0, 1, 'C');

        // Separador
        $this->SetDrawColor(255,142,49);
        $this->SetLineWidth(0.6);
        $this->Line(10,42,200,42);
        $this->Ln(3);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('dejavusans','I',8);
        $this->Cell(0,10,'Generado por WA SPORT - '.date('Y-m-d H:i'),0,0,'C');
    }
}

// ========= Construcción del PDF =========
$pdf = new PDF_FACT('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->tipo            = $tipo;
$pdf->nro_comprobante = $nro_comprobante;
$pdf->taller_nombre   = $taller_nombre;
$pdf->taller_dir      = $taller_dir;
$pdf->taller_tel      = $taller_tel;
$pdf->taller_mail     = $taller_mail;
$pdf->logo_path       = $logo_path;

$pdf->SetCreator('WA SPORT');
$pdf->SetAuthor('WA SPORT');
$pdf->SetTitle("Factura $tipo - $nro_comprobante - Orden {$orden['orden_numero']}");
$pdf->SetSubject('Factura');

$pdf->SetMargins(10, 48, 10);
$pdf->SetHeaderMargin(8);
$pdf->SetFooterMargin(12);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->AddPage();
$pdf->SetFont('dejavusans','',10);

// Encabezado Orden/Fecha
$tbl = '
<table cellpadding="6" cellspacing="0" border="1" width="100%">
  <tr style="background-color:#FFC631;">
    <td width="50%"><b>Orden N°:</b> '.e($orden['orden_numero']).'</td>
    <td width="50%"><b>Fecha:</b> '.e($orden['orden_fecha']).'</td>
  </tr>
</table><br/>';
$pdf->writeHTML($tbl, true, false, false, false, '');

// Datos Cliente
$cliente_bloque = '
<h3>Datos del Cliente</h3>
<table cellpadding="5" cellspacing="0" border="0" width="100%">
  <tr><td><b>Nombre:</b> '.e($orden['cliente_nombre']).'</td></tr>
  <tr><td><b>DNI:</b> '.e($orden['cliente_DNI']).'</td></tr>
  <tr><td><b>Dirección:</b> '.e($orden['cliente_direccion']).'</td></tr>
  <tr><td><b>Teléfono:</b> '.e($orden['cliente_telefono']).'</td></tr>
  <tr><td><b>Email:</b> '.e($orden['cliente_email']).'</td></tr>
</table><br/>';
$pdf->writeHTML($cliente_bloque, true, false, false, false, '');

// Datos Vehículo
$veh_bloque = '
<h3>Datos del Vehículo</h3>
<table cellpadding="5" cellspacing="0" border="0" width="100%">
  <tr><td><b>Patente:</b> '.e($orden['vehiculo_patente']).'</td></tr>
  <tr><td><b>Marca:</b> '.e($orden['vehiculo_marca']).'</td></tr>
  <tr><td><b>Modelo:</b> '.e($orden['vehiculo_modelo']).'</td></tr>
</table><br/>';
$pdf->writeHTML($veh_bloque, true, false, false, false, '');

// Detalle Servicio
$detalle_tbl = '
<h3>Detalle del Servicio</h3>
<table cellpadding="6" cellspacing="0" border="1" width="100%">
  <tr style="background-color:#FFE0AA;">
    <th width="20%"><b>Código</b></th>
    <th width="55%"><b>Servicio</b></th>
    <th width="25%"><b>Costo</b></th>
  </tr>
  <tr>
    <td align="center">'.e($orden['servicio_codigo']).'</td>
    <td>'.e($orden['servicio_nombre']).'</td>
    <td align="right">$ '.nfmt($subtotal_servicio).'</td>
  </tr>
</table>';
$pdf->writeHTML($detalle_tbl, true, false, false, false, '');

// Comentario (si hubiera)
if (trim((string)$orden['orden_comentario']) !== '') {
  $pdf->Ln(2);
  $coment_html = '
  <table cellpadding="5" cellspacing="0" border="0" width="100%">
    <tr><td><b>Comentario:</b><br/>'.nl2br(e($orden['orden_comentario'])).'</td></tr>
  </table>';
  $pdf->writeHTML($coment_html, true, false, false, false, '');
}

// Detalle Productos (si hay)
if (!empty($items)) {
  $wCode = 10;  // %
  $wDesc = 48;  // %
  $wCant = 10;  // %
  $wUnit = 16;  // %
  $wImp  = 16;  // %
  $wSubL = $wCode + $wDesc + $wCant + $wUnit; 
  $wSubR = $wImp;                              

  $pdf->Ln(3);
  $prod_html = '
  <h3>Productos</h3>
  <table cellpadding="6" cellspacing="0" border="1" width="100%">
    <thead>
      <tr style="background-color:#DFF0D8;">
        <th width="'.$wCode.'%"><b>Código</b></th>
        <th width="'.$wDesc.'%"><b>Descripción</b></th>
        <th width="'.$wCant.'%" align="right"><b>Cant.</b></th>
        <th width="'.$wUnit.'%" align="right"><b>P. Unit.</b></th>
        <th width="'.$wImp.'%"  align="right"><b>Importe</b></th>
      </tr>
    </thead>
    <tbody>';

  foreach ($items as $it) {
    $cant   = (float)$it['cantidad'];
    $punit  = (float)$it['precio_unitario'];
    $imp    = $cant * $punit;

    $prod_html .= '
      <tr>
        <td width="'.$wCode.'%">'.e($it['prod_codigo']).'</td>
        <td width="'.$wDesc.'%">'.e($it['prod_descripcion']).'</td>
        <td width="'.$wCant.'%" align="right" nowrap="nowrap">'.nfmt($cant).'</td>
        <td width="'.$wUnit.'%" align="right" nowrap="nowrap">$&nbsp;'.nfmt($punit).'</td>
        <td width="'.$wImp.'%"  align="right" nowrap="nowrap">$&nbsp;'.nfmt($imp).'</td>
      </tr>';
  }

  $prod_html .= '
      <tr>
        <td width="'.$wSubL.'%" align="right"><b>Subtotal productos</b></td>
        <td width="'.$wSubR.'%" align="right" nowrap="nowrap"><b>$&nbsp;'.nfmt($subtotal_productos).'</b></td>
      </tr>
    </tbody>
  </table>';

  $pdf->writeHTML($prod_html, true, false, false, false, '');
}

// Totales
$pdf->Ln(4);
$total_tbl = '
<table cellpadding="6" cellspacing="0" border="1" width="100%">
  <tr>
    <td width="75%" align="right"><b>Subtotal servicio</b></td>
    <td width="25%" align="right">$ '.nfmt($subtotal_servicio).'</td>
  </tr>
  <tr>
    <td width="75%" align="right"><b>Subtotal productos</b></td>
    <td width="25%" align="right">$ '.nfmt($subtotal_productos).'</td>
  </tr>
  <tr>
    <td width="75%" align="right"><b>TOTAL</b></td>
    <td width="25%" align="right"><b>$ '.nfmt($total_factura).'</b></td>
  </tr>
</table>';
$pdf->writeHTML($total_tbl, true, false, false, false, '');

// Guardar SIEMPRE en /facturas y actualizar BD
$nombreArchivo = 'Factura_'.$tipo.'_'.str_pad($nro_comprobante, 8, '0', STR_PAD_LEFT).'_Orden_'.$orden_numero.'.pdf';
$dirFact = __DIR__ . DIRECTORY_SEPARATOR . 'facturas';
if (!is_dir($dirFact)) { @mkdir($dirFact, 0777, true); }
$savePath = $dirFact . DIRECTORY_SEPARATOR . $nombreArchivo;
$pdf->Output($savePath, 'F');

$conexion->prepare("UPDATE facturas SET pdf_nombre = ? WHERE factura_id = ?")
         ->execute([$nombreArchivo, $factura_id]);

// ========= Output: imprimir o email =========
if ($accion === 'imprimir') {
  // stream inline + (opcional) auto print
  $pdf->IncludeJS('print(true);');
  header('Content-Type: application/pdf');
  header('Content-Disposition: inline; filename="'.$nombreArchivo.'"');
  readfile($savePath);
  exit;
}

// ======= Email =======
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$ok_envio = false;
$err_envio = '';
$destino_final = $email_destino ?: ($orden['cliente_email'] ?? '');

try {
  if ($destino_final === '') { throw new Exception('No hay email destino.'); }

  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host       = 'smtp.gmail.com';
  $mail->SMTPAuth   = true;
  $mail->Username   = 'wasporttaller@gmail.com';
  $mail->Password   = 'gdkwryakgynsewdl'; // App Password Gmail (ya la tenías)
  $mail->SMTPSecure = 'tls';
  $mail->Port       = 587;

  $mail->setFrom('wasporttaller@gmail.com', 'WA SPORT');
  $mail->addAddress($destino_final, $orden['cliente_nombre'] ?: 'Cliente');
  $mail->Subject = 'Factura '.$tipo.' Nº '.str_pad($nro_comprobante, 8, '0', STR_PAD_LEFT).' - Orden '.$orden_numero;
  $mail->isHTML(true);
  $mail->Body = '
    <p>Hola <strong>'.e($orden['cliente_nombre']).'</strong>,</p>
    <p>Adjuntamos la factura correspondiente a tu orden <strong>#'.e($orden_numero).'</strong>.</p>
    <p>Servicio: <strong>'.e($orden['servicio_codigo']).' - '.e($orden['servicio_nombre']).'</strong></p>
    <p>Importe servicio: <strong>$ '.nfmt($subtotal_servicio).'</strong><br>
       Importe productos: <strong>$ '.nfmt($subtotal_productos).'</strong><br>
       Total: <strong>$ '.nfmt($total_factura).'</strong></p>
    <p>¡Gracias por elegir <strong>WA SPORT</strong>!</p>';
  $mail->addAttachment($savePath, $nombreArchivo);
  $mail->send();
  $ok_envio = true;

  $conexion->prepare("UPDATE facturas SET email_enviado = 1, email_destino = ? WHERE factura_id = ?")
           ->execute([$destino_final, $factura_id]);

} catch (Exception $e) {
  $err_envio = $e->getMessage();
}

// ========= Respuesta HTML simple post-email =========
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8"><title>Factura - Envío</title>
<link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
</head>
<body>
<?php include 'nav_rec.php'; ?>
<section class="generar_fact">
  <?php if ($ok_envio): ?>
    <h2>Factura enviada correctamente</h2>
    <p>Tipo: <strong><?= e($tipo) ?></strong> — Nº <strong><?= str_pad($nro_comprobante, 8, '0', STR_PAD_LEFT) ?></strong></p>
    <p>Destino: <strong><?= e($destino_final) ?></strong></p>
    <p class="generar_fac_bot" >
      <a class="generar_fact_btn" href="facturacion.php">Volver a Facturación</a>
      <a class="generar_fact_btn" href="<?= 'download.php?name='.urlencode($nombreArchivo) ?>">Descargar PDF</a>
    </p>
  <?php else: ?>
    <h2>Error al enviar la factura</h2>
    <p><?= e($err_envio) ?></p>
    <p class="generar_fac_bot" >
      <a class="generar_fact_btn" href="<?= 'download.php?name='.urlencode($nombreArchivo) ?>">Descargar PDF</a>
      <a class="generar_fact_btn" href="facturacion.php">Volver</a>
    </p>
  <?php endif; ?>
</section>
<?php include("piedepagina.php"); ?>
</body>
</html>

