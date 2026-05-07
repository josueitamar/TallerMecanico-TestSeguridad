<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['empleado_dni'])) { header("Location: login.php"); exit; }

require_once 'conexion_base.php';
require_once 'nav_gerente.php';

// Helpers
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nfmt($n){ return number_format((float)$n, 2, ',', '.'); }
function quitar_acentos($str){
    $map = ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N','á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n'];
    return strtr($str, $map);
}

// ===================== FILTROS (GET) =====================
$codigo      = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';
$descripcion = isset($_GET['descripcion']) ? trim($_GET['descripcion']) : '';
$categoria   = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
$incluir_no_disponibles = isset($_GET['incluir_no_disponibles']) ? 1 : 0;
$nuevo = isset($_GET['nuevo']) ? 1 : 0;

function filtros_qs($codigo, $categoria, $descripcion, $incluir_no_disponibles) {
    $arr = ['codigo'=>$codigo, 'categoria'=>$categoria, 'descripcion'=>$descripcion];
    if ($incluir_no_disponibles) $arr['incluir_no_disponibles']=1;
    return http_build_query($arr);
}
$current_qs = filtros_qs($codigo, $categoria, $descripcion, $incluir_no_disponibles);

// ===================== ACCIONES (POST) =====================
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $ids    = isset($_POST['ids']) ? array_filter((array)$_POST['ids'], 'is_numeric') : [];

    $f_codigo      = $_POST['f_codigo']      ?? $codigo;
    $f_categoria   = $_POST['f_categoria']   ?? $categoria;
    $f_descripcion = $_POST['f_descripcion'] ?? $descripcion;
    $f_incluir_nd  = isset($_POST['f_incluir_no_disponibles']) ? 1 : $incluir_no_disponibles;
    $qs = filtros_qs($f_codigo, $f_categoria, $f_descripcion, $f_incluir_nd);

    if ($accion === 'ver_guardar') {
        $id     = (int)($_POST['id'] ?? 0);
        $estado = (int)($_POST['prod_disponible'] ?? 1);
        $stock  = max(0, (int)($_POST['prod_stock'] ?? 0));
        $prov   = max(0, (float)($_POST['prod_precio_proveedor'] ?? 0));
        $venta  = round($prov * 1.10, 2);
        if ($stock <= 0) $estado = 0;

        $sql = "UPDATE productos
                SET prod_stock=:stock, prod_precio_proveedor=:prov,
                    prod_precio_venta=:venta, prod_disponible=:disp
                WHERE prod_id=:id";
        $st = $conexion->prepare($sql);
        $st->execute([':stock'=>$stock, ':prov'=>$prov, ':venta'=>$venta, ':disp'=>$estado, ':id'=>$id]);

        header("Location: productos.php?{$qs}&msg=edit_ok");
        exit;
    }

    if ($accion === 'incrementar_aplicar') {
        if (empty($ids)) { header("Location: productos.php?{$qs}&msg=sin_ids"); exit; }
        $porcentaje = (float)($_POST['porcentaje'] ?? 0);
        if ($porcentaje < 0) { header("Location: productos.php?{$qs}&msg=porcentaje_invalido"); exit; }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE productos
                SET prod_precio_venta = ROUND(prod_precio_venta * (1 + (?/100)), 2)
                WHERE prod_id IN ($placeholders)";
        $st = $conexion->prepare($sql);
        $params = array_merge([$porcentaje], array_map('intval', $ids));
        $st->execute($params);

        header("Location: productos.php?{$qs}&msg=incremento_ok");
        exit;
    }

    if ($accion === 'eliminar_aplicar') {
        if (empty($ids)) { header("Location: productos.php?{$qs}&msg=sin_ids"); exit; }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE productos SET prod_disponible = 0 WHERE prod_id IN ($placeholders)";
        $st = $conexion->prepare($sql);
        $st->execute(array_map('intval', $ids));

        header("Location: productos.php?{$qs}&msg=eliminar_ok");
        exit;
    }

    if ($accion === 'nuevo_guardar') {
        $cat_sel  = trim((string)($_POST['prod_categoria'] ?? ''));
        $cat_new  = trim((string)($_POST['prod_categoria_nueva'] ?? ''));
        $cat      = $cat_new !== '' ? $cat_new : $cat_sel;
        $desc  = trim((string)($_POST['prod_descripcion'] ?? ''));
        $stock = max(0, (int)($_POST['prod_stock'] ?? 0));
        $prov  = max(0, (float)($_POST['prod_precio_proveedor'] ?? 0));

        if ($cat === '' || $desc === '') {
            header("Location: productos.php?{$qs}&msg=nuevo_invalido&nuevo=1");
            exit;
        }

        $st = $conexion->prepare("SELECT prod_codigo FROM productos WHERE prod_categoria = ? ORDER BY prod_codigo ASC");
        $st->execute([$cat]);
        $codigos = $st->fetchAll(PDO::FETCH_COLUMN);

        $prefijo = '';
        $dig_len = 3;
        $max_n = 0;

        foreach ($codigos as $c) {
            if (preg_match('/^([A-Z]+)(\d{2,})$/i', $c, $m)) {
                $pf = strtoupper($m[1]);
                $nn = (int)$m[2];
                if ($nn > $max_n) {
                    $max_n  = $nn;
                    $dig_len = max($dig_len, strlen($m[2]));
                    $prefijo = $pf;
                }
            }
        }

        if ($prefijo === '') {
            $base = strtoupper(quitar_acentos(preg_replace('/\s+/', '', $cat)));
            $prefijo = substr($base, 0, 3);
            $max_n = 0; 
            $dig_len = 3;
        }

        $nuevo_num  = $max_n + 1;
        $codigo_gen = $prefijo . str_pad((string)$nuevo_num, $dig_len, '0', STR_PAD_LEFT);

        for ($i=0; $i<50; $i++) {
            $chk = $conexion->prepare("SELECT COUNT(*) FROM productos WHERE prod_codigo = ?");
            $chk->execute([$codigo_gen]);
            if ((int)$chk->fetchColumn() === 0) break;
            $nuevo_num++;
            $codigo_gen = $prefijo . str_pad((string)$nuevo_num, $dig_len, '0', STR_PAD_LEFT);
        }

        $venta = round($prov * 1.10, 2);
        $disp  = ($stock > 0) ? 1 : 0;

        $ins = $conexion->prepare(
            "INSERT INTO productos
             (prod_codigo, prod_categoria, prod_descripcion, prod_stock,
              prod_precio_proveedor, prod_precio_venta, prod_disponible)
             VALUES (:cod, :cat, :des, :stk, :prov, :venta, :disp)"
        );
        $ins->execute([
            ':cod' => $codigo_gen, ':cat' => $cat, ':des' => $desc, ':stk' => $stock,
            ':prov' => $prov, ':venta' => $venta, ':disp' => $disp
        ]);

        header("Location: productos.php?{$qs}&msg=nuevo_ok&nuevo_codigo=".urlencode($codigo_gen));
        exit;
    }
}

