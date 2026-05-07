<?php
require_once 'conexion_base.php';
require_once 'verificar_sesion_empleado.php';

$modalError = false;
$modalFinalizado = false;
$modalExito = false;
$mensajeModal = "";

$datosOrden = [];

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nfmt($n){ return number_format((float)$n, 2, ',', '.'); }

$abrirModalProductos = (isset($_GET['agregar']) && $_GET['agregar'] == '1');

$f_cod = trim($_GET['p_cod'] ?? '');
$f_cat = trim($_GET['p_cat'] ?? '');
$f_des = trim($_GET['p_des'] ?? '');

$listaProductos = [];
$itemsOrden = [];
$subtotalOrden = 0.0;

try {
    $ordenNum = trim($_GET['orden'] ?? '');

    if (empty($ordenNum)) {
        $modalError = true;
        $mensajeModal = "No se especificó el número de orden.";
    } else {
        // OBTENER DATOS DE ORDEN
        $stmt = $conexion->prepare(" SELECT o.orden_numero, o.vehiculo_patente, o.orden_fecha,
                   v.vehiculo_marca, v.vehiculo_modelo, v.vehiculo_anio,
                   s.servicio_nombre, ot.complejidad, ot.orden_kilometros,
                   ot.orden_comentario, ot.orden_estado
            FROM ordenes o
            JOIN vehiculos v ON o.vehiculo_patente = v.vehiculo_patente
            JOIN orden_trabajo ot ON o.orden_numero = ot.orden_numero
            JOIN servicios s ON ot.servicio_codigo = s.servicio_codigo
            WHERE o.orden_numero = :orden
        ");
        $stmt->execute(['orden' => $ordenNum]);

        if ($stmt->rowCount() === 0) {
            $modalError = true;
            $mensajeModal = "La orden no existe.";
        } else {
            $datosOrden = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($datosOrden['orden_estado'] == 1) {
                $modalFinalizado = true;
            }
        }
    }

    if (!$modalError && !$modalFinalizado && $_SERVER["REQUEST_METHOD"] === "POST") {

        // FINALIZAR ORDEN SI ENVIO FORMULARIO
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['finalizar'])) {
            $ordenNum = $_POST['orden_numero'];
            $complejidad = $_POST['complejidad'];
            $km = $_POST['orden_kilometros'];
            $comentario = $_POST['orden_comentario'];

            // VERIFICAR QUE KILOMETRAJE SEA NÚMERO ENTERO POSITIVO
            if (!ctype_digit($km)) {
                $modalError = true;
                $mensajeModal = "El kilometraje debe ser un número entero positivo.";
            } else {
                $km = (int)$km; // Asegura tipo entero

                // VERIFICAR QUE KILOMETRAJE NO SEA MENOR AL ANTERIOR
                $stmt = $conexion->prepare(" SELECT orden_kilometros FROM orden_trabajo
                    WHERE orden_numero = :orden
                ");
                $stmt->execute(['orden' => $ordenNum]);
                $kmActual = (int)($stmt->fetchColumn() ?? 0);

                if ($km < $kmActual) {
                    $modalError = true;
                    $mensajeModal = "El kilometraje ingresado ($km) no puede ser menor al actual ($kmActual).";
                }
            }   
             
            // SOLO ACTUALIZA SI NO HUBO ERRORES
            if (!$modalError) {
                $stmt = $conexion->prepare("UPDATE orden_trabajo
                    SET complejidad = :comp, orden_kilometros = :km,
                        orden_comentario = :comentario, orden_estado = 1
                    WHERE orden_numero = :orden
                ");
                $stmt->execute([
                    'comp' => $complejidad,
                    'km' => $km,
                    'comentario' => $comentario,
                    'orden' => $ordenNum
                ]);
                $modalExito = true;
                
            }
        }

        // AGREGAR PRODUCTOS (con control de stock, cantidades enteras y transacción)
        if (isset($_POST['accion']) && $_POST['accion'] === 'agregar_productos') {
            $ordenNum = (int)($_POST['orden_numero'] ?? 0);
            $mecanico = $_SESSION['empleado_DNI'] ?? $_SESSION['empleado_dni'] ?? '';

            $ids   = isset($_POST['prod_id']) && is_array($_POST['prod_id']) ? $_POST['prod_id'] : [];
            $cants = $_POST['cant'] ?? []; // cant[prod_id] => cantidad

            // Validaciones mínimas
            if ($ordenNum <= 0) {
                $modalError = true;
                $mensajeModal = "Orden inválida.";
                $abrirModalProductos = true;
            } elseif (empty($ids)) {
                $modalError = true;
                $mensajeModal = "No seleccionaste productos.";
                $abrirModalProductos = true;
            } else {
                try {
                    $conexion->beginTransaction();

                    $errores = [];
                    foreach ($ids as $idStr) {
                        $pid = (int)$idStr;

                        // Fuerza ENTEROS >= 1
                        $cantSolic = isset($cants[$pid]) ? (int)$cants[$pid] : 1;
                        if ($cantSolic < 1) { $cantSolic = 1; }

                        // Lock de fila producto
                        $st = $conexion->prepare("
                            SELECT prod_id, prod_codigo, prod_descripcion, prod_stock, prod_precio_venta
                            FROM productos
                            WHERE prod_id = ? AND prod_disponible = 1
                            FOR UPDATE
                        ");
                        $st->execute([$pid]);
                        $p = $st->fetch(PDO::FETCH_ASSOC);

                        if (!$p) {
                            $errores[] = "Producto ID $pid no disponible.";
                            continue;
                        }

                        $stockActual = (int)$p['prod_stock'];
                        if ($stockActual < $cantSolic) {
                            $errores[] = "Stock insuficiente para {$p['prod_codigo']} ({$p['prod_descripcion']}). Disponible: $stockActual, solicitado: $cantSolic.";
                            continue;
                        }

                        // Upsert a orden_productos (suma cantidades)
                        $upd = $conexion->prepare("
                            UPDATE orden_productos
                            SET cantidad = cantidad + :cant,
                                precio_unitario = :precio,
                                mecanico_DNI = :dni
                            WHERE orden_numero = :o AND prod_id = :pid
                        ");
                        $upd->execute([
                            ':cant'   => $cantSolic,
                            ':precio' => $p['prod_precio_venta'],
                            ':dni'    => $mecanico,
                            ':o'      => $ordenNum,
                            ':pid'    => $p['prod_id']
                        ]);

                        if ($upd->rowCount() === 0) {
                            $ins = $conexion->prepare("
                                INSERT INTO orden_productos
                                    (orden_numero, prod_id, prod_codigo, prod_descripcion, cantidad, precio_unitario, mecanico_DNI)
                                VALUES (:o,:pid,:cod,:des,:cant,:precio,:dni)
                            ");
                            $ins->execute([
                                ':o'      => $ordenNum,
                                ':pid'    => $p['prod_id'],
                                ':cod'    => $p['prod_codigo'],
                                ':des'    => $p['prod_descripcion'],
                                ':cant'   => $cantSolic,
                                ':precio' => $p['prod_precio_venta'],
                                ':dni'    => $mecanico
                            ]);
                        }

                        // Descuento de stock
                        $updStock = $conexion->prepare("
                            UPDATE productos
                            SET prod_stock = prod_stock - :cant
                            WHERE prod_id = :pid
                        ");
                        $updStock->execute([
                            ':cant' => $cantSolic,
                            ':pid'  => $p['prod_id']
                        ]);
                    }

                    if (!empty($errores)) {
                        // Si hubo al menos un error, NO aplicamos nada
                        $conexion->rollBack();
                        $modalError = true;
                        $mensajeModal = "No se pudieron agregar productos:\n- " . implode("\n- ", $errores);
                        $abrirModalProductos = true;
                    } else {
                        $conexion->commit();
                        header("Location: ordenes_pendientes.php?orden=" . urlencode((string)$ordenNum));
                        exit;
                    }
                } catch (Throwable $tx) {
                    if ($conexion->inTransaction()) $conexion->rollBack();
                    $modalError = true;
                    $mensajeModal = "Error al agregar productos: " . $tx->getMessage();
                    $abrirModalProductos = true;
                }
            }
        }
    }

    if (!$modalError && !$modalFinalizado && $datosOrden) {
        $st = $conexion->prepare("SELECT * FROM orden_productos WHERE orden_numero=? ORDER BY id ASC");
        $st->execute([$datosOrden['orden_numero']]);
        $itemsOrden = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach($itemsOrden as $it){ $subtotalOrden += (float)$it['cantidad']*(float)$it['precio_unitario']; }

        if ($abrirModalProductos) {
            $where = ["prod_disponible=1"];
            $params=[];
            if ($f_cod !== '') { $where[]="prod_codigo LIKE :c";     $params[':c']="%{$f_cod}%"; }
            if ($f_cat !== '') { $where[]="prod_categoria LIKE :g";  $params[':g']="%{$f_cat}%"; }
            if ($f_des !== '') { $where[]="prod_descripcion LIKE :d";$params[':d']="%{$f_des}%"; }
            $sql = "SELECT prod_id, prod_codigo, prod_categoria, prod_descripcion, prod_stock, prod_precio_venta
                    FROM productos";
            if ($where) $sql .= " WHERE ".implode(" AND ",$where);
            $sql .= " ORDER BY prod_categoria ASC, prod_codigo ASC";
            $st = $conexion->prepare($sql);
            $st->execute($params);
            $listaProductos = $st->fetchAll(PDO::FETCH_ASSOC);
        }
    }

} catch (PDOException $e) {
    $modalError = true;
    $mensajeModal = "Error en la base de datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden Pendiente</title>
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
<style>
 /* Centrado perfecto, por encima de todo */
       /* Centrado perfecto, por encima de todo */
        dialog.modal-prods{
            position: center !important;
            top: 50% !important; 
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            max-width: 980px !important;
            max-height: 90vh !important;
            margin: 0 !important; 
            padding: 0 !important;
            border: 1px solid #ccc !important; 
            border-radius: 10px !important;
            box-shadow: 0 20px 60px rgba(0,0,0,.35) !important;
            /*z-index: 999999;*/
            display: flex  !important;              /* header + body + footer*/ 
            flex-direction: column !important;
            color: #000 !important;
            overflow: hidden !important;           /* el scroll va en .modal-body */
        }
        dialog.modal-prods::backdrop{ background: rgba(255, 0, 0, 0.45); }
        .modal-header{ padding: 12px 16px; border-bottom: 1px solid #3a3f3a71; }
        .modal-body{ 
            padding: 12px 16px !important; 
            overflow: auto !important; 
            flex: 1 !important; 
            text-align: center !important;
        }
        .modal-footer{
            padding: 10px 16px;
            border-top: 1px solid #e5e5e5;
            background: #fafafa;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            align-items: center;
        }
        /*.modal-footer{
            padding: 10px 16px; border-top: 1px solid #e5e5e5; background: #fafafa;
            display: flex; gap: 8px; justify-content: flex-end;
        }*/
        /*.row-flex{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }*/
        .btn{ 
            display: inline-block !important; 
            padding:6px 12px !important; 
            border:1px solid #3AAFAF !important; 
            background-color: #3AAFAF !important;
            border-radius:4px !important; 
            cursor: pointer !important; 
            font-family: 'Big_Shoulders_Medium' !important;
            font-size: 16px !important;
        }
       .btn-prim{ 
            color: #000 !important; 
            width: 90px !important;
            height: 35px !important;
        }
        .btn-neu{ 
            color: #000 !important; 
            text-decoration:none !important; 
            text-align: center !important;
            width: 90px !important;
            height: 35px !important;
        
        
        }
        table.table{ width:100% !important; border-collapse:collapse !important;}
        .table th,.table td{ padding:6px !important; text-align:left !important; border-style: solid !important;  border-color:#00000036 !important;}
        /*.right{ text-align:right; }
        .subtle{ color:#666; font-size:13px; }*/

        /* (1) Estados de stock visuales */
        .row-low  { background: #ffd66dff !important;}  /* stock bajo */
        .row-zero { background: #ffd66dff !important ; }  /* sin stock  */
        .tag { display:inline-block !important ; padding:2px 8px !important; border-radius:12px !important; font-size:12px !important; }
        .tag-low  { background:#ffe8b3 !important; color:#8a5a00 !important; }
        .tag-zero { background:#f8c7c3 !important; color:#8a1f12 !important; }

        /* (2) Inputs/checkbox deshabilitados visibles */
        .table input[disabled], .table input:disabled {
            background:#f5f5f5; color:#777; cursor:not-allowed;
        }    
</style>

</head>
<body>
    <?php include("nav_mecanico.php"); ?>
    <br>

    <!-- MODAL ERROR -->
    <?php if ($modalError): ?>
    <dialog open>
        <p style="text-align:center;"><strong><?= htmlspecialchars($mensajeModal) ?></strong></p>
        <div style="text-align:center;"><button onclick="this.closest('dialog').close()">Cerrar</button></div>
    </dialog>

    <!-- MODAL FINALIZADO -->
    <?php elseif ($modalFinalizado): ?>
    <dialog open>
        <p style="text-align:center;"><strong>La orden ya fue finalizada.</strong></p>
        <div style="text-align:center;"><button onclick="this.closest('dialog').close()">Cerrar</button></div>
    </dialog>

    <!-- MODAL EXITO -->
    <?php elseif ($modalExito): ?>
    <dialog open>
        <p style="text-align:center;"><strong>Orden Nº <?= htmlspecialchars($ordenNum) ?> finalizada con éxito.</strong></p>
        <form method="get" action="mecanico.php" style="text-align:center;">
            <button type="submit">Volver</button>
        </form>
    </dialog>
    <?php endif; ?>

    <?php if (!empty($datosOrden) && !$modalFinalizado): ?>
    <section class="orden_prendiente">
        <br><br>
        <h2 class="recepcion_titulos">
            <?= htmlspecialchars($datosOrden['vehiculo_patente']) ?> - 
            <?= htmlspecialchars($datosOrden['vehiculo_marca']) ?> - 
            <?= htmlspecialchars($datosOrden['vehiculo_modelo']) ?> - 
            <?= htmlspecialchars($datosOrden['vehiculo_anio']) ?>
        </h2>

        <form method="post" class="form_ordenen">
            <input type="hidden" name="orden_numero" value="<?= htmlspecialchars($datosOrden['orden_numero']) ?>">
            <h2>Servicio</h2> 
            <p> <?= htmlspecialchars($datosOrden['servicio_nombre']) ?></p>
            <br>
            <div class="orden_pediente_prod">
                <h4>Orden N° <?= e($datosOrden['orden_numero']) ?> —
                Subtotal productos: $ <?= nfmt($subtotalOrden) ?></h4>
            <?php if ($itemsOrden): ?>
                <h4>Items: <?= count($itemsOrden) ?></h4>
            <?php endif; ?>
        </div>

        <!-- BOTONES DE ACCIÓN DE ORDEN -->
        <div class="btn_agregarprod">
            <!-- ABRIR MODAL: Agregar productos -->
            <a href="ordenes_pendientes.php?orden=<?= e($datosOrden['orden_numero']) ?>&agregar=1">
                🧰 Agregar productos 
            </a>
        </div>

            <br><br>
            <label for="complejidad">Complejidad</label>
            <select name="complejidad" id="complejidad" class="orden_compl">
                <option value="1" <?= $datosOrden['complejidad'] == 1 ? 'selected' : '' ?>>Baja (1)</option>
                <option value="2" <?= $datosOrden['complejidad'] == 2 ? 'selected' : '' ?>>Media (2)</option>
                <option value="3" <?= $datosOrden['complejidad'] == 3 ? 'selected' : '' ?>>Alta (3)</option>
            </select>
            <br><br>

            <label for="orden_kilometros">Kilometraje</label>
            <input type="text" name="orden_kilometros" id="orden_kilometros" class="kil_form1" value="<?= htmlspecialchars($datosOrden['orden_kilometros']) ?>" required>

            <br><br>
            <label for="orden_comentario"> Comentario</label>
            <br>
            <textarea name="orden_comentario" id="orden_comentario" style="height: 103px; width: 1231px; margin-left: 5%;"><?= htmlspecialchars($datosOrden['orden_comentario']) ?></textarea>

            <br><br>
            
            <div class="bot_orden">
                <input class="orden_fin" type="submit" value="Finalizar" name="finalizar">
                <a href="exportar_pdf_orden.php?orden=<?= htmlspecialchars($datosOrden['orden_numero']) ?>" 
                   target="_blank" class="imprimir_ordpen">🖨️ Imprimir</a>
            </div>
        </form>
    </section>
    <?php endif; ?>
    <br>
    <?php include("piedepagina.php"); ?>
    <script src="control_inactividad.js"></script>

    <?php if ($abrirModalProductos && !$modalError && !$modalFinalizado && $datosOrden): ?>
    <dialog open class="modal-prods">
      <div class="modal-header">
        <h3 style="margin:0; text-align:center;">
          Agregar productos — Orden <?= e($datosOrden['orden_numero']) ?>
        </h3>
      </div>

      <div class="modal-body">
        <!-- FILTROS -->
        <form method="get" class="row-flex" action="ordenes_pendientes.php" style="margin:6px 0 10px;">
          <input type="hidden" name="orden" value="<?= e($datosOrden['orden_numero']) ?>">
          <input type="hidden" name="agregar" value="1">
          <input type="text" name="p_cod" placeholder="Código" value="<?= e($f_cod) ?>">
          <input type="text" name="p_cat" placeholder="Categoría" value="<?= e($f_cat) ?>">
          <input type="text" name="p_des" placeholder="Descripción" value="<?= e($f_des) ?>">
          <button class="btn btn-prim" type="submit">Buscar</button>
          <a class="btn btn-neu" href="ordenes_pendientes.php?orden=<?= e($datosOrden['orden_numero']) ?>">Limpiar</a>
        </form>

        <!-- LISTA MULTISELECCIÓN -->
        <form id="formProds" method="post" action="ordenes_pendientes.php?orden=<?= e($datosOrden['orden_numero']) ?>">
          <input type="hidden" name="accion" value="agregar_productos">
          <input type="hidden" name="orden_numero" value="<?= e($datosOrden['orden_numero']) ?>">

          <table class="table">
            <thead>
              <tr>
                <th style="width:28px;">
                  <!-- (3) Select-all solo habilitados -->
                  <input type="checkbox" id="ck_all_modal"
                    onclick="document.querySelectorAll('.ckp:not(:disabled)').forEach(x=>x.checked=this.checked)">
                </th>
                <th>Código</th>
                <th>Categoría</th>
                <th>Descripción</th>
                <th class="right">Stock</th>
                <th class="right">P. Venta</th>
                <th class="right" style="width:120px;">Cantidad</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$listaProductos): ?>
                <tr><td colspan="7" class="subtle">No hay resultados con el filtro actual.</td></tr>
              <?php else: foreach ($listaProductos as $p):
                    $stock = (int)$p['prod_stock'];
                    $rowCls = ($stock <= 0) ? 'row-zero' : (($stock < 3) ? 'row-low' : '');
                    $disabled = ($stock <= 0) ? 'disabled' : '';
                    $titleCk  = ($stock <= 0) ? 'title="Sin stock"' : 'title="Seleccionar"';
                ?>
                <tr class="<?= $rowCls ?>">
                  <td>
                    <input class="ckp" type="checkbox" name="prod_id[]" value="<?= (int)$p['prod_id'] ?>" <?= $disabled ?> <?= $titleCk ?>>
                  </td>
                  <td><?= e($p['prod_codigo']) ?></td>
                  <td><?= e($p['prod_categoria']) ?></td>
                  <td>
                    <?= e($p['prod_descripcion']) ?>
                    <?php if ($stock <= 0): ?>
                      &nbsp;<span class="tag tag-zero">Sin stock</span>
                    <?php elseif ($stock < 6): ?>
                      &nbsp;<span class="tag tag-low">Stock bajo</span>
                    <?php endif; ?>
                  </td>
                  <td class="right"><?= $stock ?></td>
                  <td class="right">$ <?= nfmt($p['prod_precio_venta']) ?></td>
                  <td class="right">
                    <input
                      type="number"
                      name="cant[<?= (int)$p['prod_id'] ?>]"
                      step="1"
                      min="1"
                      value="1"
                      style="width:90px;"
                      inputmode="numeric"
                      pattern="\d+"
                      oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                      <?= $disabled ?>
                      title="<?= ($stock <= 0) ? 'Sin stock' : 'Cantidad' ?>"
                    />
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </form>
      </div>

      <div class="modal-footer">
        <a class="btn btn-neu" href="ordenes_pendientes.php?orden=<?= e($datosOrden['orden_numero']) ?>">Volver</a>
        <button class="btn btn-prim" type="submit" form="formProds">Agregar</button>
      </div>
    </dialog>
    <?php endif; ?>

    <!-- (4) Script opcional para el select-all (no rompe el inline) -->
    <script>
      (function(){
        const ckAll = document.getElementById('ck_all_modal');
        if (ckAll) {
          ckAll.addEventListener('click', () => {
            document.querySelectorAll('.ckp').forEach(x => {
              if (!x.disabled) x.checked = ckAll.checked;
            });
          });
        }
      })();
    </script>
</body>
</html>

