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

  // --- Datos del formulario ---
  $patente  = trim($_POST['patente']  ?? '');
  $modelo   = trim($_POST['modelo']   ?? '');
  $cliente  = trim($_POST['cliente']  ?? '');
  $correo   = trim($_POST['correo']   ?? '');
  $telefono = trim($_POST['telefono'] ?? '');

  if ($patente === '') { echo "Patente requerida."; exit(); }

  // Buscar cliente_id a partir de la patente
  $stmt = $conexion->prepare("SELECT cliente_id FROM vehiculos WHERE patente = ?");
  $stmt->bind_param("s", $patente);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) { echo "Vehículo no encontrado"; exit(); }
  $cliente_id = (int)$res->fetch_assoc()['cliente_id'];
  $stmt->close();

  // Actualizar datos del cliente (incluye teléfono)
  $upCli = $conexion->prepare("UPDATE clientes SET nombre = ?, correo = ?, telefono = ? WHERE id = ?");
  $upCli->bind_param("sssi", $cliente, $correo, $telefono, $cliente_id);
  $upCli->execute();
  $upCli->close();

  // Actualizar modelo del vehículo (si vino)
  if ($modelo !== '') {
    $upVeh = $conexion->prepare("UPDATE vehiculos SET modelo = ? WHERE patente = ?");
    $upVeh->bind_param("ss", $modelo, $patente);
    $upVeh->execute();
    $upVeh->close();
  }

  // Helper de subida a Cloudinary
  $upload = function(string $tmpPath, string $folder, string $suffix) use ($cloudinary, $patente): string {
    $r = $cloudinary->uploadApi()->upload($tmpPath, [
      'folder'     => $folder,
      'public_id'  => $patente . '_' . $suffix . '_' . uniqid(),
      'overwrite'  => false,
      'invalidate' => true
    ]);
    return $r['secure_url'];
  };

  // Guardado en tabla `imagenes` (una fila por tipo)
  // Usamos REPLACE para que reemplace la existente (requiere UNIQUE en (vehiculo_patente, tipo))
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

  // Subir solo si se envió cada archivo
  $tryUpload('dni_frente',      'gnc/dni',     'dni_frente',     'DNI Frente',     'dni_frente');
  $tryUpload('dni_dorso',       'gnc/dni',     'dni_dorso',      'DNI Dorso',      'dni_dorso');
  $tryUpload('tarjeta_frente',  'gnc/tarjeta', 'tarjeta_frente', 'Tarjeta Frente', 'tarjeta_frente');
  $tryUpload('tarjeta_dorso',   'gnc/tarjeta', 'tarjeta_dorso',  'Tarjeta Dorso',  'tarjeta_dorso');

  $saveImg->close();

  header("Location: ficha-cliente.php?patente=" . urlencode($patente));
  exit();
} else {
  echo "Acceso no permitido";
}