// ===================== LISTADO =====================
$where  = [];
$params = [];
if (!$incluir_no_disponibles) { $where[] = "prod_disponible = 1"; }
if ($codigo      !== '') { $where[] = "prod_codigo LIKE :codigo";             $params[':codigo']      = "%{$codigo}%"; }
if ($categoria   !== '') { $where[] = "prod_categoria LIKE :categoria";       $params[':categoria']   = "%{$categoria}%"; }
if ($descripcion !== '') { $where[] = "prod_descripcion LIKE :descripcion";   $params[':descripcion'] = "%{$descripcion}%"; }

$sql = "SELECT prod_id, prod_codigo, prod_categoria, prod_descripcion, prod_stock,
               prod_precio_proveedor, prod_precio_venta, prod_disponible
        FROM productos";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY prod_categoria ASC, prod_codigo ASC";
$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Categorías para el modal Nuevo
$stc = $conexion->query("SELECT DISTINCT prod_categoria FROM productos ORDER BY prod_categoria ASC");
$categorias = $stc->fetchAll(PDO::FETCH_COLUMN);

// ===================== MODAL VER (GET ?ver=ID) =====================
$verId = isset($_GET['ver']) ? (int)$_GET['ver'] : 0;
$productoVer = null;
if ($verId > 0) {
    $st = $conexion->prepare("SELECT * FROM productos WHERE prod_id = ?");
    $st->execute([$verId]);
    $productoVer = $st->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Productos – Panel Gerente</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
</head>
<body>

<?php if (($msg ?? '') === 'nuevo_ok'): ?>
  <dialog open>
    <p><strong>✅ Producto guardado correctamente.</strong><br>
       Código: <strong><?= e($_GET['nuevo_codigo'] ?? '') ?></strong>
    </p>
    <form method="get" action="productos.php">
      <?php if (!empty($codigo)): ?><input type="hidden" name="codigo" value="<?= e($codigo) ?>"><?php endif; ?>
      <?php if (!empty($categoria)): ?><input type="hidden" name="categoria" value="<?= e($categoria) ?>"><?php endif; ?>
      <?php if (!empty($descripcion)): ?><input type="hidden" name="descripcion" value="<?= e($descripcion) ?>"><?php endif; ?>
      <?php if ($incluir_no_disponibles): ?><input type="hidden" name="incluir_no_disponibles" value="1"><?php endif; ?>
      <button type="submit">Aceptar</button>
    </form>
  </dialog>
<?php endif; ?>

<div class="servicios_ger" >
    <div class="topbar">
        <br>
        <h2>Listado de Productos</h2>
        <?php if ($msg === 'incremento_ok'): ?><div>Incremento aplicado correctamente.</div><?php endif; ?>
        <?php if ($msg === 'eliminar_ok'): ?><div>Productos marcados como no disponibles.</div><?php endif; ?>
        <?php if ($msg === 'edit_ok'): ?><div>Producto modificado correctamente.</div><?php endif; ?>
        <?php if ($msg === 'sin_ids'): ?><div>Seleccioná al menos un producto.</div><?php endif; ?>
        <?php if ($msg === 'porcentaje_invalido'): ?><div>Porcentaje inválido.</div><?php endif; ?>
        <?php if ($msg === 'nuevo_ok'): ?>
            <div >Producto creado correctamente (código: <?= e($_GET['nuevo_codigo'] ?? '') ?>).</div>
        <?php elseif ($msg === 'nuevo_invalido'): ?>
            <div >Completá todos los campos requeridos.</div>
        <?php endif; ?>
    </div>

    <!-- Buscador -->
    <form method="get" class="buscador_produ_ger" action="productos.php" >
        <label for="codigo">Código</label>
        <input class="servicios_ger_input" type="text" id="codigo" name="codigo" value="<?= e($codigo) ?>" placeholder="Ej: LUB001" >

        <label for="categoria">Categoría</label>
        <input class="servicios_ger_input" type="text" id="categoria" name="categoria" value="<?= e($categoria) ?>" placeholder="Ej: Lubricantes" >

        <label for="descripcion">Descripción</label>
        <input class="servicios_ger_input" type="text" id="descripcion" name="descripcion" value="<?= e($descripcion) ?>" placeholder="Buscar descripción" >

        <label>
            <input class="servicios_ger_check" type="checkbox" name="incluir_no_disponibles" value="1" <?= $incluir_no_disponibles ? 'checked' : '' ?>>
            Incluir no disponibles
        </label>
        <div class="acciones_ger">
            <button  type="submit" name="buscar">Buscar</button>
            <a href="productos.php" >Limpiar</a>
            <a href="productos.php?<?= e($current_qs) ?>&nuevo=1" >Nuevo</a>
        </div>
    </form>

    <!-- FORM PRINCIPAL -->
    <form class="servicios_ger" id="formProductos" method="post" action="productos.php?<?= e($current_qs) ?>">
        <div class="acciones_ger" >
            <button name="accion" value="incrementar" >Incrementar precio</button>
            <button name="accion" value="eliminar" >Eliminar</button>
            <a href="gerente.php">Volver</a>
        </div>

        <table>
            <thead>
            <tr>
                <th >
                    <input class="servicios_ger_check" type="checkbox" id="check_all"
                        onclick="document.querySelectorAll('.check_row').forEach(c=>c.checked=this.checked);">
                </th>
                <th>Código</th>
                <th>Categoría</th>
                <th>Descripción</th>
                <th>Stock</th>
                <th>Prov.</th>
                <th>Venta</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$productos): ?>
                <tr><td style="border:1px solid #ddd; padding:8px;" colspan="9">No se encontraron productos con los filtros aplicados.</td></tr>
            <?php else: foreach ($productos as $p): 
                $noDisp = (int)$p['prod_disponible'] === 0 || (int)$p['prod_stock'] <= 0;
                $estadoTxt = $noDisp ? 'No disponible' : 'Disponible';
                $estadoCls = $noDisp ? 'nodisp' : 'disp';
                $verUrl = "productos.php?ver=".(int)$p['prod_id']."&".$current_qs;
            ?>
                <tr class="<?= $noDisp ? 'fila-nd' : '' ?>">
                    <td ><input class="check_row" type="checkbox" name="ids[]" value="<?= (int)$p['prod_id'] ?>"></td>
                    <td><?= e($p['prod_codigo']) ?></td>
                    <td><?= e($p['prod_categoria']) ?></td>
                    <td><?= e($p['prod_descripcion']) ?></td>
                    <td><?= number_format((int)$p['prod_stock'], 0, ',', '.') ?></td>
                    <td>$ <?= nfmt($p['prod_precio_proveedor']) ?></td>
                    <td>$ <?= nfmt($p['prod_precio_venta']) ?></td>
                    <td><span class="estado-tag <?= $estadoCls ?>"><?= $estadoTxt ?></span></td>
                    <td><a class="acciones_ger" href="<?= e($verUrl) ?>" title="Ver / Modificar">🔍</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </form>
