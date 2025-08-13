<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login/login.html");
    exit();
}
?>

<!-- HEADER -->
<div class="header">
  <div class="logo-slot">
    <!-- Acá iría el <img> solo en las páginas que lo necesiten -->
  </div>
  <nav class="nav-links" id="navLinks">
    <a href="../autos/admin-panel.php">Buscar por patente</a>
    <a href="../ver_turnos.php" target="_blank">Ver turnos</a>
    <a href="../verificar_vencimientos.php" target="_blank">Ver vencimientos</a>
  </nav>
</div>

<script>
  function toggleMenu() {
    const nav = document.getElementById("navLinks");
    nav.classList.toggle("show");
  }
</script>
