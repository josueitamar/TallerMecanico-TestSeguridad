<?php
session_start();

require_once 'conexion_base.php';
require_once 'verificar_sesion_empleado.php';

// PHPMailer (para mails de bienvenida y baja)
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ===================== Helpers ===================== */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ntitle($s){
    $s = mb_strtolower(trim($s), 'UTF-8');
    return preg_replace_callback('/\b\p{L}+/u', function($m){
        return mb_convert_case($m[0], MB_CASE_TITLE, 'UTF-8');
    }, $s);
}
function lower($s){ return mb_strtolower(trim((string)$s), 'UTF-8'); }

function filtros_qs($dni,$nombre,$email,$roll,$estado,$incluir_bajas){
    $arr = [];
    if ($dni   !== '') $arr['dni'] = $dni;
    if ($nombre!== '') $arr['nombre'] = $nombre;
    if ($email !== '') $arr['email'] = $email;
    if ($roll  !== '') $arr['roll'] = $roll;
    if ($estado!== '') $arr['estado'] = $estado;
    if ($incluir_bajas) $arr['incluir_bajas']=1;
    return http_build_query($arr);
}

// Envío mail helper
function enviar_mail($paraEmail, $paraNombre, $asunto, $html){
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'wasporttaller@gmail.com';
    $mail->Password = 'gdkwryakgynsewdl';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('wasporttaller@gmail.com', 'WA SPORT');
    $mail->addAddress($paraEmail, $paraNombre ?: 'Empleado');

    $mail->isHTML(true);
    $mail->Subject = $asunto;
    $mail->Body    = $html;
    $mail->AltBody = strip_tags(str_replace(['<br>','<br/>','<br />'],"\n",$html));
    $mail->send();
}

/* ===================== Filtros (GET) ===================== */
$dni    = isset($_GET['dni'])    ? trim($_GET['dni'])    : '';
$nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';
$email  = isset($_GET['email'])  ? trim($_GET['email'])  : '';
$roll   = isset($_GET['roll'])   ? trim($_GET['roll'])   : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$incluir_bajas = isset($_GET['incluir_bajas']) ? 1 : 0;

$nuevo  = isset($_GET['nuevo']) ? 1 : 0; // abrir modal Nuevo
$msg    = $_GET['msg'] ?? '';

$current_qs = filtros_qs($dni,$nombre,$email,$roll,$estado,$incluir_bajas);