</div>

<?php
// ===================== MODALES (todos con <dialog>) =====================

// Modal: Nuevo producto
if ($nuevo) {
    ?>
    <dialog open>
      <p><strong>Nuevo producto</strong></p>
      <form method="post" action="productos.php?<?= e($current_qs) ?>">
        <input type="hidden" name="accion" value="nuevo_guardar">
        <input type="hidden" name="f_codigo" value="<?= e($codigo) ?>">
        <input type="hidden" name="f_categoria" value="<?= e($categoria) ?>">
        <input type="hidden" name="f_descripcion" value="<?= e($descripcion) ?>">
        <?php if ($incluir_no_disponibles): ?><input type="hidden" name="f_incluir_no_disponibles" value="1"><?php endif; ?>

        <label>Categoría:</label>
        <select name="prod_categoria" style="min-width:280px;">
            <option value="" selected>Seleccionar existente...</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <span style="margin:0 8px; display:inline-block;">o</span>
        <input type="text" name="prod_categoria_nueva" placeholder="Nueva categoría" style="min-width:280px;">
        <p style="margin-top:6px;"><em>Si completás “Nueva categoría”, se usará esa y se generará el prefijo automáticamente.</em></p>
        <br>

        <label>Descripción:</label><br>
        <input type="text" name="prod_descripcion" maxlength="255" required style="min-width:420px;">
        <br><br>

        <label>Stock:</label>
        <input type="number" name="prod_stock" min="0" required>
        <br><br>

        <label>Precio proveedor:</label>
        <input type="number" step="1" min="0" name="prod_precio_proveedor" required>
        <p><em>El <b>código</b> se generará automáticamente según la categoría. El <b>precio de venta</b> será proveedor + 10%.</em></p>

        <div class="modal-actions">
            <button type="submit">Guardar</button>
            <a class="cancelar_boton" href="productos.php?<?= e($current_qs) ?>">Volver</a>
        </div>
      </form>
    </dialog>
    <?php
}

