<?php
require_once '../conexion.php';
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patente = $_POST['patente'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';

    if ($patente && $fecha && $descripcion) {
        $stmt = $conexion->prepare("INSERT INTO servicios (vehiculo_patente, fecha, descripcion) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $patente, $fecha, $descripcion);

        if ($stmt->execute()) {
            header("Location: ../clientes/ficha-cliente.php?patente=" . urlencode($patente));
            exit();
        } else {
            echo "<p>Error al guardar el servicio.</p>";
        }

        $stmt->close();
    } else {
        echo "<p>Faltan datos del formulario.</p>";
    }
} else {
    echo "<p>Acceso no permitido.</p>";
}
?>
