<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'verificar_sesion_empleado.php';
require_once 'conexion_base.php';

// =============== Helpers ===============
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nfmt($n){ return number_format((float)$n, 2, ',', '.'); }
function money($n){ return '$ '.nfmt($n); }

function filtros_from_request() {
    $tipo   = isset($_REQUEST['tipo'])  ? strtolower(trim($_REQUEST['tipo']))  : 'servicios';
    $desde  = isset($_REQUEST['desde']) ? trim($_REQUEST['desde']) : '';
    $hasta  = isset($_REQUEST['hasta']) ? trim($_REQUEST['hasta']) : '';
    $solo   = isset($_REQUEST['solo_facturadas']) ? 1 : 0;
    // mecánico (DNI) opcional
    $mec    = isset($_REQUEST['mecanico']) ? trim($_REQUEST['mecanico']) : '';

    if ($desde === '') { $desde = date('Y-m-01'); }
    if ($hasta === '') { $hasta = date('Y-m-d'); }
    return [$tipo,$desde,$hasta,$solo,$mec];
}

function validar_fechas($desde,$hasta){
    $errs = [];
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$desde)) $errs[]='Fecha "desde" inválida (AAAA-MM-DD).';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$hasta)) $errs[]='Fecha "hasta" inválida (AAAA-MM-DD).';
    if (!$errs && $desde>$hasta) $errs[]='Rango inválido (desde > hasta).';
    return $errs;
}

/**
 * Devuelve: [rows, cols, totalGeneral]
 * - $tipo: 'servicios' | 'ventas'
 * - $mecDni: '' (todos) o DNI a filtrar
 */
function obtener_estadisticas(PDO $db, $tipo, $desde, $hasta, $solo_facturadas, $mecDni=''){
    if ($tipo === 'servicios') {
        $sql = "
        SELECT 
            o.orden_fecha                                                             AS fecha,
            f.tipo                                                                    AS tipo_factura,
            f.nro_comprobante                                                         AS nro_comprobante,
            o.orden_numero                                                            AS orden,
            ot.orden_kilometros                                                       AS orden_kilometros,
            s.servicio_nombre                                                         AS servicio,
            COALESCE(
                f.total,
                ot.costo_ajustado + COALESCE((
                    SELECT SUM(op.cantidad*op.precio_unitario)
                    FROM orden_productos op WHERE op.orden_numero = o.orden_numero
                ),0)
            )                                                                         AS total,
            em.empleado_nombre                                                        AS mecanico
        FROM ordenes o
        JOIN orden_trabajo ot ON ot.orden_numero = o.orden_numero
        JOIN servicios s      ON s.servicio_codigo = ot.servicio_codigo
        LEFT JOIN facturas f  ON f.orden_numero = o.orden_numero
                             AND f.servicio_codigo = ot.servicio_codigo
        LEFT JOIN empleados em ON em.empleado_DNI = ot.mecanico_DNI
        WHERE o.orden_fecha BETWEEN :d AND :h
          AND ot.orden_estado = 1
        ";
        if ($solo_facturadas) { $sql .= " AND f.factura_id IS NOT NULL "; }
        if ($mecDni !== '')   { $sql .= " AND ot.mecanico_DNI = :mec "; }

        $sql .= " ORDER BY o.orden_fecha ASC, o.orden_numero ASC";

        $params = [':d'=>$desde, ':h'=>$hasta];
        if ($mecDni !== '') { $params[':mec'] = $mecDni; }

        $st = $db->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $cols = ['fecha','tipo_factura','nro_comprobante','orden','orden_kilometros','servicio','total','mecanico'];
        $totalGeneral = 0.0;
        foreach($rows as $r){ $totalGeneral += (float)$r['total']; }

        return [$rows,$cols,$totalGeneral];
    } else {
        // Ventas de productos
        $sql = "
        SELECT 
            o.orden_fecha                                     AS fecha,
            o.orden_numero                                   AS orden,
            op.prod_codigo                                    AS prod_codigo,
            op.prod_descripcion                               AS prod_descripcion,
            op.cantidad                                       AS cantidad,
            op.precio_unitario                                AS precio_unitario,
            (op.cantidad*op.precio_unitario)                  AS importe,
            em.empleado_nombre                                AS mecanico,
            f.tipo                                            AS tipo_factura,
            f.nro_comprobante                                 AS nro_comprobante
        FROM ordenes o
        JOIN orden_productos op ON op.orden_numero = o.orden_numero
        " . ($solo_facturadas ? "JOIN facturas f ON f.orden_numero = o.orden_numero " 
                              : "LEFT JOIN facturas f ON f.orden_numero = o.orden_numero ") . "
        LEFT JOIN empleados em ON em.empleado_DNI = op.mecanico_DNI
        WHERE o.orden_fecha BETWEEN :d AND :h
        ";
        if ($mecDni !== '') { $sql .= " AND op.mecanico_DNI = :mec "; }

        $sql .= " ORDER BY o.orden_fecha ASC, o.orden_numero ASC, op.id ASC";

        $params = [':d'=>$desde, ':h'=>$hasta];
        if ($mecDni !== '') { $params[':mec'] = $mecDni; }

        $st = $db->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $cols = ['fecha','orden','prod_codigo','prod_descripcion','cantidad','precio_unitario','importe','mecanico','tipo_factura','nro_comprobante'];
        $totalGeneral = 0.0;
        foreach($rows as $r){ $totalGeneral += (float)$r['importe']; }

        return [$rows,$cols,$totalGeneral];
    }
}