// Modal: Incrementar precio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'incrementar') {
    $ids = isset($_POST['ids']) ? array_filter((array)$_POST['ids'], 'is_numeric') : [];
    ?>
    <dialog open>
      <p><strong>Incrementar precio</strong></p>
      <?php if (empty($ids)): ?>
        <p>No seleccionaste productos.</p>
        <div class="modal-actions">
            <a class="cancelar_boton" href="productos.php?<?= e($current_qs) ?>">Volver</a>
        </div>
      <?php else: ?>
        <p>Seleccionados: <strong><?= count($ids) ?></strong> producto(s).</p>
        <form method="post" action="productos.php?<?= e($current_qs) ?>">
            <?php foreach ($ids as $id): ?><input type="hidden" name="ids[]" value="<?= (int)$id ?>"><?php endforeach; ?>
            <input type="hidden" name="f_codigo" value="<?= e($codigo) ?>">
            <input type="hidden" name="f_categoria" value="<?= e($categoria) ?>">
            <input type="hidden" name="f_descripcion" value="<?= e($descripcion) ?>">
            <?php if ($incluir_no_disponibles): ?><input type="hidden" name="f_incluir_no_disponibles" value="1"><?php endif; ?>
            <label>Porcentaje (%): </label>
            <input type="number" name="porcentaje" step="1" min="0" required>
            <div class="modal-actions">
                <button name="accion" value="incrementar_aplicar">Aplicar</button>
                <a class="cancelar_boton" href="productos.php?<?= e($current_qs) ?>">Cancelar</a>
            </div>
        </form>
      <?php endif; ?>
    </dialog>
    <?php
}

