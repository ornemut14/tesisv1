<?php
require_once '../conexion.php';
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../login/login.html");
  exit();
}

$patente = $_GET['patente'] ?? '';

if (empty($patente)) {
  echo "<p>Patente no proporcionada.</p><a href='../admin/autos/admin-panel.php'>Volver</a>";
  exit();
}

// Ocultar el vehículo (eliminación lógica)
$stmt = $conexion->prepare("UPDATE vehiculos SET activo = 0 WHERE patente = ?");
$stmt->bind_param("s", $patente);
$stmt->execute();
$stmt->close();

// Redirigir al panel
header("Location: ../admin/autos/admin-panel.php");
exit();
?>
