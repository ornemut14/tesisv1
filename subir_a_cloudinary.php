<?php
require 'vendor/autoload.php';

use Cloudinary\Cloudinary;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configuración con variables del .env
$cloudinary = new Cloudinary([
  'cloud' => [
    'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'],
    'api_key'    => $_ENV['CLOUDINARY_API_KEY'],
    'api_secret' => $_ENV['CLOUDINARY_API_SECRET']
  ]
]);

// Validación de archivo
if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
  die('Error al subir la imagen');
}

$tmpPath = $_FILES['imagen']['tmp_name'];

try {
  $result = $cloudinary->uploadApi()->upload($tmpPath);
  echo "✅ Imagen subida: <a href='{$result['secure_url']}' target='_blank'>Ver imagen</a>";
} catch (Exception $e) {
  echo "❌ Error: " . $e->getMessage();
  
}
