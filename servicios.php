<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>"> 
    
    <title>Servicios</title>
</head>
<body>
    <?php 
        include("navegador.php");
    ?>
    
    <br>
    <section class="serv">
        <div > 
           <div class="item_serv">
                <img src="servicios/service.jpg" alt="">
            </div>
            <h2> SERVICE</h2>
            <h3>CAMBIO DE FILTROS - ACEITE, AIREMOTOR, AIRE HABITACULO, COMBUSTIBLE- CAMBIO DE FLUIDOS</h3>
        </div> 

        <div>
            <div class="item_serv">
                <img src="servicios/diag_computarizado.jpg" alt="">
            </div>
            <h2>DIAGNOSTICO COMPUTARIZADO</h2>
            <h3>TOMA DE DIAGNOSTICO</h3>
        </div>
 
        <div>
            <div class="item_serv">
                <img src="servicios/sist_distribucion.jpg" alt="">
            </div>
            <h2> SERVICE DISTRIBUCION</h2>
            <h3>CORREA O CADENA DE DISTRIBUCION, TENSORES, CORREA DE ACCESORIOS</h3>
        </div>

        <div>
            <div class="item_serv">
                <img src="servicios/susp_delantera_basi.jpg" alt="">
            </div>
            <h2>FRENOS DELANTEROS</h2>
            <h3>REPARACION O CAMBIO DE DISCO, CAMBIO DE PASTILLAS</h3>
        </div>
        
        <div>
            <div class="item_serv">
                <img src="servicios/susp_delantera_comp.jpg" alt="">
            </div>
            <h2>SUSPENSIÓN DELANTERA COMPLETA</h2>
            <h3>SUPENCION DELANTERA BASICA + AMORTIGUADORES, CASOLETAS, ESPIRALES, PARRILLAS</h3>
        </div>

        <div>
            <div class="item_serv">
                <img src="servicios/sist_direccion.jpg" alt="">
            </div>
            <h2>SISTEMA DE DIRECCION</h2>
            <h3>CREMALLERA, EXTREMOS, PRECAP, COLUMNA DE DIRECCION</h3>
        </div>  
        
        <div>
            <div class="item_serv">
                <img src="servicios/sistema_admision.jpg" alt="">
            </div>
            <h2>SISTEMA DE ADMSION</h2>
            <h3>LIMPIEZA, REGULACION Y PUESTA A PUNTO DE CARBURADOR O CUERPO MARIPOSA</h3>
        </div>  

        <div>
            <div class="item_serv">
            <img src="servicios/tapa_cilindro.jpg" alt="">
        </div>
            <h2>TAPA DE CILINDRO</h2>
        <h3>REPARACION DE TAPA DE CILINDROS, CAMBIO DE JUNTAS, RETENES Y BULONES</h3>
        </div>  

        <div>
            <div class="item_serv">
            <img src="servicios/motor_comp.jpg" alt="">
        </div>
            <h2>MOTOR COMPLETO</h2>
        <h3>DESARME Y REPARACION COMPLETA DE MOTOR</h3>
        </div>  
    </section >

    <?php 
        include("piedepagina.php");
    ?>

</body>
</html>
