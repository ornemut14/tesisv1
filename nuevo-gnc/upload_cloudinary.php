<?php
require_once __DIR__ . '/vendor/autoload.php';

use Cloudinary\Cloudinary;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$cloudinary = new Cloudinary([
  'cloud' => [
    'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'],
    'api_key'    => $_ENV['CLOUDINARY_API_KEY'],
    'api_secret' => $_ENV['CLOUDINARY_API_SECRET'],
  ],
]);

function subirACloudinary($tmp_path, $public_id_prefix) {
  global $cloudinary;

  $resultado = $cloudinary->uploadApi()->upload($tmp_path, [
    'folder' => 'autos',
    'public_id' => $public_id_prefix . '_' . uniqid()
  ]);

  return $resultado['secure_url']; // URL pública
}
