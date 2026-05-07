<?php
require 'conexion_base.php';
require_once 'verificar_sesion_empleado.php';

// VARIABLES DE MODAL
$clienteInexistente = false;
$vehiculoExistente = false;
$vehiculoExistenteDatos = [];
$modalErrorKilometraje = false;
$modalErrorKilometrajeMenor = false;
$modalVehiculoNoEncontrado = false;
$modalCamposVehiculoIncompletos = false;
$modalVehiculoRegistrado = false;
$modalCamposIncompletos = false;
$modalOrdenRegistrada = false;
$modalErrorBD = false;
$mensajeErrorBD = "";
$mostrarModalVehiculo = false;
$mostrarModalOrden = false;
$datosVehiculo = [];
$datosOrden = [];
$modalOrdenNoEncontrada = false;
$modalMecanicoTurnoConflicto = false;   // mecánico con turno pendiente ese día
$modalMecanicoOrdenPendiente = false;   // mecánico con alguna orden sin finalizar

try {
    // FORMULARIO NUEVO VEHICULO
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['nuevo_vehiculo'])) {
        $dni     = trim($_POST['vehiculo_dni'] ?? '');
        $patente = strtoupper(trim($_POST['vehiculo_patente'] ?? ''));
        $marca   = trim($_POST['vehiculo_marca'] ?? '');
        $modelo  = trim($_POST['vehiculo_modelo'] ?? '');
        $anio    = trim($_POST['vehiculo_anio'] ?? '');
        $color   = trim($_POST['color'] ?? '');
        $motor   = trim($_POST['motor'] ?? '');

        //VALIDA CAMPOS NOTNULL
        if (empty($dni) || empty($patente) || empty($marca) || empty($modelo)) {
            $modalCamposVehiculoIncompletos = true;
            goto fin;
        } else {
            // VERIFICAR SI EL CLIENTE EXISTE
            $stmt = $conexion->prepare("SELECT * FROM clientes WHERE cliente_DNI = :dni");
            $stmt->execute(['dni' => $dni]);

            if ($stmt->rowCount() === 0) {
                $clienteInexistente = true;
            } else {
            // VERIFICAR SI LA PATENTE YA EXISTE
                $stmt = $conexion->prepare("SELECT v.*, c.cliente_nombre, c.cliente_email 
                    FROM vehiculos v 
                    JOIN clientes c ON v.cliente_DNI = c.cliente_DNI 
                    WHERE v.vehiculo_patente = :patente");
                $stmt->execute(['patente' => $patente]);

                if ($stmt->rowCount() > 0) {
                        $vehiculoExistente = true;
                        $vehiculoExistenteDatos = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                // INSERTAR VEHICULO
                    $insert = $conexion->prepare("INSERT INTO vehiculos 
                            (vehiculo_patente, vehiculo_marca, vehiculo_modelo, vehiculo_anio, vehiculo_color, vehiculo_motor, cliente_DNI)
                            VALUES (:patente, :marca, :modelo, :anio, :color, :motor, :dni)");
                    $insert->execute([
                        'patente' => $patente,
                        'marca'   => $marca,
                        'modelo'  => $modelo,
                        'anio'    => $anio ?: null,
                        'color'   => $color ?: null,
                        'motor'   => $motor ?: null,
                        'dni'     => $dni
                    ]);
                    $modalVehiculoRegistrado = true;
                    goto fin;
                }
            }
        }
    }

    // FORMULARIO NUEVA ORDEN
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['nueva_orden'])) {
    $patente     = strtoupper(trim($_POST['veh_pat'] ?? ''));
    $fecha       = $_POST['fecha'] ?? '';
    $complejidad = $_POST['complejidad'] ?? '';
    $km          = $_POST['kilometraje'] ?? '';
    $servicio    = $_POST['serv_cod'] ?? '';
    $comentario  = trim($_POST['descripcion'] ?? '');
    $mecanico = $_POST['mecanico_dni'] ?? '';
        //VERIFICAR CAMPOS VACIOS
        if (empty($patente) || empty($fecha) || empty($complejidad) || empty($km) || empty($servicio) || empty($mecanico)) {
            $modalCamposIncompletos = true;
            goto fin;
        // VERIFICAR QUE KILOMETRAJE SEA NÚMERO ENTERO POSITIVO
        } elseif (!ctype_digit($km)) {
                $modalErrorKilometraje = true;
        } else {
            // VERIFICAR SI EL VEHICULO EXISTE
            $stmt = $conexion->prepare("SELECT * FROM vehiculos WHERE vehiculo_patente = :patente");
            $stmt->execute(['patente' => $patente]);

            if ($stmt->rowCount() === 0) {
                $modalVehiculoNoEncontrado = true;
                goto fin;
            } else {
                // VERIFICAR QUE KILOMETRAJE NO SEA MENOR AL ANTERIOR
                $stmt = $conexion->prepare("SELECT MAX(ot.orden_kilometros) as max_km 
                        FROM orden_trabajo ot 
                        JOIN ordenes o ON ot.orden_numero = o.orden_numero 
                        WHERE o.vehiculo_patente = :patente");
                $stmt->execute(['patente' => $patente]);
                $maxKm = (int) $stmt->fetch(PDO::FETCH_ASSOC)['max_km'] ?? 0;
                
                $kmInt = (int)$km;   
                if ($kmInt < $maxKm) {
                    $modalErrorKilometrajeMenor = true;
                    goto fin;
                } else {
                    // VERIFICAR QUE EL MECANICO TIENE TURNO PENDIENTE EL MISMO DIA
                    $stmt = $conexion->prepare("
                        SELECT 1
                        FROM turnos
                        WHERE mecanico_dni = :mec
                        AND turno_fecha  = :fecha
                        AND turno_estado = 'pendiente'
                        LIMIT 1
                    ");
                    $stmt->execute([':mec' => $mecanico, ':fecha' => $fecha]);
                    if ($stmt->rowCount() > 0) {
                        $modalMecanicoTurnoConflicto = true;
                        goto fin;
                    }

                    // VERIFICAR QUE EL MECANICO TIENE ALGUNA ORDEN SIN FINALIZAR
                    $stmt = $conexion->prepare("
                        SELECT 1
                        FROM orden_trabajo ot
                        WHERE ot.mecanico_DNI = :mec
                        AND ot.orden_estado = 0
                        LIMIT 1
                    ");
                    $stmt->execute([':mec' => $mecanico]);
                    if ($stmt->rowCount() > 0) {
                        $modalMecanicoOrdenPendiente = true;
                        goto fin;
                    }
                    // VERIFICA LA ULTIMA ORDEN
                    $stmt = $conexion->query("SELECT MAX(orden_numero) AS ultimo FROM ordenes");
                    $ultimoNumero = $stmt->fetch(PDO::FETCH_ASSOC)['ultimo'] ?? 0;
                    $nuevoNumero = $ultimoNumero + 1;
                    // INSERT TABLA ORDENES
                    $insertOrden = $conexion->prepare("INSERT INTO ordenes (orden_numero, orden_fecha, vehiculo_patente) 
                                                    VALUES (:numero, :fecha, :patente)");
                    $insertOrden->execute([
                        'numero'  => $nuevoNumero,
                        'fecha'   => $fecha,
                        'patente' => $patente
                    ]);

                    $ordenNumero = $nuevoNumero;
                    // INSERT TABLA ORDEN_TRABAJO
                    $insertTrabajo = $conexion->prepare("INSERT INTO orden_trabajo 
                        (orden_numero, servicio_codigo, complejidad, orden_kilometros, orden_comentario, orden_estado, mecanico_DNI)
                        VALUES (:orden_numero, :servicio, :complejidad, :km, :comentario, 0, :mecanico)");
                    $insertTrabajo->execute([
                        'orden_numero' => $ordenNumero,
                        'servicio'     => $servicio,
                        'complejidad'  => $complejidad,
                        'km'           => $km,
                        'comentario'   => $comentario,
                        'mecanico'     => $mecanico
                    ]);
                    $modalOrdenRegistrada = true;
                    goto fin;
                }
            }
        }
    }

    // PARA TRAER SERVICIO PARA EL SELECT
    $servicios = $conexion->query("SELECT servicio_codigo, servicio_nombre FROM servicios")->fetchAll(PDO::FETCH_ASSOC);
    
    // PARA TRAER MECANICO PARA EL SELECT
    $mecanicos = $conexion->query("SELECT empleado_DNI, empleado_nombre FROM empleados WHERE empleado_roll = 'mecanico'")->fetchAll(PDO::FETCH_ASSOC);

    // FORMULARIO NUEVA ORDEN
    if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['buscar_rec'])) {
        $buscarDNI = trim($_GET['dni'] ?? '');
        $buscarVeh = trim($_GET['vehiculo'] ?? '');
        $buscarOrden = trim($_GET['n_orden'] ?? '');
        
        // BUSQUEDA POR DNI
        if (!empty($buscarDNI)) {
            $stmt = $conexion->prepare("SELECT cliente_DNI FROM clientes WHERE cliente_DNI = :dni");
            $stmt->execute(['dni' => $buscarDNI]);

            if ($stmt->rowCount() > 0) {
                header("Location: modificacion_cliente_recepcionista.php?dni=" . urlencode($buscarDNI));
                exit();
    } else {
        $clienteInexistente = true;
    }
        // BUSQUEDA POR VEHICULO
        } elseif (!empty($buscarVeh)) {
            $stmt = $conexion->prepare("SELECT * FROM vehiculos WHERE vehiculo_patente = :patente");
            $stmt->execute(['patente' => $buscarVeh]);
            if ($stmt->rowCount() > 0) {
                $datosVehiculo = $stmt->fetch(PDO::FETCH_ASSOC);
                $mostrarModalVehiculo = true;
            }else {
                $modalVehiculoNoEncontrado = true;
            }
        // BUSQUEDA POR ORDEN
        } elseif (!empty($buscarOrden)) {
            $stmt = $conexion->prepare("SELECT o.orden_numero, o.orden_fecha, o.vehiculo_patente,
                                        ot.servicio_codigo, ot.complejidad, ot.orden_kilometros,
                                        ot.orden_comentario, ot.orden_estado
                                FROM ordenes o
                                JOIN orden_trabajo ot ON o.orden_numero = ot.orden_numero
                                WHERE o.orden_numero = :orden");
            $stmt->execute(['orden' => $buscarOrden]);

            if ($stmt->rowCount() > 0) {
                $datosOrden = $stmt->fetch(PDO::FETCH_ASSOC);
                $mostrarModalOrden = true;
            }else {
                $modalOrdenNoEncontrada = true;
            }
        }
    }
}catch (PDOException $e) {
    $modalErrorBD = true;
    $mensajeErrorBD = "Error en la base de datos: " . $e->getMessage();
    goto fin;
}
fin:
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>"> 
    <title>Document</title>
</head>

<!--MODAL CLIENTE INEXISTENTE-->
<?php if ($clienteInexistente): ?>
<dialog open id="modal_cliente_inexistente">
    <p style="text-align:center;"><strong>Cliente inexistente</strong></p>
    <div style="text-align:center;">
        <button onclick="document.getElementById('modal_cliente_inexistente').close()">Cerrar</button>
        <a href="registro_cliente_recepcionista.php"><button>Nuevo Cliente</button></a>
    </div>
</dialog>
<?php endif; ?>

<!--MODAL VEHICULO EXISTENTE-->
<?php if ($vehiculoExistente): ?>
<dialog open id="modal_vehiculo_existente">
    <p style="text-align:center;"><strong>El vehículo ya está registrado</strong></p>
    <p style="text-align:center;">Patente: <?= htmlspecialchars($vehiculoExistenteDatos['vehiculo_patente']) ?></p>
    <p style="text-align:center;">Cliente: <?= htmlspecialchars($vehiculoExistenteDatos['cliente_nombre']) ?></p>
    <p style="text-align:center;">Email: <?= htmlspecialchars($vehiculoExistenteDatos['cliente_email']) ?></p>
    <div style="text-align:center;">
        <button onclick="document.getElementById('modal_vehiculo_existente').close()">Cerrar</button>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL ERROR KILOMETRAJE NO NUMÉRICO -->
<?php if ($modalErrorKilometraje): ?>
<dialog open id="modal_error_km">
    <p style="text-align:center;"><strong>Error en el campo Kilometraje</strong></p>
    <p style="text-align:center;">Por favor, ingrese un valor numérico válido.</p>
    <div style="text-align:center;">
        <button onclick="document.getElementById('modal_error_km').close()">Cerrar</button>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL ERROR KILOMETRAJE MENOR -->
<?php if ($modalErrorKilometrajeMenor): ?>
<dialog open id="modal_km_menor">
    <p style="text-align:center;"><strong>Error de Kilometraje</strong></p>
    <p style="text-align:center;">El kilometraje ingresado es menor al registrado previamente.</p>
    <div style="text-align:center;">
        <button onclick="document.getElementById('modal_km_menor').close()">Cerrar</button>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL VEHICULO NO ENCONTRADO -->
<?php if ($modalVehiculoNoEncontrado): ?>
<dialog open id="modal_vehiculo_no_encontrado">
    <p style="text-align:center;"><strong>Vehículo no encontrado</strong></p>
    <p style="text-align:center;">No se ha encontrado un vehículo registrado con la patente ingresada.</p>
    <div style="text-align:center;">
        <button onclick="document.getElementById('modal_vehiculo_no_encontrado').close()">Cerrar</button>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL CAMPOS VEHICULO INCOMPLETO -->
<?php if ($modalCamposVehiculoIncompletos): ?>
<dialog open id="modal_campos_incompletos">
    <p style="text-align:center;"><strong>Faltan completar campos obligatorios</strong></p>
    <p style="text-align:center;">Debe ingresar DNI, Patente, Marca y Modelo para registrar un vehículo.</p>
    <div style="text-align:center;">
        <button onclick="document.getElementById('modal_campos_incompletos').close()">Cerrar</button>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL VEHICULO REGISTRADO -->
<?php if ($modalVehiculoRegistrado): ?>
<dialog open id="modal_vehiculo_ok">
    <p style="text-align:center;"><strong>Vehículo registrado con éxito</strong></p>
    <div style="text-align:center;">
        <form method="post" action="recepcionista.php">
            <button type="submit">Aceptar</button>
        </form>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL CAMPOS ORDEN INCOMPLETO -->
<?php if ($modalCamposIncompletos): ?>
<dialog open id="modal_campos_incompletos">
    <p style="text-align:center;"><strong>Error</strong></p>
    <p style="text-align:center;">Por favor complete todos los campos del formulario.</p>
    <div style="text-align:center;">
        <button onclick="document.getElementById('modal_campos_incompletos').close()">Cerrar</button>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL ORDEN REGISTRADA -->
<?php if ($modalOrdenRegistrada): ?>
<dialog open id="modal_orden_ok">
    <p style="text-align:center;"><strong>Orden registrada con éxito</strong></p>
    <p style="text-align:center;">La orden fue cargada correctamente.</p>
    <div style="text-align:center;">
        <form method="post" action="recepcionista.php">
            <button type="submit">Aceptar</button>
        </form>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL ERROR BdD -->
<?php if ($modalErrorBD): ?>
<dialog open id="modal_error_bd">
    <p style="text-align:center;"><strong>Error en la Base de Datos</strong></p>
    <p style="text-align:center;"><?= htmlspecialchars($mensajeErrorBD) ?></p>
    <div style="text-align:center;">
        <button onclick="document.getElementById('modal_error_bd').close()">Cerrar</button>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL VEHICULO ENCONTRADO -->
<?php if ($mostrarModalVehiculo): ?>
<dialog open id="modal_vehiculo_encontrado">
    <h3>Datos del Vehículo</h3>
    <p><strong>Patente:</strong> <?= htmlspecialchars($datosVehiculo['vehiculo_patente']) ?></p>
    <p><strong>Marca:</strong> <?= htmlspecialchars($datosVehiculo['vehiculo_marca']) ?></p>
    <p><strong>Modelo:</strong> <?= htmlspecialchars($datosVehiculo['vehiculo_modelo']) ?></p>
    <p><strong>Año:</strong> <?= htmlspecialchars($datosVehiculo['vehiculo_anio']) ?></p>
    <p><strong>Color:</strong> <?= htmlspecialchars($datosVehiculo['vehiculo_color']) ?></p>
    <p><strong>Motor:</strong> <?= htmlspecialchars($datosVehiculo['vehiculo_motor']) ?></p>
    <div style="text-align:center;">
        <button onclick="document.getElementById('modal_vehiculo_encontrado').close()">Cerrar</button>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL ORDEN ENCONTRADA -->
<?php if ($mostrarModalOrden): ?>
<dialog open id="modal_orden_encontrada">
    <h3>Datos de la Orden</h3>
    <form class="form_orden">
        <p><strong>Número:</strong> <?= htmlspecialchars($datosOrden['orden_numero']) ?></p>
        <p><strong>Fecha:</strong> <?= htmlspecialchars($datosOrden['orden_fecha']) ?></p>
        <p><strong>Patente:</strong> <?= htmlspecialchars($datosOrden['vehiculo_patente']) ?></p>
        <p><strong>Servicio:</strong> <?= htmlspecialchars($datosOrden['servicio_codigo']) ?></p>
        <p><strong>Complejidad:</strong> <?= htmlspecialchars($datosOrden['complejidad']) ?></p>
        <p><strong>Kilometraje:</strong> <?= htmlspecialchars($datosOrden['orden_kilometros']) ?></p>
        <p><strong>Comentario:</strong> <?= htmlspecialchars($datosOrden['orden_comentario']) ?></p>
        <p><strong>Estado:</strong> <?= $datosOrden['orden_estado'] == 0 ? 'Pendiente' : 'Finalizada' ?></p>
        <div style="text-align:center;">
            <button type="button" onclick="document.getElementById('modal_orden_encontrada').close()">Cerrar</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<!-- MODAL ORDEN NO ENCONTRADA -->
<?php if ($modalOrdenNoEncontrada): ?>
<dialog open id="modal_orden_no_encontrada">
    <p style="text-align:center;"><strong>Orden no encontrada</strong></p>
    <p style="text-align:center;">No existe ninguna orden con el número ingresado.</p>
    <div style="text-align:center;">
        <button onclick="document.getElementById('modal_orden_no_encontrada').close()">Cerrar</button>
    </div>
</dialog>
<?php endif; ?>

<!-- MODAL TURNO PENDIENTE -->
<?php if ($modalMecanicoTurnoConflicto): ?>
<dialog open id="modal_mec_turno">
  <p style="text-align:center;"><strong>Mecánico ocupado por turno</strong></p>
  <p style="text-align:center;">El mecánico seleccionado ya tiene un turno pendiente en la fecha elegida.</p>
  <div style="text-align:center;">
    <form method="post" action="recepcionista.php">
      <button type="submit">Volver</button>
    </form>
  </div>
</dialog>
<?php endif; ?>

<!-- MODAL ORDEN PENDIENTE -->
<?php if ($modalMecanicoOrdenPendiente): ?>
<dialog open id="modal_mec_orden_pte">
  <p style="text-align:center;"><strong>Mecánico con orden pendiente</strong></p>
  <p style="text-align:center;">El mecánico seleccionado ya tiene una orden sin finalizar.</p>
  <div style="text-align:center;">
    <form method="post" action="recepcionista.php">
      <button type="submit">Volver</button>
    </form>
  </div>
</dialog>
<?php endif; ?>

<body>
    <?php 
        include("nav_rec.php");
    ?>
    <br>
    
    <section class="nuevo_cli"> 
        <br>
        <h2 class="recepcion_titulos">NUEVO VEHICULO</h2>
        <br>
        <!--FORMULARIO NUEVO VEHICULO-->
        <form action="" method="post" class="form_recp_clinuv">
            <label  for="dni_cli" class="form_dni">DNI Cliente</label>
            <input type="text" name="vehiculo_dni" id="dni_cli" class="form_dni1">
            <br><br><br>
            <label for="patente" class="form_patetente">Patente</label>
            <input type="text" name="vehiculo_patente" id="patente" class="form_patetente1">
            <label for="marca" class="form_marca">Marca</label>
            <input type="text" name="vehiculo_marca" id="marca" class="form_marca1">
            <label for="modelo" class="form_modelo">Modelo</label>
            <input type="text" name="vehiculo_modelo" id="modelo" class="form_modelo1">
            <label for="año" class="form_año">Año</label>
            <input type="number" name="vehiculo_anio" id="año" class="form_año1">
            <label for="motor" class="form_motor">Motor</label>
            <input type="text" name="motor" id="motor" class="form_motor1">
            <label for="color" class="form_color">Color</label>
            <input type="text" name="color" id="color" class="form_color1">
            <input class="guardar_rec" type="submit" value="Guardar" name="nuevo_vehiculo">
        </form>
    </section>
 <br>
    <section class="nueva_ord">
        <br>
        <h2 class="recepcion_titulos">NUEVA ORDEN</h2>
        <br>
        <!--FORMULARIO NUEVA ORDEN-->
        <form action="" method="post" class="form_orden">
            <label for="veh_pat" class="patente_form">Vehiculo Patente</label>
            <input type="text" name="veh_pat" id="veh_pat" class="patente_form1">
            <label for="fecha" class="fecha_form">Fecha</label>
            <input type="date" name="fecha" class="fecha_form1">
            <label for="complejidad" class="compl_form">Complejidad</label>
            <select name="complejidad" id="complejidad" class="compl_form1">
                <option value="1">Baja (1)</option>
                <option value="2">Media (2)</option>
                <option value="3">Alta (3)</option>
            </select>
            <label for="kilometraje" class="kil_form">Kilometraje</label>
            <input type="text" name="kilometraje" id="kilometraje" class="kil_form1">
            <label for="serv_cod" class="serv_form">Servicio</label>
            <select name="serv_cod" id="serv_cod" class="serv_form1">
                <option value="">Seleccione un servicio</option>
                <?php foreach ($servicios as $serv): ?>
                    <option value="<?= htmlspecialchars($serv['servicio_codigo']) ?>">
                        <?= htmlspecialchars($serv['servicio_codigo'] . ' - ' . $serv['servicio_nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="descripcion" class="serv_desc">Descripcion/Observacion</label>
            <label for="mecanico_dni" class="mecanico_form">Mecánico</label>
                <select name="mecanico_dni" id="mecanico_dni" class="mecanico_form1" required>
                    <option value="">Seleccione un mecánico</option>
                    <?php foreach ($mecanicos as $mec): ?>
                        <option value="<?= htmlspecialchars($mec['empleado_DNI']) ?>">
                            <?= htmlspecialchars($mec['empleado_nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <textarea  class="serv_desc1" name="descripcion" id="descripcion" cols="" rows=""></textarea>
            <input class="guardar_rec" type="submit" value="Guardar" name="nueva_orden">
        </form>
    </section>
<br>
    <article class="busqueda_cli">
        <br>
        <h2 class="recepcion_titulos"> BUSCAR</h2>
        <br>
        <!--FORMULARIO BUSQUEDA-->
        <form action="" method="get" class="form_busqueda">
            <label for="dni" class="form2_dni">DNI</label>
            <input type="text" name="dni" id="dni" class="form2_dni2">

            <label for="vehiculo" class="form2_veh">Vehiculo Patente </label>
            <input type="text" name="vehiculo" id="vehiculo" class="form2_veh2">

            <label for="n_orden" class="form2_orden">Número de orden</label>
            <input type="text" name="n_orden" id="n_orden" class="form2_orden2" >
            

            <input class="buscar_rec" type="submit" value="Buscar" name="buscar_rec" >

        </form>
    </article>

    <br>

    <?php include("piedepagina.php");?>
    <script src="control_inactividad.js"></script>
</body>
</html>
