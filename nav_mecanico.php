<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
    <title>navegador</title>

</head>
<body>

    <header class="nav_mecanico">
        <div class="mecanico_navegador">
            <a  href="http://localhost/tallermecanico/inicio.php"><img class="login" src="iconos/WA_Sport_pdf.jpg" alt="Logotipo de WA Sport" ></a>
            <div class="botonera_mecanico">
                <a class="nav" href="http://localhost/tallermecanico/modificacion_mecanico.php" >Datos Personales</a>
                <a href="#" class="nav" onclick="document.getElementById('modal_ordenes_pendientes').showModal(); return false;">
                    Órdenes Pendientes
                </a>
                <a href="#" class="nav" onclick="document.getElementById('modal_turnos_pendientes').showModal(); return false;">
                    Turnos Pendientes
                </a>        
                <a class="nav" href="http://localhost/tallermecanico/mecanico.php">Volver </a>
                <a class="nav" href="http://localhost/tallermecanico/logout.php">Log Out</a>
            </div>
        </div>
    </header>
</body>
</html> 


