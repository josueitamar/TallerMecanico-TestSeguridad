<?php
session_start();
require_once 'verificar_sesion_empleado.php';
require_once 'conexion_base.php';

/*
 Trae órdenes finalizadas (ot.orden_estado=1) y sin facturar (ot.factura_id IS NULL)
 e incluye el subtotal de productos por orden (sum(cantidad*precio_unitario)).
*/
$sql = "
SELECT 
  o.orden_numero, o.orden_fecha,
  c.cliente_nombre, c.cliente_DNI, c.cliente_direccion, c.cliente_telefono, c.cliente_email,
  v.vehiculo_patente, v.vehiculo_marca, v.vehiculo_modelo,
  s.servicio_codigo, s.servicio_nombre,
  ot.orden_comentario, ot.costo_ajustado,
  IFNULL(op.productos_total, 0)   AS productos_total,
  IFNULL(op.productos_count, 0)   AS productos_count
FROM orden_trabajo ot
JOIN ordenes   o ON o.orden_numero = ot.orden_numero
JOIN servicios s ON s.servicio_codigo = ot.servicio_codigo
JOIN vehiculos v ON v.vehiculo_patente = o.vehiculo_patente
JOIN clientes  c ON c.cliente_DNI = v.cliente_DNI
LEFT JOIN (
  SELECT orden_numero,
         SUM(cantidad * precio_unitario) AS productos_total,
         COUNT(*) AS productos_count
  FROM orden_productos
  GROUP BY orden_numero
) op ON op.orden_numero = ot.orden_numero
WHERE ot.orden_estado = 1         -- finalizado
  AND ot.factura_id IS NULL       -- sin facturar
ORDER BY o.orden_fecha DESC, o.orden_numero DESC
";
$rows = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Facturación</title>
  <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
</head>
<body>
<?php include 'nav_rec.php'; ?>

<main class="facturacion">
  <h2 style="text-align:center;margin:16px 0;">Trabajos finalizados pendientes de facturar</h2>

  <table>
    <thead>
      <tr>
        <th>Orden</th>
        <th>Fecha</th>
        <th>Cliente</th>
        <th>DNI</th>
        <th>Vehículo</th>
        <th>Servicio</th>
        <th class="right">Serv. ($)</th>
        <th class="right">Prod. ($)</th>
        <th class="right">Total ($)</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr class="fila-vacia"><td colspan="10">No hay trabajos pendientes de facturar.</td></tr>
      <?php else: foreach ($rows as $r): 
        $servicio  = (float)$r['costo_ajustado'];
        $prods     = (float)$r['productos_total'];
        $totalFact = $servicio + $prods;
      ?>
        <tr>
          <td><?= htmlspecialchars($r['orden_numero']) ?></td>
          <td><?= htmlspecialchars($r['orden_fecha']) ?></td>
          <td><?= htmlspecialchars($r['cliente_nombre']) ?></td>
          <td><?= htmlspecialchars($r['cliente_DNI']) ?></td>
          <td>
            <div><?= htmlspecialchars($r['vehiculo_patente']) ?></div>
            <div class="mini"><?= htmlspecialchars($r['vehiculo_marca'].' '.$r['vehiculo_modelo']) ?></div>
          </td>
          <td>
            <div><strong><?= htmlspecialchars($r['servicio_codigo']) ?></strong> - <?= htmlspecialchars($r['servicio_nombre']) ?></div>
            <?php if (trim((string)$r['orden_comentario'])!==''): ?>
              <div class="mini"><?= htmlspecialchars($r['orden_comentario']) ?></div>
            <?php endif; ?>
            <?php if ((int)$r['productos_count'] > 0): ?>
              <div class="mini">Productos asociados: <?= (int)$r['productos_count'] ?> (subtot: $ <?= number_format($prods,2,',','.') ?>)</div>
            <?php else: ?>
              <div class="mini" style="color:#a00;">Sin productos cargados</div>
            <?php endif; ?>
          </td>
          <td class="right">$ <?= number_format($servicio, 2, ',', '.') ?></td>
          <td class="right">$ <?= number_format($prods,    2, ',', '.') ?></td>
          <td class="right"><strong>$ <?= number_format($totalFact, 2, ',', '.') ?></strong></td>
          <td>
            <button type="button" class="btn-icono"
              onclick="abrirModalFactura(this)"
              data-orden_numero="<?= htmlspecialchars($r['orden_numero']) ?>"
              data-orden_fecha="<?= htmlspecialchars($r['orden_fecha']) ?>"
              data-cliente_nombre="<?= htmlspecialchars($r['cliente_nombre']) ?>"
              data-cliente_dni="<?= htmlspecialchars($r['cliente_DNI']) ?>"
              data-cliente_direccion="<?= htmlspecialchars($r['cliente_direccion']) ?>"
              data-cliente_telefono="<?= htmlspecialchars($r['cliente_telefono']) ?>"
              data-cliente_email="<?= htmlspecialchars($r['cliente_email']) ?>"
              data-vehiculo_patente="<?= htmlspecialchars($r['vehiculo_patente']) ?>"
              data-vehiculo_marca="<?= htmlspecialchars($r['vehiculo_marca']) ?>"
              data-vehiculo_modelo="<?= htmlspecialchars($r['vehiculo_modelo']) ?>"
              data-servicio_codigo="<?= htmlspecialchars($r['servicio_codigo']) ?>"
              data-servicio_nombre="<?= htmlspecialchars($r['servicio_nombre']) ?>"
              data-orden_comentario="<?= htmlspecialchars($r['orden_comentario']) ?>"
              data-costo_ajustado="<?= htmlspecialchars($r['costo_ajustado']) ?>"
              data-productos_total="<?= number_format($prods, 2, '.', '') ?>"
              data-total_factura="<?= number_format($totalFact, 2, '.', '') ?>"
            >🧾 Facturar</button>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</main>

