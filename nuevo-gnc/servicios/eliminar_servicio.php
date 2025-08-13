<?php
require_once '../conexion.php';
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../login/login.html");
  exit();
}

$id = $_GET['id'] ?? '';
$patente = $_GET['patente'] ?? '';

if ($id === '' || $patente === '') {
  echo "<p>Error: datos faltantes.</p><a href='../admin/autos/admin-panel.php'>Volver</a>";
  exit();
}

// Eliminación lógica del servicio (activo = 0)
$stmt = $conexion->prepare("UPDATE servicios SET activo = 0 WHERE id = ? AND vehiculo_patente = ?");
$stmt->bind_param("is", $id, $patente);

if ($stmt->execute()) {
  header("Location: ../clientes/ficha-cliente.php?patente=" . urlencode($patente));
  exit();
} else {
  echo "<p>Error al eliminar el servicio.</p>";
}
$stmt->close();
?>
