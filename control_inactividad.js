(function () {
    let tiempoInactividad = 55 * 60 * 1000; // 5 minutos en milisegundos
    let timer;

    function resetTimer() {
        clearTimeout(timer);
        timer = setTimeout(cerrarSesionPorInactividad, tiempoInactividad);
    }

    function cerrarSesionPorInactividad() {
        console.log("⏳ Inactividad detectada. Cerrando sesión...");
        fetch("logout_auto.php", {
            method: "POST",
            body: "inactivo",
        }).then(() => {
            window.location.href = "logout.php";
        });
    }

    // Eventos de actividad
    window.onload = resetTimer;
    document.onmousemove = resetTimer;
    document.onkeydown = resetTimer;
    document.onscroll = resetTimer;
})();
