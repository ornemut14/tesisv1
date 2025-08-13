<?php
require_once '../conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ========= CONFIG LOCAL ========= */
define('BASE_URL', 'http://localhost/nuevo-gnc'); // <- tu carpeta local

/* ========= Overlay helper ========= */
function renderOverlayPage($title, $message, $type = 'info', $duration = 5000, $redirectTo = null) {
  $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
  $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
  $type = in_array($type, ['success', 'error', 'info']) ? $type : 'info';
  $redirectJs = $redirectTo ? "window.location.href = '".htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8')."';"
                            : "history.back();";
  $bg = $type === 'success' ? '#e6f7f1' : ($type === 'error' ? '#fdecea' : '#eef2ff');
  $bd = $type === 'success' ? '#1b7f4b' : ($type === 'error' ? '#b00020' : '#3b5bdb');
  $tx = $type === 'success' ? '#0f5132' : ($type === 'error' ? '#842029' : '#1c2763');
  $duration = (int)$duration;
  echo <<<HTML
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
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
</head><body>
<div class="overlay"><div class="card">
  <h1>{$title}</h1><p>{$message}</p><p class="meta">Se cerrará automáticamente...</p>
</div></div>
<script>setTimeout(function(){ {$redirectJs} }, {$duration});</script>
</body></html>
HTML;
  exit;
}

/* ========= Logs (opcional) ========= */
@mkdir(__DIR__ . '/../logs', 0755, true);
$log = __DIR__ . '/../logs/mail.log';
function log_line($m){ global $log; @file_put_contents($log, "[".date('Y-m-d H:i:s')."] $m\n", FILE_APPEND); }

/* ========= GET: form ========= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  ?>
  <!doctype html><html lang="es"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Recuperar contraseña</title>
    <style>
      body{font-family:Arial, sans-serif; padding:24px; background:#f7f7f7; color:#222}
      .card{max-width:420px;margin:auto;background:#fff;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,.08);padding:20px}
      label{display:block;margin:10px 0 6px}
      input,button{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px}
      button{background:#016b3b;color:#fff;border:none;margin-top:10px;cursor:pointer}
      button:hover{opacity:.95}
    </style>
  </head><body>
    <div class="card">
      <h2>Recuperar contraseña</h2>
      <form method="POST">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" required>
        <button type="submit">Enviar enlace</button>
      </form>
    </div>
  </body></html>
  <?php
  exit;
}

/* ========= POST: procesar ========= */
$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  renderOverlayPage('Recuperación de contraseña',
    'Si el correo existe, te enviaremos un enlace para restablecer tu contraseña.',
    'success', 5500, BASE_URL . '/login/login.html');
}

// Buscar usuario
$stmt = $conexion->prepare("SELECT id, nombre FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($uid, $nombre);
$exists = $stmt->fetch();
$stmt->close();

if ($exists) {
  // Token 64 hex + expira 1h (calculado por MySQL para evitar desfases)
  $token = bin2hex(random_bytes(32));
  $upd = $conexion->prepare("
    UPDATE usuarios
       SET reset_token = ?,
           reset_expira = DATE_ADD(NOW(), INTERVAL 1 HOUR)
     WHERE id = ?
  ");
  $upd->bind_param("si", $token, $uid);
  $updOk = $upd->execute();
  $upd->close();

  if ($updOk) {
    // Link absoluto (local)
    $link = rtrim(BASE_URL, '/') . "/login/restablecer.php?token={$token}";

    // (Opcional) Mostrar el link en pantalla cuando estás en local:
    // echo $link; exit;

    // Envío de email (PHPMailer)
    $mail = new PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host       = 'smtp.gmail.com';
      $mail->SMTPAuth   = true;
      $mail->Username   = 'gncrivadavia1@gmail.com';
      $mail->Password   = 'dvcloeuomtxsbhun'; // App Password (16 chars sin espacios)
      $mail->CharSet    = 'UTF-8';
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port       = 587;

      $mail->setFrom('gncrivadavia1@gmail.com', 'GNC Rivadavia');
      $mail->addAddress($email, $nombre ?: $email);

      $mail->isHTML(true);
      $mail->Subject = 'Recuperar contraseña';
      $mail->Body    = "
        <p>Hola ".htmlspecialchars($nombre ?: '', ENT_QUOTES, 'UTF-8').",</p>
        <p>Para restablecer tu contraseña, hacé clic en este enlace (válido por 1 hora):</p>
        <p><a href=\"{$link}\">Restablecer contraseña</a></p>
        <p>Si no solicitaste esto, ignorá este mail.</p>";
      $mail->AltBody = "Restablecé tu contraseña: {$link} (válido por 1 hora)";

      $mail->send();
      log_line("Mail recovery enviado a $email");
    } catch (Exception $e) {
      log_line("Error mail recovery: " . $e->getMessage());
    }
  } else {
    log_line("No se pudo actualizar token/expira para $email");
  }
}

// Overlay genérico → login
renderOverlayPage('Recuperación de contraseña',
  'Si el correo existe, te enviaremos un enlace para restablecer tu contraseña.',
  'success', 5500, BASE_URL . '/login/login.html');