// ================== Datos base UI: lista de mecánicos ==================
$listaMecanicos = [];
try {
    // Solo empleados cuyo rol sea "mecanico" (con o sin acento)
    $sql = "
        SELECT empleado_DNI, empleado_nombre
        FROM empleados
        WHERE LOWER(empleado_roll) IN ('mecanico','mecánico')
        ORDER BY empleado_nombre ASC
    ";
    $rs = $conexion->query($sql);
    $listaMecanicos = $rs ? $rs->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $e) {
    $listaMecanicos = [];
}

// ===================== Export =====================
list($tipo,$desde,$hasta,$solo,$mec) = filtros_from_request();
$errores = validar_fechas($desde,$hasta);

if (isset($_GET['export']) && !$errores) {
    list($rows,$cols,$totalGeneral) = obtener_estadisticas($conexion,$tipo,$desde,$hasta,$solo,$mec);

    if ($_GET['export'] === 'pdf') {
        require_once 'tcpdf/tcpdf.php';
        class PDF_EST extends TCPDF {
            public $titulo;
            function Header(){
                $this->SetFont('dejavusans','B',12);
                $this->Cell(0,8,$this->titulo,0,1,'C');
                $this->Ln(2);
            }
            function Footer(){
                $this->SetY(-15);
                $this->SetFont('dejavusans','I',8);
                $this->Cell(0,10,'Generado el '.date('Y-m-d H:i'),0,0,'C');
            }
        }
        $pdf = new PDF_EST('L','mm','A4',true,'UTF-8',false);
        $tit = 'Estadísticas - '.ucfirst($tipo).' ('.$desde.' a '.$hasta.')'.($solo?' - Solo facturadas':'');
        if ($mec !== '') $tit .= ' - Mecánico: '.$mec;
        $pdf->titulo = $tit;
        $pdf->SetMargins(10,20,10);
        $pdf->AddPage();
        $pdf->SetFont('dejavusans','',9);

        $th = '';
        foreach($cols as $c){ $th .= '<th style="background-color:#f2f2f2;border:1px solid #ddd;">'.e(ucwords(str_replace('_',' ',$c))).'</th>'; }
        $trs = '';
        foreach($rows as $r){
            $trs .= '<tr>';
            foreach($cols as $c){
                $val = $r[$c] ?? '';
                if ($tipo==='servicios' && $c==='total') $val = money($val);
                if ($tipo==='ventas' && in_array($c,['precio_unitario','importe'])) $val = money($val);
                $align = in_array($c,['cantidad','precio_unitario','importe','total'])?'right':'left';
                $trs .= '<td style="border:1px solid #ddd;text-align:'.$align.';padding:4px 6px;">'.e((string)$val).'</td>';
            }
            $trs .= '</tr>';
        }
        $tbl = '<table cellpadding="4" cellspacing="0" width="100%"><thead><tr>'.$th.'</tr></thead><tbody>'.$trs.'</tbody></table>';
        $pdf->writeHTML($tbl,true,false,false,false,'');

        $pdf->Ln(2);
        $pdf->SetFont('dejavusans','B',10);
        $pdf->Cell(0,8,'Total: '.money($totalGeneral),0,1,'R');

        $fname = 'estadisticas_'. $tipo .'_'. $desde .'_'. $hasta . ($solo?'_solo':'') . ($mec!=='' ? ('_mec_'.$mec) : '') .'.pdf';
        $pdf->Output($fname,'I');
        exit;
    }

    if ($_GET['export'] === 'excel') {
        $fname = 'estadisticas_'. $tipo .'_'. $desde .'_'. $hasta . ($solo?'_solo':'') . ($mec!=='' ? ('_mec_'.$mec) : '') .'.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$fname.'"');

        echo '<meta charset="UTF-8">';
        echo '<table border="1" cellspacing="0" cellpadding="4">';
        echo '<tr><th colspan="'.count($cols).'" style="font-weight:bold;background:#ddd;">'.
             'Estadísticas - '.e(ucfirst($tipo)).' ('.e($desde).' a '.e($hasta).')'.
             ($solo?' - Solo facturadas':'').
             ($mec!=='' ? ' - Mecánico: '.e($mec) : '').
             '</th></tr>';
        echo '<tr>';
        foreach($cols as $c){ echo '<th>'.e(ucwords(str_replace('_',' ',$c))).'</th>'; }
        echo '</tr>';
        foreach($rows as $r){
            echo '<tr>';
            foreach($cols as $c){
                $val = $r[$c] ?? '';
                if ($tipo==='servicios' && $c==='total') $val = money($val);
                if ($tipo==='ventas' && in_array($c,['precio_unitario','importe'])) $val = money($val);
                echo '<td>'.e((string)$val).'</td>';
            }
            echo '</tr>';
        }
        echo '<tr><td colspan="'.(count($cols)-1).'" style="text-align:right;font-weight:bold;">Total</td><td style="font-weight:bold;">'.money($totalGeneral).'</td></tr>';
        echo '</table>';
        exit;
    }
}

