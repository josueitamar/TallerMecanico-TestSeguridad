<?php
require 'conexion_base.php';
require_once 'verificar_sesion_empleado.php';

// DNI DEL GERENTE LOGEADO
$mecDni = $_SESSION['empleado_DNI'] ?? $_SESSION['empleado_dni'] ?? null;

include 'nav_gerente.php';
?>
<div class="gerente_inicio">
    <br><br>
    <h2>Panel del Gerente</h2>
    <br>
    <hr>
    <!-- SECCIÓN PRODUCTOS -->
    <section>
        <br>
        <h3>Productos</h3>
        <br>
        <!-- GET para que se vean los filtros en la URL y se mantengan -->
        <form action="productos.php" method="GET" class="form-busqueda_ger">
            <label>Código</label>
            <input class="form-busqueda_ger_input" type="text" name="codigo" placeholder="Ej: LUB001">

            <label>Categoría</label>
            <input class="form-busqueda_ger_input"  type="text" name="categoria" placeholder="Ej: Lubricantes">

            <label>Descripción</label>
            <input class="form-busqueda_ger_input" type="text" name="descripcion" placeholder="Buscar descripción">
            <div class="botones_ger">
                <br>
                <input type="checkbox" name="incluir_no_disponibles" value="1">Incluir no disponibles
                <br>
                <button type="submit" name="buscar">Buscar</button>
                <!-- Botón que abre el modal "Nuevo" en productos.php -->
                <button type="submit" name="nuevo" value="1">Nuevo</button>
            </div>
        </form>
    </section>

    <hr>
    <br>
    <!-- SECCIÓN ESTADÍSTICAS -->
    <section>
        <br>
        <h3>Estadísticas</h3>
        <br>
        <form action="estadisticas.php" method="GET" class="form-busqueda_ger">
            <label>Tipo</label>
            <select name="tipo">
                <option value="servicios">Servicios</option>
                <option value="ventas">Ventas</option>
            </select>

            <label>Desde</label>
            <input class="form-busqueda_ger_input" type="date" name="desde">

            <label>Hasta</label>
            <input class="form-busqueda_ger_input" type="date" name="hasta">
        <div class="botones_ger">
            <button type="submit" name="buscar_estadisticas">Buscar</button>
        </div>    
        </form>
    </section>
</div>

    <?php 
        include("piedepagina.php");
    ?>
</body>
</html>
