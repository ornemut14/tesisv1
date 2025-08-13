<?php
require_once '../conexion.php';
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../login/login.html");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patente = $_POST['patente'] ?? '';
  $fecha_oblea_realizada = $_POST['oblea'] ?? null;
  $fecha_prueba_realizada = $_POST['prueba_hidraulica'] ?? null;
  $reprueba = $_POST['reprueba'] ?? null;

  if (!$patente) {
    echo "<p>Error: patente no proporcionada.</p>";
    echo "<a href='../admin/autos/admin-panel.php'>Volver al panel</a>";
    exit();
  }

  // Calcular fechas de vencimiento
  $oblea_vencimiento = $fecha_oblea_realizada ? date('Y-m-d', strtotime($fecha_oblea_realizada . ' +1 year')) : null;
  $prueba_vencimiento = $fecha_prueba_realizada ? date('Y-m-d', strtotime($fecha_prueba_realizada . ' +5 years')) : null;

  // Verificar si ya existen vencimientos para esa patente
  $check = $conexion->prepare("SELECT vehiculo_patente FROM vencimientos WHERE vehiculo_patente = ?");
  $check->bind_param("s", $patente);
  $check->execute();
  $resultado = $check->get_result();
  $existe = $resultado->num_rows > 0;
  $check->close();

  if ($existe) {
    // Actualizar
    $stmt = $conexion->prepare("UPDATE vencimientos SET oblea = ?, prueba_hidraulica = ?, reprueba = ? WHERE vehiculo_patente = ?");
    $stmt->bind_param("ssss", $oblea_vencimiento, $prueba_vencimiento, $reprueba, $patente);
  } else {
    // Insertar
    $stmt = $conexion->prepare("INSERT INTO vencimientos (vehiculo_patente, oblea, prueba_hidraulica, reprueba) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $patente, $oblea_vencimiento, $prueba_vencimiento, $reprueba);
  }

  if ($stmt->execute()) {
    header("Location: ../clientes/ficha-cliente.php?patente=" . urlencode($patente));
    exit();
  } else {
    echo "<p>Error al guardar los vencimientos.</p>";
  }

  $stmt->close();
} else {
  echo "<p>Acceso no permitido.</p>";
}
?>