// Modal: Confirmar eliminación lógica
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $ids = isset($_POST['ids']) ? array_filter((array)$_POST['ids'], 'is_numeric') : [];
    ?>
    <dialog open>
      <p><strong>Eliminar productos (lógico)</strong></p>
      <?php if (empty($ids)): ?>
        <p>No seleccionaste productos.</p>
        <div class="modal-actions">
            <a class="cancelar_boton" href="productos.php?<?= e($current_qs) ?>">Volver</a>
        </div>
      <?php else: ?>
        <p>Se marcarán como <strong>No disponibles</strong> <b><?= count($ids) ?></b> producto(s).</p>
        <form method="post" action="productos.php?<?= e($current_qs) ?>">
            <?php foreach ($ids as $id): ?><input type="hidden" name="ids[]" value="<?= (int)$id ?>"><?php endforeach; ?>
            <input type="hidden" name="f_codigo" value="<?= e($codigo) ?>">
            <input type="hidden" name="f_categoria" value="<?= e($categoria) ?>">
            <input type="hidden" name="f_descripcion" value="<?= e($descripcion) ?>">
            <?php if ($incluir_no_disponibles): ?><input type="hidden" name="f_incluir_no_disponibles" value="1"><?php endif; ?>
            <div class="modal-actions">
                <button name="accion" value="eliminar_aplicar">Sí, eliminar</button>
                <a class="cancelar_boton" href="productos.php?<?= e($current_qs) ?>">Cancelar</a>
            </div>
        </form>
      <?php endif; ?>
    </dialog>
    <?php
}

// Modal: Ver/Editar producto
if ($productoVer) {
    ?>
    <dialog open>
      <p><strong>Producto <?= e($productoVer['prod_codigo']) ?></strong></p>
      <table style="width:100%;border-collapse:collapse;">
        <tr><th style="width:220px;">Código</th><td><?= e($productoVer['prod_codigo']) ?></td></tr>
        <tr><th>Categoría</th><td><?= e($productoVer['prod_categoria']) ?></td></tr>
        <tr><th>Descripción</th><td><?= e($productoVer['prod_descripcion']) ?></td></tr>
        <tr><th>Precio venta (actual)</th><td>$ <?= nfmt($productoVer['prod_precio_venta']) ?></td></tr>
      </table>
      <br>
      <form method="post" action="productos.php?<?= e($current_qs) ?>">
        <input type="hidden" name="accion" value="ver_guardar">
        <input type="hidden" name="id" value="<?= (int)$productoVer['prod_id'] ?>">
        <input type="hidden" name="f_codigo" value="<?= e($codigo) ?>">
        <input type="hidden" name="f_categoria" value="<?= e($categoria) ?>">
        <input type="hidden" name="f_descripcion" value="<?= e($descripcion) ?>">
        <?php if ($incluir_no_disponibles): ?><input type="hidden" name="f_incluir_no_disponibles" value="1"><?php endif; ?>

        <label>Estado:</label>
        <select name="prod_disponible">
            <option value="1" <?= $productoVer['prod_disponible'] ? 'selected':'' ?>>Disponible</option>
            <option value="0" <?= !$productoVer['prod_disponible'] ? 'selected':'' ?>>No disponible</option>
        </select>
        <br><br>

        <label>Stock:</label>
        <input type="number" name="prod_stock" min="0" value="<?= (int)$productoVer['prod_stock'] ?>" required>
        <br><br>

        <label>Precio proveedor:</label>
        <input type="number" step="1" min="0" name="prod_precio_proveedor" value="<?= e($productoVer['prod_precio_proveedor']) ?>" required>
        <p><em>El precio de venta se recalculará automáticamente (proveedor × 1.10).</em></p>

        <div class="modal-actions">
            <button type="submit">Guardar</button>
            <a class="cancelar_boton" href="productos.php?<?= e($current_qs) ?>">Volver</a>
        </div>
      </form>
    </dialog>
    <?php
}
?>
</body>
</html>

