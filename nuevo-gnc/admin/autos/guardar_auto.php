<?php
require_once '../../conexion.php';
require_once '../../vendor/autoload.php';

use Cloudinary\Cloudinary;
use Dotenv\Dotenv;

session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../login/login.html"); exit();
}

// Cargar .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../'); 
$dotenv->load();

// Cloudinary
$cloudinary = new Cloudinary([
  'cloud' => [
    'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'],
    'api_key'    => $_ENV['CLOUDINARY_API_KEY'],
    'api_secret' => $_ENV['CLOUDINARY_API_SECRET']
  ]
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // ---- Datos del formulario ----
  $patente        = strtoupper(trim($_POST['patente'] ?? ''));
  $modelo         = trim($_POST['modelo'] ?? '');
  $cliente_nombre = trim($_POST['cliente'] ?? '');
  $correo         = trim($_POST['correo'] ?? '');
  $telefono       = trim($_POST['telefono'] ?? '');

  // (opcional) normalizar teléfono simple
  // $telefono = preg_replace('/[^0-9+()\- ]/', '', $telefono);

  if ($patente === '' || $modelo === '' || $cliente_nombre === '' || $correo === '') {
    echo "❌ Faltan datos obligatorios."; exit();
  }

  try {
    // ---- Función de subida a Cloudinary ----
    $subir = function($tmp, $folder, $suffix) use ($cloudinary, $patente) {
      if (!is_uploaded_file($tmp)) {
        throw new Exception("No se recibió el archivo para $suffix.");
      }
      $r = $cloudinary->uploadApi()->upload($tmp, [
        'folder'     => $folder,
        'public_id'  => $patente . '_' . $suffix . '_' . uniqid(),
        'overwrite'  => false,
        'invalidate' => true
      ]);
      return $r['secure_url'];
    };

    // ---- Subir 4 imágenes ----
    $dniFrenteURL = $subir($_FILES['dni_frente']['tmp_name'],   'gnc/dni',     'dni_frente');
    $dniDorsoURL  = $subir($_FILES['dni_dorso']['tmp_name'],    'gnc/dni',     'dni_dorso');
    $tarFrenteURL = $subir($_FILES['tarjeta_frente']['tmp_name'],'gnc/tarjeta', 'tarjeta_frente');
    $tarDorsoURL  = $subir($_FILES['tarjeta_dorso']['tmp_name'],'gnc/tarjeta', 'tarjeta_dorso');

    // ---- Buscar cliente por correo ----
    $stmt = $conexion->prepare("SELECT id, nombre, telefono FROM clientes WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
      // Existe: opcionalmente actualizar nombre/teléfono si cambiaron
      $row = $res->fetch_assoc();
      $cliente_id = (int)$row['id'];
      $stmt->close();

      // Solo actualiza si hay cambios
      $debeActualizar = false;
      $nuevoNombre = $row['nombre'];
      $nuevoTelefono = $row['telefono'];

      if ($cliente_nombre !== '' && $cliente_nombre !== $row['nombre']) {
        $nuevoNombre = $cliente_nombre;
        $debeActualizar = true;
      }
      if ($telefono !== '' && $telefono !== $row['telefono']) {
        $nuevoTelefono = $telefono;
        $debeActualizar = true;
      }

      if ($debeActualizar) {
        $upd = $conexion->prepare("UPDATE clientes SET nombre = ?, telefono = ? WHERE id = ?");
        $upd->bind_param("ssi", $nuevoNombre, $nuevoTelefono, $cliente_id);
        $upd->execute();
        $upd->close();
      }
    } else {
      // No existe: crear con teléfono
      $stmt->close();
      $ins = $conexion->prepare("INSERT INTO clientes (nombre, correo, telefono) VALUES (?, ?, ?)");
      $ins->bind_param("sss", $cliente_nombre, $correo, $telefono);
      $ins->execute();
      $cliente_id = $ins->insert_id;
      $ins->close();
    }

    // ---- Crear vehículo ----
    $stmt2 = $conexion->prepare("INSERT INTO vehiculos (patente, modelo, cliente_id) VALUES (?, ?, ?)");
    $stmt2->bind_param("ssi", $patente, $modelo, $cliente_id);
    $stmt2->execute();
    $stmt2->close();

    // ---- Guardar imágenes por tipo en `imagenes` ----
    $sqlImg = $conexion->prepare(
      "REPLACE INTO imagenes (titulo, url, vehiculo_patente, tipo) VALUES (?,?,?,?)"
    );

    $titulo = $patente . ' DNI Frente';   $tipo = 'dni_frente';       $url = $dniFrenteURL;
    $sqlImg->bind_param("ssss", $titulo, $url, $patente, $tipo);  $sqlImg->execute();

    $titulo = $patente . ' DNI Dorso';    $tipo = 'dni_dorso';        $url = $dniDorsoURL;
    $sqlImg->bind_param("ssss", $titulo, $url, $patente, $tipo);  $sqlImg->execute();

    $titulo = $patente . ' Tarjeta Frente'; $tipo = 'tarjeta_frente'; $url = $tarFrenteURL;
    $sqlImg->bind_param("ssss", $titulo, $url, $patente, $tipo);  $sqlImg->execute();

    $titulo = $patente . ' Tarjeta Dorso';  $tipo = 'tarjeta_dorso';  $url = $tarDorsoURL;
    $sqlImg->bind_param("ssss", $titulo, $url, $patente, $tipo);  $sqlImg->execute();

    $sqlImg->close();

    // ---- Vencimientos iniciales ----
    $conexion->query("
      INSERT INTO vencimientos (vehiculo_patente, oblea, prueba_hidraulica, reprueba) 
      VALUES ('$patente', NULL, NULL, NULL)
    ");

    // ---- Redirigir a ficha ----
    header("Location: ../../clientes/ficha-cliente.php?patente=$patente"); 
    exit();

  } catch (Exception $e) {
    echo "❌ Error al procesar la solicitud: " . $e->getMessage();
  }
} else {
  echo "<p>Acceso denegado.</p>";
}
