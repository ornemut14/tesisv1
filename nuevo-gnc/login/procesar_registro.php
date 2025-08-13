<?php
require_once '../conexion.php';
session_start();

// ---- Helpers ----
function bad_request($html) {
  http_response_code(400);
  echo $html;
  exit;
}
function see_other($url) {
  // For success cases that redirect
  http_response_code(303);
  header("Location: $url");
  exit;
}

/**
 * Renders a centered overlay with auto-dismiss and optional redirect.
 * $type: "success" | "error" | "info"
 * $duration: milliseconds the overlay remains visible before acting
 */
function renderOverlayPage($title, $message, $type = 'info', $duration = 5000, $redirectTo = null) {
  $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
  $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
  $type = in_array($type, ['success', 'error', 'info']) ? $type : 'info';
  $duration = (int)$duration;
  $redirectJs = $redirectTo ? "window.location.href = '".htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8')."';" : "history.back();";

  // Minimal inline styles (podés moverlos a tu CSS)
  $bg = $type === 'success' ? '#e6f7f1' : ($type === 'error' ? '#fdecea' : '#eef2ff');
  $bd = $type === 'success' ? '#1b7f4b' : ($type === 'error' ? '#b00020' : '#3b5bdb');
  $tx = $type === 'success' ? '#0f5132' : ($type === 'error' ? '#842029' : '#1c2763');

  echo <<<HTML
<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>{$title}</title>
<style>
  html,body{height:100%;margin:0;font-family:Montserrat,Arial,sans-serif;background:#fafafa}
  .overlay{min-height:100%;display:flex;align-items:center;justify-content:center;padding:24px}
  .card{max-width:560px;width:100%;background:{$bg};border:2px solid {$bd};color:{$tx};
        border-radius:14px;box-shadow:0 6px 24px rgba(0,0,0,.08);padding:22px}
  h1{margin:0 0 8px;font-size:22px}
  p{margin:0 0 6px;line-height:1.5}
  .meta{font-size:.9rem;opacity:.8}
</style>
</head>
<body>
<div class="overlay">
  <div class="card">
    <h1>{$title}</h1>
    <p>{$message}</p>
    <p class="meta">Se cerrará automáticamente...</p>
  </div>
</div>
<script>
  setTimeout(function(){ {$redirectJs} }, {$duration});
</script>
</body></html>
HTML;
}

// ---- Read + validate inputs ----
$nombre   = trim($_POST['nombre'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$rol      = 'cliente'; // fijo

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  bad_request(renderOverlayPage(
    'Email inválido',
    'Revisá el formato del correo electrónico.',
    'error',
    5000
  ));
}

// Password policy (72 bytes is bcrypt limit; strlen counts bytes)
if (
  strlen($password) < 8 || strlen($password) > 72 ||
  !preg_match('/[a-z]/', $password) ||
  !preg_match('/[A-Z]/', $password) ||
  !preg_match('/\d/', $password) ||
  !preg_match('/[^A-Za-z0-9]/', $password) ||
  preg_match('/\s/', $password)
) {
  bad_request(renderOverlayPage(
    'Contraseña débil',
    'Debe tener 8+ caracteres, mayúscula, minúscula, número y símbolo, sin espacios.',
    'error',
    6000
  ));
}

// Hash (PASSWORD_DEFAULT hoy suele ser bcrypt o Argon2 según versión)
$hash = password_hash($password, PASSWORD_DEFAULT);
if ($hash === false) {
  bad_request(renderOverlayPage(
    'Error interno',
    'No se pudo procesar la contraseña.',
    'error',
    5000
  ));
}

// ---- Check duplicate email ----
$verificar = $conexion->prepare("SELECT id FROM usuarios WHERE email = ?");
$verificar->bind_param("s", $email);
$verificar->execute();
$verificar->store_result();

if ($verificar->num_rows > 0) {
  $verificar->close();
  bad_request(renderOverlayPage(
    'Correo ya registrado',
    'El correo ingresado ya existe. Probá iniciar sesión o recuperá tu contraseña.',
    'error',
    6000
  ));
}
$verificar->close();

// ---- Insert user ----
$stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, contraseña, rol) VALUES (?, ?, ?, ?)");
if (!$stmt) {
  bad_request(renderOverlayPage(
    'Error de base de datos',
    'No se pudo preparar la consulta.',
    'error',
    5000
  ));
}
$stmt->bind_param("ssss", $nombre, $email, $hash, $rol);
$ok = $stmt->execute();
$stmt->close();

// ---- Final response ----
if ($ok) {
  // Mostramos overlay 4.5s y redirigimos a login
  renderOverlayPage(
    'Registro exitoso',
    'Tu cuenta fue creada correctamente. Te redirigimos al inicio de sesión.',
    'success',
    4500,
    'login.html'
  );
  exit;
} else {
  bad_request(renderOverlayPage(
    'No se pudo registrar',
    'Ocurrió un problema al guardar tus datos. Intentá nuevamente.',
    'error',
    6000
  ));
}
