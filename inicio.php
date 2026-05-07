<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>"> 
    <title>WA </title>
    
</head>
<body>
    <?php 
        include("navegador.php");
    ?>
    </div>    
    <section class="inicio">
        <p class="in">
            Nos especializamos en brindar servicios de reparación y mantenimiento automotriz de alta calidad.
            <br> 
            Con años de experiencia en el sector, nuestro equipo de mecánicos altamente capacitados se dedica a
            <br>
            ofrecer soluciones eficientes y confiables para tu vehículo, asegurando su rendimiento óptimo y tu seguridad en la carretera.
        </p>
    </section>
    <br>
    <section class="prom-opi">
        <div class="prom"> 
            <h2 >Promos</h2>
            <!--<img class="prom-opi_img" src="Promociones/5.png" alt="Tiene que variar las promos ">-->
            <!-- INTENTO DE CARRUCEL CON HTML Y CSS SIN JAVA -->
             <div class="slide">
                <div class="slide-inner">
                    <input class="slide-open" type="radio" id="slide-1" 
                        name="slide" aria-hidden="true" hidden="" checked="checked">
                    <div class="slide-item">
                        <img src="Promociones/5.jpg">
                    </div>

                    <input class="slide-open" type="radio" id="slide-2" 
                        name="slide" aria-hidden="true" hidden="">
                    <div class="slide-item">
                        <img src="Promociones/6.jpg">
                    </div>

                    <input class="slide-open" type="radio" id="slide-3" 
                        name="slide" aria-hidden="true" hidden="">
                    <div class="slide-item">
                        <img src="Promociones/7.jpg">
                    </div>

                    <input class="slide-open" type="radio" id="slide-4" name="slide" hidden>
                    <div class="slide-item">
                        <img src="Promociones/8.jpg">
                </div>

                        <label for="slide-4" class="slide-control prev control-1">‹</label>
                        <label for="slide-2" class="slide-control next control-1">›</label>

                        <!-- Slide 2: prev = 1, next = 3 -->
                        <label for="slide-1" class="slide-control prev control-2">‹</label>
                        <label for="slide-3" class="slide-control next control-2">›</label>

                        <!-- Slide 3: prev = 2, next = 4 -->
                        <label for="slide-2" class="slide-control prev control-3">‹</label>
                        <label for="slide-4" class="slide-control next control-3">›</label>

                        <!-- Slide 4: prev = 3, next = 1 -->
                        <label for="slide-3" class="slide-control prev control-4">‹</label>
                        <label for="slide-1" class="slide-control next control-4">›</label>

                        <!-- Indicadores -->
                        <ol class="slide-indicador">
                        <li><label for="slide-1" class="slide-circulo">•</label></li>
                        <li><label for="slide-2" class="slide-circulo">•</label></li>
                        <li><label for="slide-3" class="slide-circulo">•</label></li>
                        <li><label for="slide-4" class="slide-circulo">•</label></li>
                        </ol>

                </div>
            </div>

        </div>
        <div>
            <h2 >Opiniones</h2>
            <img class="prom-opi_img" src="fondos/qrcode_www.google.com.ar.jpg" alt="QR dirijido a reseñas de google">
           
            

        </div>
    </section>

    <br>

  
    <?php 
        include("piedepagina.php");
    ?>
    <script src="logout_control.js"></script>
</body>
</html>