/* ===================== Acciones (POST) ===================== */
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $accion = $_POST['accion'] ?? '';

    // Persistir filtros en vuelta
    $f_dni    = $_POST['f_dni']    ?? $dni;
    $f_nombre = $_POST['f_nombre'] ?? $nombre;
    $f_email  = $_POST['f_email']  ?? $email;
    $f_roll   = $_POST['f_roll']   ?? $roll;
    $f_estado = $_POST['f_estado'] ?? $estado;
    $f_incb   = isset($_POST['f_incluir_bajas']) ? 1 : $incluir_bajas;
    $qs = filtros_qs($f_dni,$f_nombre,$f_email,$f_roll,$f_estado,$f_incb);

    $ids = isset($_POST['ids']) ? array_filter((array)$_POST['ids'], static fn($x)=>strlen(trim($x))>0) : [];

    if ($accion === 'ver_guardar'){
        $dniKey    = trim((string)($_POST['empleado_DNI'] ?? ''));
        if ($dniKey===''){ header("Location: empleados.php?{$qs}&msg=edit_fail"); exit; }

        $emailE    = lower($_POST['empleado_email'] ?? '');
        $rollE     = lower($_POST['empleado_roll'] ?? '');
        $dirE      = ntitle($_POST['empleado_direccion'] ?? '');
        $locE      = ntitle($_POST['empleado_localidad'] ?? '');
        $telE      = trim($_POST['empleado_telefono'] ?? '');
        $estadoE   = $_POST['empleado_estado'] ?? 'disponible';
        $licDesde  = !empty($_POST['licencia_desde']) ? $_POST['licencia_desde'] : null;
        $licHasta  = !empty($_POST['licencia_hasta']) ? $_POST['licencia_hasta'] : null;
        $nuevaPass = $_POST['nueva_contrasena'] ?? '';

        if (!in_array($estadoE, ['disponible','no_disponible','licencia','baja'], true)){
            header("Location: empleados.php?{$qs}&msg=edit_fail"); exit;
        }
        if ($estadoE==='licencia'){
            if (!$licDesde || !$licHasta || $licDesde > $licHasta){
                header("Location: empleados.php?{$qs}&msg=lic_invalida"); exit;
            }
        } else {
            $licDesde = $licHasta = null;
        }

        $habilitado = ($estadoE==='baja') ? 0 : 1;

        try{
            $conexion->beginTransaction();
            if ($nuevaPass!==''){
                if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/", $nuevaPass)){
                    $conexion->rollBack();
                    header("Location: empleados.php?{$qs}&msg=pass_bad"); exit;
                }
                $hash = password_hash($nuevaPass, PASSWORD_DEFAULT);
                $st = $conexion->prepare("
                    UPDATE empleados
                       SET empleado_email=:email, empleado_roll=:roll,
                           empleado_direccion=:dir, empleado_localidad=:loc, empleado_telefono=:tel,
                           empleado_estado=:est, licencia_desde=:ld, licencia_hasta=:lh,
                           empleado_habilitado=:hab, empleado_contrasena=:pass
                     WHERE empleado_DNI=:dni
                ");
                $st->execute([
                    ':email'=>$emailE, ':roll'=>$rollE, ':dir'=>$dirE, ':loc'=>$locE, ':tel'=>$telE,
                    ':est'=>$estadoE, ':ld'=>$licDesde, ':lh'=>$licHasta, ':hab'=>$habilitado,
                    ':pass'=>$hash, ':dni'=>$dniKey
                ]);
            } else {
                $st = $conexion->prepare("
                    UPDATE empleados
                       SET empleado_email=:email, empleado_roll=:roll,
                           empleado_direccion=:dir, empleado_localidad=:loc, empleado_telefono=:tel,
                           empleado_estado=:est, licencia_desde=:ld, licencia_hasta=:lh,
                           empleado_habilitado=:hab
                     WHERE empleado_DNI=:dni
                ");
                $st->execute([
                    ':email'=>$emailE, ':roll'=>$rollE, ':dir'=>$dirE, ':loc'=>$locE, ':tel'=>$telE,
                    ':est'=>$estadoE, ':ld'=>$licDesde, ':lh'=>$licHasta, ':hab'=>$habilitado,
                    ':dni'=>$dniKey
                ]);
            }
            $conexion->commit();

            if ($estadoE==='baja'){
                try{
                    $stE = $conexion->prepare("SELECT empleado_nombre FROM empleados WHERE empleado_DNI=?");
                    $stE->execute([$dniKey]);
                    $nom = $stE->fetchColumn() ?: 'Empleado';

                    $html = "Hola <strong>".e($nom)."</strong>,<br><br>
                    Te informamos que ya no formás parte de <strong>WA SPORT</strong> y tu usuario ha sido inhabilitado.<br><br>
                    Saludos.";
                    enviar_mail($emailE, $nom, 'Notificación de baja - WA SPORT', $html);
                } catch (Exception $ex){}
            }

            header("Location: empleados.php?{$qs}&msg=edit_ok"); exit;

        } catch (Exception $ex){
            if ($conexion->inTransaction()) $conexion->rollBack();
            header("Location: empleados.php?{$qs}&msg=edit_fail"); exit;
        }
    }

    if ($accion === 'baja_aplicar'){
        if (empty($ids)){ header("Location: empleados.php?{$qs}&msg=sin_ids"); exit; }
        try{
            $conexion->beginTransaction();
            $place = implode(',', array_fill(0,count($ids),'?'));
            $sql = "UPDATE empleados
                       SET empleado_estado='baja', empleado_habilitado=0
                     WHERE empleado_DNI IN ($place)";
            $st  = $conexion->prepare($sql);
            $st->execute($ids);

            $conexion->commit();
            try{
                $st2 = $conexion->prepare("SELECT empleado_nombre, empleado_email FROM empleados WHERE empleado_DNI IN ($place)");
                $st2->execute($ids);
                foreach ($st2->fetchAll(PDO::FETCH_ASSOC) as $row){
                    try{
                        $html = "Hola <strong>".e($row['empleado_nombre'])."</strong>,<br><br>
                        Te informamos que ya no formás parte de <strong>WA SPORT</strong> y tu usuario ha sido inhabilitado.<br><br>
                        Saludos.";
                        enviar_mail($row['empleado_email'], $row['empleado_nombre'], 'Notificación de baja - WA SPORT', $html);
                    } catch (Exception $ex){}
                }
            } catch (Exception $ex){}

            header("Location: empleados.php?{$qs}&msg=baja_ok"); exit;

        } catch (Exception $ex){
            if ($conexion->inTransaction()) $conexion->rollBack();
            header("Location: empleados.php?{$qs}&msg=baja_fail"); exit;
        }
    }

    if ($accion === 'nuevo_guardar'){
        $dniN  = trim((string)($_POST['empleado_DNI'] ?? ''));
        $nomN  = ntitle($_POST['empleado_nombre'] ?? '');
        $emailN= lower($_POST['empleado_email'] ?? '');
        $rollN = lower($_POST['empleado_roll'] ?? '');
        $dirN  = ntitle($_POST['empleado_direccion'] ?? '');
        $locN  = ntitle($_POST['empleado_localidad'] ?? '');
        $telN  = trim($_POST['empleado_telefono'] ?? '');
        $estN  = $_POST['empleado_estado'] ?? 'disponible';
        $ldN   = !empty($_POST['licencia_desde']) ? $_POST['licencia_desde'] : null;
        $lhN   = !empty($_POST['licencia_hasta']) ? $_POST['licencia_hasta'] : null;

        if ($dniN==='' || $nomN==='' || $emailN==='' || $rollN===''){
            header("Location: empleados.php?{$current_qs}&msg=nuevo_invalido&nuevo=1"); exit;
        }
        if (!in_array($estN, ['disponible','no_disponible','licencia','baja'], true)){
            header("Location: empleados.php?{$current_qs}&msg=nuevo_invalido&nuevo=1"); exit;
        }
        if ($estN==='licencia'){
            if (!$ldN || !$lhN || $ldN>$lhN){
                header("Location: empleados.php?{$current_qs}&msg=lic_invalida&nuevo=1"); exit;
            }
        } else {
            $ldN = $lhN = null;
        }
        $habN = ($estN==='baja') ? 0 : 1;

        try{
            $chk = $conexion->prepare("SELECT COUNT(*) FROM empleados WHERE empleado_DNI = ?");
            $chk->execute([$dniN]);
            if ((int)$chk->fetchColumn() > 0){
                header("Location: empleados.php?{$current_qs}&msg=dni_dup&nuevo=1"); exit;
            }

            $tempPlain = bin2hex(random_bytes(6));
            $tempHash  = password_hash($tempPlain, PASSWORD_DEFAULT);
            $token     = bin2hex(random_bytes(32));

            $ins = $conexion->prepare("
                INSERT INTO empleados
                (empleado_DNI, empleado_contrasena, empleado_nombre, empleado_roll, empleado_email,
                 token_recuperacion, empleado_direccion, empleado_localidad, empleado_telefono,
                 empleado_habilitado, empleado_estado, licencia_desde, licencia_hasta)
                VALUES
                (:dni,:pass,:nom,:rol,:email,:tok,:dir,:loc,:tel,:hab,:est,:ld,:lh)
            ");
            $ins->execute([
                ':dni'=>$dniN, ':pass'=>$tempHash, ':nom'=>$nomN, ':rol'=>$rollN, ':email'=>$emailN,
                ':tok'=>$token, ':dir'=>$dirN, ':loc'=>$locN, ':tel'=>$telN,
                ':hab'=>$habN, ':est'=>$estN, ':ld'=>$ldN, ':lh'=>$lhN
            ]);

            $link = "http://localhost/tallermecanico/restablecer_contrasena_empleado.php?token={$token}";
            $html = "Hola <strong>".e($nomN)."</strong>,<br><br>
            ¡Bienvenido/a a <strong>WA SPORT</strong>!<br>
            Podés definir tu contraseña haciendo clic en el siguiente enlace:<br><br>
            <a href='{$link}'>{$link}</a><br><br>
            Si no reconocés este alta, por favor contactanos.";

            try{ enviar_mail($emailN, $nomN, 'Bienvenida - WA SPORT', $html); } catch (Exception $ex){}

            header("Location: empleados.php?{$current_qs}&msg=nuevo_ok&nuevo_codigo=".urlencode($dniN)); exit;

        } catch (Exception $ex){
            header("Location: empleados.php?{$current_qs}&msg=nuevo_fail&nuevo=1"); exit;
        }
    }
}

/* ===================== Listado ===================== */
$where=[]; $params=[];
if ($dni    !== '') { $where[]="empleado_DNI LIKE :dni";       $params[':dni'] = "%{$dni}%"; }
if ($nombre !== '') { $where[]="empleado_nombre LIKE :nom";     $params[':nom'] = "%".mb_strtoupper($nombre,'UTF-8')."%"; }
if ($email  !== '') { $where[]="empleado_email LIKE :mail";     $params[':mail']="%{$email}%"; }
if ($roll   !== '') { $where[]="empleado_roll = :roll";         $params[':roll']= lower($roll); }
if ($estado !== '') { $where[]="empleado_estado = :estado";     $params[':estado']=$estado; }
if (!$incluir_bajas){ $where[]="empleado_estado <> 'baja'"; }

$sql = "SELECT empleado_DNI, empleado_nombre, empleado_roll, empleado_email,
               empleado_direccion, empleado_localidad, empleado_telefono,
               empleado_habilitado, empleado_estado, licencia_desde, licencia_hasta
        FROM empleados";
if ($where) $sql .= " WHERE ".implode(' AND ',$where);
$sql .= " ORDER BY empleado_nombre ASC, empleado_DNI ASC";
$st = $conexion->prepare($sql);
$st->execute($params);
$empleados = $st->fetchAll(PDO::FETCH_ASSOC);

// Modal VER (GET ?ver=DNI)
$verDni = isset($_GET['ver']) ? trim($_GET['ver']) : '';
$empVer = null;
if ($verDni!==''){
    $st2 = $conexion->prepare("SELECT * FROM empleados WHERE empleado_DNI = ?");
    $st2->execute([$verDni]);
    $empVer = $st2->fetch(PDO::FETCH_ASSOC);
}

/* ===================== Roles existentes (DISTINCT) ===================== */
$roles = [];
try {
    $rs = $conexion->query("SELECT DISTINCT empleado_roll FROM empleados WHERE empleado_roll IS NOT NULL AND empleado_roll<>'' ORDER BY empleado_roll ASC");
    $roles = $rs->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (Exception $e) { $roles = []; }

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Empleados – Panel Gerente</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
</head>
<body>
    <?php include("nav_gerente.php"); ?>
<div class="ger_empleados">
  <br>
    <h2>Empleados</h2>
    <br>
    <div class="topbar">
        <?php if ($msg==='edit_ok'): ?><div style="color:#1e824c;">Empleado actualizado.</div><?php endif; ?>
        <?php if ($msg==='edit_fail'): ?><div style="color:#c0392b;">No se pudo actualizar.</div><?php endif; ?>
        <?php if ($msg==='pass_bad'): ?><div style="color:#c0392b;">La nueva contraseña no cumple requisitos.</div><?php endif; ?>
        <?php if ($msg==='baja_ok'): ?><div style="color:#1e824c;">Baja aplicada e inhabilitada.</div><?php endif; ?>
        <?php if ($msg==='baja_fail'): ?><div style="color:#c0392b;">No se pudo aplicar la baja.</div><?php endif; ?>
        <?php if ($msg==='nuevo_ok'): ?><div style="color:#1e824c;">Empleado creado y mail de bienvenida enviado.</div><?php endif; ?>
        <?php if ($msg==='nuevo_fail'): ?><div style="color:#c0392b;">No se pudo crear el empleado.</div><?php endif; ?>
        <?php if ($msg==='nuevo_invalido'): ?><div style="color:#c0392b;">Completá DNI, nombre, email y rol.</div><?php endif; ?>
        <?php if ($msg==='lic_invalida'): ?><div style="color:#c0392b;">Rango de licencia inválido.</div><?php endif; ?>
        <?php if ($msg==='dni_dup'): ?><div style="color:#c0392b;">El DNI ya existe.</div><?php endif; ?>
    </div>

    <!-- Buscador -->
    <form method="get" action="empleados.php">
        <label>DNI:</label>
        <input class="ger_empleados_input" type="text" name="dni" value="<?= e($dni) ?>" placeholder="Ej: 12345678">
        <label>Nombre:</label>
        <input class="ger_empleados_input" type="text" name="nombre" value="<?= e($nombre) ?>" placeholder="Ej: Juan Perez">
        <label>Email:</label>
        <input class="ger_empleados_input" type="text" name="email" value="<?= e($email) ?>" placeholder="mail@...">
        <label>Rol:</label>
        <select class="ger_empleado_sel" name="roll">
            <option value="" <?= $roll===''?'selected':'' ?>>—</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= e($r) ?>" <?= ($r===$roll?'selected':'') ?>><?= e($r) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Estado:</label>
        <select  class="ger_empleado_sel" name="estado">
            <option value="" <?= $estado===''?'selected':'' ?>>—</option>
            <option value="disponible"     <?= $estado==='disponible'?'selected':'' ?>>Disponible</option>
            <option value="no_disponible"  <?= $estado==='no_disponible'?'selected':'' ?>>No disponible</option>
            <option value="licencia"       <?= $estado==='licencia'?'selected':'' ?>>Licencia</option>
            <option value="baja"           <?= $estado==='baja'?'selected':'' ?>>Baja</option>
        </select>
        <br>
        <div class="ger_empleados_btn">
          <label>
              <input class="ger_empleados_check " type="checkbox" name="incluir_bajas" value="1" <?= $incluir_bajas?'checked':'' ?>>
              Incluir bajas
          </label>
        
          <button type="submit">Buscar</button>
          <a href="empleados.php">Limpiar</a>
          <a href="empleados.php?<?= e($current_qs) ?>&nuevo=1">Nuevo</a>
          <a href="gerente.php">Volver</a>
        </div>
    </form>

    <!-- Botones Exportar -->
    <?php $export_qs = $current_qs !== '' ? $current_qs.'&' : ''; ?>
    <div class="ger_empleados_btn" >
      <a href="empleados_export.php?<?= e($export_qs) ?>format=csv">Exportar CSV</a>
      <a href="empleados_export.php?<?= e($export_qs) ?>format=pdf" target="_blank">Exportar PDF / Imprimir</a>
    </div>

    <!-- Form principal: acciones en lote -->
    <form class="ger_empleados_table" id="formEmpleados" method="post" action="empleados.php?<?= e($current_qs) ?>">
        <div class="ger_empleados_baja">
            <button name="accion" value="baja">Dar de Baja (inhabilitar)</button>
        </div>
        <br>
        <table >
            <thead>
            <tr>
                <th>
                    <input type="checkbox"
                      onclick="document.querySelectorAll('input[name=&quot;ids[]&quot;]').forEach(c=>c.checked=this.checked);">
                </th>
                <th>DNI</th>
                <th>Nombre</th>
                <th>Rol</th>
                <th>Email</th>
                <th>Estado</th>
                <th>Licencia</th>
                <th>Teléfono</th>
                <th style="width:90px;">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$empleados): ?>
                <tr><td colspan="9">Sin resultados.</td></tr>
            <?php else: foreach ($empleados as $r):
                $cls = ($r['empleado_estado']==='baja') ? 'fila-baja' : '';
                $tagCls = 'est-disponible';
                if ($r['empleado_estado']==='no_disponible') $tagCls='est-no_disp';
                elseif ($r['empleado_estado']==='licencia') $tagCls='est-lic';
                elseif ($r['empleado_estado']==='baja') $tagCls='est-baja';
                $licTxt = ($r['empleado_estado']==='licencia')
                        ? (e($r['licencia_desde'])." a ".e($r['licencia_hasta']))
                        : '—';
                $verUrl = "empleados.php?ver=".urlencode($r['empleado_DNI'])."&".$current_qs;
            ?>
                <tr class="<?= $cls ?>">
                    <td><input type="checkbox" name="ids[]" value="<?= e($r['empleado_DNI']) ?>"></td>
                    <td><?= e($r['empleado_DNI']) ?></td>
                    <td><?= e($r['empleado_nombre']) ?></td>
                    <td><?= e($r['empleado_roll']) ?></td>
                    <td><?= e($r['empleado_email']) ?></td>
                    <td><span class="estado-tag <?= $tagCls ?>"><?= e($r['empleado_estado']) ?></span></td>
                    <td><?= $licTxt ?></td>
                    <td><?= e($r['empleado_telefono']) ?></td>
                    <td><a class="btn_serv_ger" href="<?= e($verUrl) ?>" title="Ver / Editar">🔍</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>

        <input type="hidden" name="accion" id="accionHidden" value="">
    </form>
</div>

<?php
/* ===================== MODALES (UNIFICADOS A <dialog>) ===================== */

// Modal: Nuevo
if ($nuevo){ ?>
  <dialog open>
    <p><strong>Nuevo empleado</strong></p>
    <form method="post" action="empleados.php?<?= e($current_qs) ?>">
      <input type="hidden" name="accion" value="nuevo_guardar">
      <input type="hidden" name="f_dni"    value="<?= e($dni) ?>">
      <input type="hidden" name="f_nombre" value="<?= e($nombre) ?>">
      <input type="hidden" name="f_email"  value="<?= e($email) ?>">
      <input type="hidden" name="f_roll"   value="<?= e($roll) ?>">
      <input type="hidden" name="f_estado" value="<?= e($estado) ?>">
      <?php if ($incluir_bajas): ?><input type="hidden" name="f_incluir_bajas" value="1"><?php endif; ?>

      <table style="width:100%;border-collapse:collapse;">
        <tr><th style="width:200px;">DNI *</th><td><input type="text" name="empleado_DNI" required></td></tr>
        <tr><th>Nombre *</th><td><input type="text" name="empleado_nombre" required placeholder="Juan Perez"></td></tr>
        <tr><th>Email *</th><td><input type="email" name="empleado_email" required placeholder="falso_mail@mail.com"></td></tr>
        <tr>
          <th>Rol *</th>
          <td>
            <select name="empleado_roll" required>
              <?php foreach ($roles as $r): ?>
                <option value="<?= e($r) ?>"><?= e($r) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr><th>Dirección</th><td><input type="text" name="empleado_direccion" placeholder="Calle Falsa 123"></td></tr>
        <tr><th>Localidad</th><td><input type="text" name="empleado_localidad" placeholder="Caba"></td></tr>
        <tr><th>Teléfono</th><td><input type="text" name="empleado_telefono"></td></tr>
        <tr>
          <th>Estado</th>
          <td>
            <select name="empleado_estado" id="nuevo_estado" onchange="toggleLicencia(this.value, 'nuevo_lic')">
              <option value="disponible" selected>disponible</option>
              <option value="no_disponible">no_disponible</option>
              <option value="licencia">licencia</option>
              <option value="baja">baja</option>
            </select>
            <div id="nuevo_lic" class="small" style="margin-top:8px; display:none;">
              Desde: <input type="date" name="licencia_desde">
              Hasta: <input type="date" name="licencia_hasta">
            </div>
          </td>
        </tr>
      </table>
      <p class="small">* Se enviará un mail de bienvenida con el link para definir contraseña.</p>

      <div class="modal-actions">
        <button type="submit">Guardar</button>
        <a href="empleados.php?<?= e($current_qs) ?>" class="cancelar_boton">Volver</a>
      </div>
    </form>
  </dialog>
<?php }

// Modal: Baja lógica (confirmación)
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion'] ?? '')==='baja'){
    $ids = isset($_POST['ids']) ? array_filter((array)$_POST['ids']) : []; ?>
    <dialog open>
      <p><strong>Baja lógica</strong></p>
      <?php if (empty($ids)): ?>
        <p>No seleccionaste empleados.</p>
        <div class="modal-actions">
          <a class="cancelar_boton" href="empleados.php?<?= e($current_qs) ?>">Volver</a>
        </div>
      <?php else: ?>
        <p>Se marcarán como <strong>baja</strong> e <strong>inhabilitados</strong> <b><?= count($ids) ?></b> empleado(s). Se enviará mail de notificación.</p>
        <form method="post" action="empleados.php?<?= e($current_qs) ?>">
          <?php foreach ($ids as $id): ?><input type="hidden" name="ids[]" value="<?= e($id) ?>"><?php endforeach; ?>
          <input type="hidden" name="f_dni"    value="<?= e($dni) ?>">
          <input type="hidden" name="f_nombre" value="<?= e($nombre) ?>">
          <input type="hidden" name="f_email"  value="<?= e($email) ?>">
          <input type="hidden" name="f_roll"   value="<?= e($roll) ?>">
          <input type="hidden" name="f_estado" value="<?= e($estado) ?>">
          <?php if ($incluir_bajas): ?><input type="hidden" name="f_incluir_bajas" value="1"><?php endif; ?>
          <div class="modal-actions">
            <button name="accion" value="baja_aplicar">Sí, aplicar baja</button>
            <a class="cancelar_boton" href="empleados.php?<?= e($current_qs) ?>">Cancelar</a>
          </div>
        </form>
      <?php endif; ?>
    </dialog>
<?php }

