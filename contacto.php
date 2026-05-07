<?php
require_once 'conexion_base.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>"> 

    <title>Contacto</title>
</head>
<body>
    <?php 
        include("navegador.php");
    ?>


</div>
    
    <br>
    <h2 class="contacto">Contactanos</h2>
<form class="formulario1" action="enviar_consulta.php" method="post">
    <div class="form_1">
        <label for="nom">Nombre</label>
        <input type="text" name="nombre" id="nom" placeholder="Martina Elías González" required>
    </div>

    <div class="form_2">
        <label for="tel">Teléfono</label>
        <input type="tel" name="telefono" id="tel" placeholder="113585764" required>
    </div>

    <div class="form_3">
        <label for="mail">Email</label>
        <input type="email" name="email" id="mail" placeholder="martina.gonzalez24@gmail.com" required>
    </div>

    <div class="form_4">
        <label for="cons">Consulta</label>
        <textarea name="consulta" id="cons" placeholder="Dejanos tu consulta" cols="30" rows="10" required style="height: 257px; width: 1803px;"></textarea>
    </div>

    <button type="submit">Enviar consulta</button>
</form>

</div>

    <div class="ubi">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3282.2428424010727!2d-58.54123552353721!3d-34.64856957293816!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95bcc94f14768b4b%3A0x569b8550c525f535!2sWAsport!5e0!3m2!1ses!2sar!4v1746483584595!5m2!1ses!2sar"  allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        <div class="datos">
            <h2>WAsport</h2>
            <a href="https://www.google.com.ar/maps/place/WAsport/@-34.6485696,-58.5412355,17z/data=!3m1!4b1!4m6!3m5!1s0x95bcc94f14768b4b:0x569b8550c525f535!8m2!3d-34.6485696!4d-58.5386606!16s%2Fg%2F11h7z1d_5t?hl=es&entry=ttu&g_ep=EgoyMDI1MDQzMC4xIKXMDSoJLDEwMjExNDUzSAFQAw%3D%3D" >Paso 1418, Ramos Mejía, Provincia de Buenos Aires</a>
            <h2>011 5098-8487</h2>
        </div>
    </div>

    <?php 
         include("piedepagina.php");
    ?>


</body>
</html>