// ============== Pantalla ==============
require_once 'nav_gerente.php';
list($tipo,$desde,$hasta,$solo,$mec) = filtros_from_request();
$errores = validar_fechas($desde,$hasta);

$rows=[]; $cols=[]; $totalGeneral=0.0;
if (!$errores) {
    list($rows,$cols,$totalGeneral) = obtener_estadisticas($conexion,$tipo,$desde,$hasta,$solo,$mec);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Estadísticas</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

</head>
<body>
<div class="ger_empleados">
    <h2>Estadísticas</h2>

    <form class="filtros" method="get" action="estadisticas.php">
        <label class="ger_empleados"  for="tipo">Tipo</label>
        <select class="ger_empleado_sel" name="tipo" id="tipo">
            <option value="servicios" <?= $tipo==='servicios'?'selected':'' ?>>Servicios</option>
            <option value="ventas" <?= $tipo==='ventas'?'selected':'' ?>>Ventas</option>
        </select>

        <label class="ger_empleados"  for="desde">Desde</label>
        <input  class="ger_empleados_input" type="date" id="desde" name="desde" value="<?= e($desde) ?>">

        <label class="ger_empleados"  for="hasta">Hasta</label>
        <input class="ger_empleados_input" type="date" id="hasta" name="hasta" value="<?= e($hasta) ?>">

        <label>
            <input class="ger_empleados_check " type="checkbox" name="solo_facturadas" value="1" <?= $solo?'checked':'' ?>>
            Solo facturadas
        </label>

        <label class="ger_empleados"  for="mecanico" style="margin-left:10px;">Mecánico:</label>
        <select class="ger_empleado_sel" name="mecanico" id="mecanico">
            <option value="">Todos</option>
            <?php foreach($listaMecanicos as $m): ?>
                <option value="<?= e($m['empleado_DNI']) ?>" <?= ($mec===$m['empleado_DNI']?'selected':'') ?>>
                    <?= e($m['empleado_nombre']).' ('.e($m['empleado_DNI']).')' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="ger_empleados_btn">
            <button  type="submit" name="buscar" value="1">Buscar</button>
            <a  href="estadisticas.php">Limpiar</a>

            <button  type="button" onclick="abrirExport()">Exportar</button>
        </div>    
    </form>

    <?php if ($errores): ?>
        <div class="alert">
            <?php foreach($errores as $e): ?>• <?= e($e) ?><br><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <table class="ger_empleados_tabla_esta">
        <thead>
        <tr>
            <?php foreach($cols as $c): ?>
                <th><?= e(ucwords(str_replace('_',' ',$c))) ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="<?= max(1,count($cols)) ?>"><em>No hay datos para el criterio seleccionado.</em></td></tr>
        <?php else: foreach($rows as $r): ?>
            <tr>
                <?php foreach($cols as $c):
                    $val = $r[$c] ?? '';
                    if ($tipo==='servicios' && $c==='total') $val = money($val);
                    if ($tipo==='ventas' && in_array($c,['precio_unitario','importe'])) $val = money($val);
                    $cls = in_array($c,['cantidad','precio_unitario','importe','total'])?'right':'';
                ?>
                    <td class="<?= $cls ?>"><?= e((string)$val) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="totalbox">
        Total <?= ($tipo==='servicios'?'(servicios)':'(ventas)') ?>: <?= money($totalGeneral) ?>
    </div>
</div>

<!-- Modal Exportar -->
<dialog id="export_modal">
    <form method="get" action="estadisticas.php" id="export_form">
        <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
        <input type="hidden" name="desde" value="<?= e($desde) ?>">
        <input type="hidden" name="hasta" value="<?= e($hasta) ?>">
        <?php if($solo): ?><input type="hidden" name="solo_facturadas" value="1"><?php endif; ?>
        <?php if($mec!==''): ?><input type="hidden" name="mecanico" value="<?= e($mec) ?>"><?php endif; ?>

        <h3 style="margin-top:0;">Exportar listado</h3>
        <label style="display:block;margin:6px 0;">
            <input type="radio" name="export" value="pdf" checked> PDF (TCPDF)
        </label>
        <label style="display:block;margin:6px 0;">
            <input type="radio" name="export" value="excel"> Excel (.xls)
        </label>

        <div class="modal-actions">
            <button class="btn btn-primario" type="submit">Exportar</button>
            <button class="btn btn-neutro" type="button" onclick="cerrarExport()">Cancelar</button>
        </div>
    </form>
</dialog>

<script>
function abrirExport(){
  const d = document.getElementById('export_modal');
  if (d.showModal) d.showModal(); else d.setAttribute('open','open');
}
function cerrarExport(){
  const d = document.getElementById('export_modal');
  if (d.close) d.close(); else d.removeAttribute('open');
}
</script>
</body>
</html>