<!-- MODAL -->
<dialog id="modal_factura">
  <form id="form_facturar" method="post" action="generar_factura.php"
        target="facturaWin" onsubmit="return onSubmitFactura(this)">
    <h3 style="margin-bottom:10px;">Emitir factura</h3>

    <div class="modal-row">
      <strong>Tipo:</strong>
      <label><input type="radio" name="tipo" value="A"> A</label>
      <label><input type="radio" name="tipo" value="B" checked> B</label>
      <label><input type="radio" name="tipo" value="C"> C</label>
    </div>

    <div class="modal-row">
      <strong>Acción:</strong>
      <label><input type="radio" name="accion" value="imprimir" checked> Imprimir</label>
      <label><input type="radio" name="accion" value="email"> Email</label>
    </div>

    <div id="email_box" class="modal-row" style="display:none;">
      <label for="email_destino"><strong>Email destino:</strong></label>
      <input type="email" name="email_destino" id="email_destino" style="width:260px;">
      <div class="mini">Si lo dejás vacío usa el email del cliente.</div>
    </div>

    <!-- Resumen montos -->
    <div class="resumen-box" id="resumen_montos" aria-live="polite">
      <div class="row"><span>Servicio</span><strong id="r_servicio">$ 0,00</strong></div>
      <div class="row"><span>Productos</span><strong id="r_productos">$ 0,00</strong></div>
      <div class="row"><span>Total</span><strong id="r_total">$ 0,00</strong></div>
    </div>

    <!-- Hidden inputs con todos los datos -->
    <input type="hidden" name="orden_numero">
    <input type="hidden" name="orden_fecha">
    <input type="hidden" name="cliente_nombre">
    <input type="hidden" name="cliente_dni">
    <input type="hidden" name="cliente_direccion">
    <input type="hidden" name="cliente_telefono">
    <input type="hidden" name="cliente_email">
    <input type="hidden" name="vehiculo_patente">
    <input type="hidden" name="vehiculo_marca">
    <input type="hidden" name="vehiculo_modelo">
    <input type="hidden" name="servicio_codigo">
    <input type="hidden" name="servicio_nombre">
    <input type="hidden" name="orden_comentario">
    <input type="hidden" name="costo_ajustado">

    <!-- También mandamos estos dos como hint/preview (el total REAL se recalcula en generar_factura.php) -->
    <input type="hidden" name="productos_total">
    <input type="hidden" name="total_factura">

    <div class="modal-actions">
      <button class="guardar_rec" type="submit">Generar</button>
      <button class="cancelar_boton" type="button" onclick="cerrarModalFactura()">Cancelar</button>
    </div>
  </form>
</dialog>

<script>
function abrirModalFactura(btn){
  const d = document.getElementById('modal_factura');
  const f = document.getElementById('form_facturar');
  if (!d || !f) return;

  // carga de campos
  const names = [
    'orden_numero','orden_fecha','cliente_nombre','cliente_dni','cliente_direccion',
    'cliente_telefono','cliente_email','vehiculo_patente','vehiculo_marca','vehiculo_modelo',
    'servicio_codigo','servicio_nombre','orden_comentario','costo_ajustado',
    'productos_total','total_factura'
  ];
  names.forEach(n => { if (f.elements[n]) f.elements[n].value = btn.dataset[n] ?? ''; });

  // resumen visible
  const servicio  = parseFloat(btn.dataset.costo_ajustado || '0') || 0;
  const productos = parseFloat(btn.dataset.productos_total || '0') || 0;
  const total     = parseFloat(btn.dataset.total_factura || (servicio + productos)) || 0;
  document.getElementById('r_servicio').textContent  = formatear(servicio);
  document.getElementById('r_productos').textContent = formatear(productos);
  document.getElementById('r_total').textContent     = formatear(total);

  // defaults: tipo B + imprimir
  f.querySelectorAll('input[name="tipo"]').forEach(r => r.checked = (r.value === 'B'));
  f.querySelectorAll('input[name="accion"]').forEach(r => r.checked = (r.value === 'imprimir'));
  document.getElementById('email_box').style.display = 'none';
  document.getElementById('email_destino').value = '';

  if (typeof d.showModal === 'function') d.showModal();
  else d.setAttribute('open','open');
}

function cerrarModalFactura() {
  const d = document.getElementById('modal_factura');
  if (!d) return;
  if (typeof d.close === 'function') d.close(); else d.removeAttribute('open');
}

document.getElementById('form_facturar').addEventListener('change', (e)=>{
  if (e.target.name === 'accion') {
    document.getElementById('email_box').style.display =
      (e.target.value === 'email') ? 'block' : 'none';
  }
});

let _enviandoFactura = false;
function onSubmitFactura(form) {
  if (_enviandoFactura) return false;
  _enviandoFactura = true;

  const win = window.open('', 'facturaWin');
  if (!win) form.removeAttribute('target');

  cerrarModalFactura();
  setTimeout(() => { window.location.reload(); }, 600);
  return true;
}

function formatear(num){
  // Formato $ 1.234,56 (AR)
  const n = Number(num || 0);
  return '$ ' + n.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

<?php include("piedepagina.php"); ?>
</body>
</html>

