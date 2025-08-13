<?php
require_once '../conexion.php';

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
<title>Notificación</title>
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

/* ========= Helpers ========= */
function render_form($token) {
  $token = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
  echo <<<HTML
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Restablecer contraseña</title>
<style>
  body{font-family:Arial, sans-serif; padding:24px; background:#f7f7f7; color:#222}
  .card{max-width:520px;margin:auto;background:#fff;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,.08);padding:20px}
  label{display:block;margin:10px 0 6px}
  input,button{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px}
  button{background:#016b3b;color:#fff;border:none;margin-top:10px;cursor:pointer}
  button:hover{opacity:.95}
  .pwd-reqs{margin:8px 0 12px; padding-left:18px; font-size:.9rem; line-height:1.4}
  .pwd-reqs li{margin:2px 0}
  .ok{color:#1b7f4b; font-weight:600}
  .bad{color:#b00020; font-weight:600}
  .error-text{color:#b00020; font-size:.9rem; display:none; margin-top:6px}
</style>
</head><body>
  <div class="card">
    <h2>Restablecer contraseña</h2>
    <form id="formReset" method="POST" novalidate>
      <input type="hidden" name="token" value="{$token}">
      <label for="password">Nueva contraseña</label>
      <input type="password" id="password" name="password" required
             minlength="8" maxlength="72"
             pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9])\\S{8,72}$"
             title="Mínimo 8 caracteres, incluir mayúscula, minúscula, número y símbolo. Sin espacios."
             oninput="validarPwd()">

      <ul class="pwd-reqs">
        <li id="r-len" class="bad">Al menos 8 caracteres</li>
        <li id="r-low" class="bad">Al menos 1 minúscula (a-z)</li>
        <li id="r-up"  class="bad">Al menos 1 mayúscula (A-Z)</li>
        <li id="r-num" class="bad">Al menos 1 número (0-9)</li>
        <li id="r-sym" class="bad">Al menos 1 símbolo (!@#$%...)</li>
        <li id="r-spc" class="bad">Sin espacios</li>
      </ul>

      <label for="password2">Confirmar contraseña</label>
      <input type="password" id="password2" name="password2" required oninput="validarPwd()">
      <div id="matchError" class="error-text">Las contraseñas no coinciden.</div>

      <button id="btnGuardar" type="submit" disabled>Guardar nueva contraseña</button>
    </form>
  </div>

<script>
function setState(el, ok){ el.classList.toggle('ok', ok); el.classList.toggle('bad', !ok); }

function validarPwd(){
  const p1 = document.getElementById('password').value;
  const p2 = document.getElementById('password2').value;

  const okLen = p1.length >= 8 && p1.length <= 72;
  const okLow = /[a-z]/.test(p1);
  const okUp  = /[A-Z]/.test(p1);
  const okNum = /\\d/.test(p1);
  const okSym = /[^A-Za-z0-9]/.test(p1);
  const okSpc = !/\\s/.test(p1);

  setState(document.getElementById('r-len'), okLen);
  setState(document.getElementById('r-low'), okLow);
  setState(document.getElementById('r-up'),  okUp);
  setState(document.getElementById('r-num'), okNum);
  setState(document.getElementById('r-sym'), okSym);
  setState(document.getElementById('r-spc'), okSpc);

  const allOk = okLen && okLow && okUp && okNum && okSym && okSpc;
  const match = p1 === p2 && p2.length > 0;

  document.getElementById('btnGuardar').disabled = !(allOk && match);
  document.getElementById('matchError').style.display = match || p2.length === 0 ? 'none' : 'block';
}

// Validación final coherente con el pattern
document.getElementById('formReset').addEventListener('submit', function(e){
  const pwd = document.getElementById('password');
  const conf = document.getElementById('password2');
  if(!pwd.checkValidity()){
    e.preventDefault();
    pwd.reportValidity();
    return;
  }
  if(pwd.value !== conf.value){
    e.preventDefault();
    document.getElementById('matchError').style.display = 'block';
    conf.focus();
  }
});
</script>
</body></html>
HTML;
  exit;
}

/* ========= GET: validar y mostrar form ========= */
$token = trim($_GET['token'] ?? '');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  if ($token === '') {
    renderOverlayPage('Error', 'Token faltante.', 'error', 5000, 'recuperar.php');
  }
  // Validar token vigente usando MySQL (evita desfases de hora)
  $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE reset_token=? AND reset_expira > NOW()");
  $stmt->bind_param("s", $token);
  $stmt->execute();
  $stmt->bind_result($id);
  $valid = $stmt->fetch();
  $stmt->close();

  if (!$valid) {
    renderOverlayPage('Token inválido o vencido', 'Volvé a solicitar el enlace.', 'error', 6000, 'recuperar.php');
  }
  render_form($token);
}

/* ========= POST: actualizar ========= */
$token = trim($_POST['token'] ?? '');
$pass  = $_POST['password']  ?? '';
$pass2 = $_POST['password2'] ?? '';

if ($token === '') {
  renderOverlayPage('Error', 'Token faltante.', 'error', 5000, 'recuperar.php');
}
if ($pass !== $pass2) {
  renderOverlayPage('Error', 'Las contraseñas no coinciden.', 'error', 5000, null);
}

// Validación backend (igual que el patrón del front)
if (
  strlen($pass) < 8 || strlen($pass) > 72 ||
  !preg_match('/[a-z]/', $pass) ||
  !preg_match('/[A-Z]/', $pass) ||
  !preg_match('/\d/', $pass) ||
  !preg_match('/[^A-Za-z0-9]/', $pass) ||
  preg_match('/\s/', $pass)
) {
  renderOverlayPage('Error', 'La contraseña no cumple los requisitos.', 'error', 6000, null);
}

// Confirmar token vigente otra vez (por seguridad)
$stmt = $conexion->prepare("SELECT id FROM usuarios WHERE reset_token=? AND reset_expira > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->bind_result($id);
$valid = $stmt->fetch();
$stmt->close();

if (!$valid) {
  renderOverlayPage('Token inválido o vencido', 'Volvé a solicitar el enlace.', 'error', 6000, 'recuperar.php');
}

// Actualizar pass y limpiar token
$hash = password_hash($pass, PASSWORD_DEFAULT);
$upd  = $conexion->prepare("UPDATE usuarios SET contraseña=?, reset_token=NULL, reset_expira=NULL WHERE id=?");
$upd->bind_param("si", $hash, $id);
if ($upd->execute()) {
  renderOverlayPage('Listo', 'Contraseña restablecida correctamente. Te llevamos al inicio de sesión.', 'success', 4500, 'login.html');
} else {
  renderOverlayPage('Error', 'No se pudo actualizar. Intentá nuevamente.', 'error', 6000, null);
}
