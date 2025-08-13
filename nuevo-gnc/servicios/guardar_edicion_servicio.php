<?php
require_once '../conexion.php';
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../login/login.html");
  exit();
}

$id = $_POST['id'] ?? '';
$patente = $_POST['patente'] ?? '';
$fecha = $_POST['fecha'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';

if ($id === '' || $patente === '' || $fecha === '' || $descripcion === '') {
  echo "<p>Error: Faltan datos para actualizar el servicio.</p>";
  echo "<a href='../admin/autos/admin-panel.php'>Volver al panel</a>";
  exit();
}

// Actualizar servicio en la base de datos
$stmt = $conexion->prepare("UPDATE servicios SET fecha = ?, descripcion = ? WHERE id = ? AND vehiculo_patente = ?");
$stmt->bind_param("ssis", $fecha, $descripcion, $id, $patente);

if ($stmt->execute()) {
  header("Location: ../clientes/ficha-cliente.php?patente=" . urlencode($patente));
  exit();
} else {
  echo "<p>Error al actualizar el servicio.</p>";
  echo "<a href='../admin/autos/admin-panel.php'>Volver al panel</a>";
}

$stmt->close();
?>
