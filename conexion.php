<?php
$host = 'localhost';        // o 127.0.0.1
$usuario = 'root';          // usuario por defecto en XAMPP
$contraseña = '';           // vacío si no le pusiste password en phpMyAdmin
$base_de_datos = 'taller_gnc';

// Crear conexión
$conexion = new mysqli($host, $usuario, $contraseña, $base_de_datos);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error al conectar con la base de datos: " . $conexion->connect_error);
}

// Opcional: establecer codificación
$conexion->set_charset("utf8mb4");
?>