// Modal: Ver/Editar
if ($empVer){ ?>
  <dialog open>
    <p><strong>Empleado <?= e($empVer['empleado_nombre']) ?> (DNI <?= e($empVer['empleado_DNI']) ?>)</strong></p>
    <table style="width:100%;border-collapse:collapse;">
      <tr><th style="width:220px;">DNI</th><td><?= e($empVer['empleado_DNI']) ?></td></tr>
      <tr><th>Nombre</th><td><?= e($empVer['empleado_nombre']) ?></td></tr>
    </table>
    <br>
    <form method="post" action="empleados.php?<?= e($current_qs) ?>">
      <input type="hidden" name="accion" value="ver_guardar">
      <input type="hidden" name="empleado_DNI" value="<?= e($empVer['empleado_DNI']) ?>">
      <input type="hidden" name="f_dni"    value="<?= e($dni) ?>">
      <input type="hidden" name="f_nombre" value="<?= e($nombre) ?>">
      <input type="hidden" name="f_email"  value="<?= e($email) ?>">
      <input type="hidden" name="f_roll"   value="<?= e($roll) ?>">
      <input type="hidden" name="f_estado" value="<?= e($estado) ?>">
      <?php if ($incluir_bajas): ?><input type="hidden" name="f_incluir_bajas" value="1"><?php endif; ?>

      <table style="width:100%;border-collapse:collapse;">
        <tr><th>Email</th><td><input type="email" name="empleado_email" value="<?= e($empVer['empleado_email']) ?>" required></td></tr>
        <tr>
          <th>Rol</th>
          <td>
            <?php
              $rolActual = $empVer['empleado_roll'] ?: '';
              $roles_ed = $roles;
              if ($rolActual !== '' && !in_array($rolActual, $roles_ed, true)) {
                  $roles_ed[] = $rolActual;
                  sort($roles_ed, SORT_STRING|SORT_FLAG_CASE);
              }
            ?>
            <select name="empleado_roll" required>
              <?php foreach ($roles_ed as $r): ?>
                <option value="<?= e($r) ?>" <?= ($r===$rolActual?'selected':'') ?>><?= e($r) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr><th>Dirección</th><td><input type="text" name="empleado_direccion" value="<?= e($empVer['empleado_direccion']) ?>"></td></tr>
        <tr><th>Localidad</th><td><input type="text" name="empleado_localidad" value="<?= e($empVer['empleado_localidad']) ?>"></td></tr>
        <tr><th>Teléfono</th><td><input type="text" name="empleado_telefono" value="<?= e($empVer['empleado_telefono']) ?>"></td></tr>
        <tr>
          <th>Estado</th>
          <td>
            <?php $est = $empVer['empleado_estado'] ?: 'disponible'; ?>
            <select name="empleado_estado" id="ver_estado" onchange="toggleLicencia(this.value, 'ver_lic')">
              <?php foreach (['disponible','no_disponible','licencia','baja'] as $op): ?>
                <option value="<?= $op ?>" <?= ($op===$est?'selected':'') ?>><?= $op ?></option>
              <?php endforeach; ?>
            </select>
            <div id="ver_lic" class="small" style="margin-top:8px; display:<?= ($est==='licencia'?'block':'none') ?>;">
              Desde: <input type="date" name="licencia_desde" value="<?= e($empVer['licencia_desde']) ?>">
              Hasta: <input type="date" name="licencia_hasta" value="<?= e($empVer['licencia_hasta']) ?>">
            </div>
          </td>
        </tr>
      </table>

      <p class="small">Reglas: Nombre/Dirección/Localidad con inicial mayúscula. Email y rol en minúscula. Si estado = baja, se inhabilita y se envía correo.</p>

      <div class="modal-actions">
        <button type="submit">Guardar</button>
        <a class="cancelar_boton" href="empleados.php?<?= e($current_qs) ?>">Volver</a>
      </div>
    </form>
  </dialog>
<?php } ?>

<script>
// Mostrar/ocultar rango de licencia según estado
function toggleLicencia(val, id){
    var el = document.getElementById(id);
    if (!el) return;
    el.style.display = (val==='licencia') ? 'block' : 'none';
}
// Interceptar botón "Baja lógica"
document.addEventListener('click', function(ev){
    if (ev.target && ev.target.tagName==='BUTTON' && ev.target.name==='accion' && ev.target.value==='baja'){
        ev.preventDefault();
        const form = document.getElementById('formEmpleados');
        const hidden = document.getElementById('accionHidden');
        hidden.value = 'baja';
        form.submit();
    }
});
// Al abrir modal "Nuevo", inicializar visibilidad
(function(){
    var ns = document.getElementById('nuevo_estado');
    if (ns){ toggleLicencia(ns.value,'nuevo_lic'); }
})();
</script>
</body>
</html>

