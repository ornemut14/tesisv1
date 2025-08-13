<?php
use Cloudinary\Cloudinary;
use Dotenv\Dotenv;

session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../login/login.html");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_once '../conexion.php';
  require_once '../vendor/autoload.php';

  // Cargar variables de entorno
  $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
  $dotenv->load();

  // Instancia Cloudinary
  $cloudinary = new Cloudinary([
    'cloud' => [
      'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'],
      'api_key'    => $_ENV['CLOUDINARY_API_KEY'],
      'api_secret' => $_ENV['CLOUDINARY_API_SECRET']
    ]
  ]);

  $patente     = $_POST['patente']     ?? '';
  $modelo      = $_POST['modelo']      ?? '';
  $cliente     = $_POST['cliente']     ?? '';
  $correo      = $_POST['correo']      ?? '';
  $telefono    = $_POST['telefono']    ?? '';
  $cliente_id  = $_POST['cliente_id']  ?? null;

  if (empty($cliente_id)) {
    echo "<p>Error: cliente_id no proporcionado.</p>";
    exit();
  }

  // Actualizar datos del cliente (incluye teléfono)
  $update = $conexion->prepare("UPDATE clientes SET nombre = ?, correo = ?, telefono = ? WHERE id = ?");
  $update->bind_param("sssi", $cliente, $correo, $telefono, $cliente_id);
  $update->execute();
  $update->close();

  // Actualizar modelo del vehículo
  $updateVehiculo = $conexion->prepare("UPDATE vehiculos SET modelo = ? WHERE patente = ?");
  $updateVehiculo->bind_param("ss", $modelo, $patente);
  $updateVehiculo->execute();
  $updateVehiculo->close();

  // Helper subida Cloudinary
  $upload = function(string $tmpPath, string $folder, string $suffix) use ($cloudinary, $patente): string {
    $r = $cloudinary->uploadApi()->upload($tmpPath, [
      'folder'     => $folder,
      'public_id'  => $patente . '_' . $suffix . '_' . uniqid(),
      'overwrite'  => false,
      'invalidate' => true
    ]);
    return $r['secure_url'];
  };

  // Guardar SOLO las imágenes nuevas en tabla `imagenes`
  // REPLACE asegura 1 por (vehiculo_patente, tipo)
  $saveImg = $conexion->prepare("REPLACE INTO imagenes (titulo, url, vehiculo_patente, tipo) VALUES (?, ?, ?, ?)");

  $tryUpload = function(string $fileKey, string $folder, string $suffix, string $tituloBase, string $tipo)
                use ($upload, $saveImg, $patente) {
    if (!empty($_FILES[$fileKey]['name']) && is_uploaded_file($_FILES[$fileKey]['tmp_name'])) {
      $url = $upload($_FILES[$fileKey]['tmp_name'], $folder, $suffix);
      $titulo = $patente . ' ' . $tituloBase;
      $saveImg->bind_param("ssss", $titulo, $url, $patente, $tipo);
      $saveImg->execute();
    }
  };

  // DNI
  $tryUpload('dni_frente',     'gnc/dni',     'dni_frente',     'DNI Frente',       'dni_frente');
  $tryUpload('dni_dorso',      'gnc/dni',     'dni_dorso',      'DNI Dorso',        'dni_dorso');
  // Tarjeta Verde
  $tryUpload('tarjeta_frente', 'gnc/tarjeta', 'tarjeta_frente', 'Tarjeta Frente',   'tarjeta_frente');
  $tryUpload('tarjeta_dorso',  'gnc/tarjeta', 'tarjeta_dorso',  'Tarjeta Dorso',    'tarjeta_dorso');

  $saveImg->close();

  header("Location: ficha-cliente.php?patente=" . urlencode($patente));
  exit();

} else {
  echo "<p>Acceso no permitido.</p>";
}
