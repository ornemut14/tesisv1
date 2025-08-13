<?php
session_start();
require_once '../conexion.php';
require_once '../admin/includes/header-admin.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../login/login.html");
  exit();
}

$patente = $_GET['patente'] ?? null;

if (!$patente) {
  echo "<p>Patente no proporcionada.</p><a href='../admin/autos/admin-panel.php'>Volver</a>";
  exit();
}

// >>> Opción B: traer imágenes desde la tabla `imagenes` por tipo
$stmt = $conexion->prepare("
  SELECT
    v.modelo,
    c.nombre AS cliente,
    c.correo,
    c.telefono,
    v.patente,
    i1.url AS dni_frente_url,
    i2.url AS dni_dorso_url,
    i3.url AS tarjeta_frente_url,
    i4.url AS tarjeta_dorso_url,
    vnc.oblea,
    vnc.prueba_hidraulica,
    vnc.reprueba
  FROM vehiculos v
  JOIN clientes c ON v.cliente_id = c.id
  LEFT JOIN vencimientos vnc
         ON v.patente = vnc.vehiculo_patente
  LEFT JOIN imagenes i1
         ON i1.vehiculo_patente = v.patente AND i1.tipo = 'dni_frente'
  LEFT JOIN imagenes i2
         ON i2.vehiculo_patente = v.patente AND i2.tipo = 'dni_dorso'
  LEFT JOIN imagenes i3
         ON i3.vehiculo_patente = v.patente AND i3.tipo = 'tarjeta_frente'
  LEFT JOIN imagenes i4
         ON i4.vehiculo_patente = v.patente AND i4.tipo = 'tarjeta_dorso'
  WHERE v.patente = ?
");
$stmt->bind_param("s", $patente);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
  echo "<p>No se encontró la patente.</p><a href='../admin/autos/admin-panel.php'>Volver</a>";
  exit();
}

$auto = $resultado->fetch_assoc();
$stmt->close();

// Servicios
$servicios = [];
$servicio_stmt = $conexion->prepare("
  SELECT id, fecha, descripcion
  FROM servicios
  WHERE vehiculo_patente = ? AND activo = 1
  ORDER BY fecha DESC
");
$servicio_stmt->bind_param("s", $patente);
$servicio_stmt->execute();
$res_servicios = $servicio_stmt->get_result();
while ($row = $res_servicios->fetch_assoc()) {
  $servicios[] = $row;
}
$servicio_stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ficha del Cliente</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; background: #f4f4f4; color: #333; padding-top: 80px; }

.header {
  background-color: #0b4d23; color: white; position: fixed; top: 0; left: 0; right: 0;
  height: 70px; display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; z-index: 1000;
}
.header img { height: 45px; }
.nav-links { display: flex; gap: 1.5rem; }
.nav-links a { color: white; text-decoration: none; font-weight: bold; }
.nav-links a:hover { text-decoration: underline; }

.container { width: 100%; padding: 2rem; }
h1, h2 { color: #0b4d23; margin-bottom: 1rem; }
p { margin-bottom: 0.75rem; font-size: 1rem; }
.label { font-weight: bold; color: #0b4d23; }

.btn {
  display: inline-block; margin-top: 1rem; padding: 0.5rem 1.2rem; background: #f1c40f; color: black;
  text-decoration: none; border-radius: 5px; font-weight: bold; transition: background 0.2s ease;
}
.btn:hover { background: #d4ac0d; }
.btn-danger { background: #dc3545; color: white; }
.btn-danger:hover { background: #c0392b; }
.btn-group-right { display: flex; justify-content: flex-end; gap: 0.5rem; flex-wrap: wrap; margin-top: 1rem; }
.text-right { text-align: right; }

.ficha-cliente { display: flex; justify-content: space-between; gap: 2rem; flex-wrap: wrap; margin-bottom: 2rem; }
.datos-cliente { flex: 1; min-width: 250px; }

/* Bloques de imágenes */
.imagenes-cliente { flex: 1; display: flex; flex-direction: column; gap: 1.2rem; }
.fila-imagenes { display: flex; gap: 1.2rem; flex-wrap: wrap; justify-content: center; }
.card-img { text-align: center; }
.card-img p { margin-bottom: 0.5rem; font-weight: 600; }
.card-img img {
  max-width: 220px; height: auto; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  background: #fff;
}

table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.95rem; }
th, td { padding: 0.75rem; border-bottom: 1px solid #ddd; }
th { background-color: #0b4d23; color: white; font-weight: bold; }

/* RESPONSIVE */
@media (max-width: 768px) {
  .container { padding: 1rem; }
  .ficha-cliente { flex-direction: column; align-items: flex-start; }
  .fila-imagenes { justify-content: flex-start; }
  table, thead, tbody, th, td, tr { display: block; }
  tr { margin-bottom: 1rem; border: 1px solid #ccc; border-radius: 6px; padding: 0.5rem; background: white; }
  td { padding: 0.5rem 0; text-align: left; }
  td[data-label]::before { content: attr(data-label); display: block; font-weight: bold; color: #0b4d23; margin-bottom: 0.3rem; }
  th { display: none; }
  .btn-group-right { justify-content: center; }
  .text-right { text-align: left; }
}
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="header">
  <img src="../landing/assets/img/LOGO.png" alt="Logo">
  <div class="nav-links">
    <a href="/nuevo-gnc/admin/autos/admin-panel.php">Buscar por patente</a>
    <a href="/nuevo-gnc/admin/ver_turnos.php" target="_blank">Ver turnos</a>
    <a href="/nuevo-gnc/verificar_vencimientos.php" target="_blank">Verificar Notificaciones</a>
  </div>
</div>

<div class="container">
  <h1>Ficha del Cliente</h1>

  <div class="ficha-cliente">
    <div class="datos-cliente">
      <p><span class="label"><i class="fa-solid fa-car"></i> Patente:</span> <?= htmlspecialchars($auto['patente']) ?></p>
      <p><span class="label"><i class="fa-solid fa-cogs"></i> Modelo:</span> <?= htmlspecialchars($auto['modelo']) ?></p>
      <p><span class="label"><i class="fa-solid fa-user"></i> Cliente:</span> <?= htmlspecialchars($auto['cliente']) ?></p>
      <p><span class="label"><i class="fa-solid fa-envelope"></i> Correo:</span> <?= htmlspecialchars($auto['correo']) ?></p>
      <p><span class="label"><i class="fa-solid fa-phone"></i> Teléfono:</span> <?= htmlspecialchars($auto['telefono'] ?? '—') ?></p>
    </div>

    <div class="imagenes-cliente">
      <!-- Fila 1: DNI Frente / DNI Dorso -->
      <div class="fila-imagenes">
        <div class="card-img">
          <p>DNI - Frente</p>
          <?php if (!empty($auto['dni_frente_url'])): ?>
            <img src="<?= htmlspecialchars($auto['dni_frente_url']) ?>" alt="DNI Frente">
          <?php else: ?>
            <small>Sin imagen</small>
          <?php endif; ?>
        </div>
        <div class="card-img">
          <p>DNI - Dorso</p>
          <?php if (!empty($auto['dni_dorso_url'])): ?>
            <img src="<?= htmlspecialchars($auto['dni_dorso_url']) ?>" alt="DNI Dorso">
          <?php else: ?>
            <small>Sin imagen</small>
          <?php endif; ?>
        </div>
      </div>

      <!-- Fila 2: Tarjeta Frente / Tarjeta Dorso -->
      <div class="fila-imagenes">
        <div class="card-img">
          <p>Tarjeta Verde - Frente</p>
          <?php if (!empty($auto['tarjeta_frente_url'])): ?>
            <img src="<?= htmlspecialchars($auto['tarjeta_frente_url']) ?>" alt="Tarjeta Verde Frente">
          <?php else: ?>
            <small>Sin imagen</small>
          <?php endif; ?>
        </div>
        <div class="card-img">
          <p>Tarjeta Verde - Dorso</p>
          <?php if (!empty($auto['tarjeta_dorso_url'])): ?>
            <img src="<?= htmlspecialchars($auto['tarjeta_dorso_url']) ?>" alt="Tarjeta Verde Dorso">
          <?php else: ?>
            <small>Sin imagen</small>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="btn-group-right">
    <a class="btn" href="editar_ficha.php?patente=<?= htmlspecialchars($patente) ?>"><i class="fa-solid fa-pen"></i></a>
    <a class="btn btn-danger" href="eliminar_vehiculo.php?patente=<?= htmlspecialchars($patente) ?>" onclick="return confirm('¿Estás seguro de que querés eliminar este vehículo?');"><i class="fa-solid fa-trash"></i></a>
  </div>

  <h2>Historial de Servicios</h2>
  <table>
    <thead>
      <tr><th>Fecha</th><th>Descripción</th><th>Acciones</th></tr>
    </thead>
    <tbody>
      <?php if (count($servicios) > 0): ?>
        <?php foreach ($servicios as $servicio): ?>
          <tr>
            <td data-label="Fecha"><?= htmlspecialchars($servicio['fecha']) ?></td>
            <td data-label="Descripción"><?= htmlspecialchars($servicio['descripcion']) ?></td>
            <td data-label="Acciones" class="text-right">
              <a href="../servicios/editar_servicio.php?id=<?= $servicio['id'] ?>&patente=<?= urlencode($patente) ?>"><i class="fa-solid fa-pen"></i></a>
              <a href="../servicios/eliminar_servicio.php?id=<?= $servicio['id'] ?>&patente=<?= urlencode($patente) ?>" onclick="return confirm('¿Eliminar este servicio?');"><i class="fa-solid fa-trash"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="3">No hay servicios cargados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="btn-group-right">
    <a class="btn" href="/nuevo-gnc/servicios/agregar_servicio.php?patente=<?= urlencode($patente) ?>">+ Agregar Servicio</a>
  </div>

  <h2>Vencimientos</h2>
  <p><span class="label">Oblea:</span> <?= $auto['oblea'] ?: 'No asignado' ?></p>
  <p><span class="label">Prueba Hidráulica:</span> <?= $auto['prueba_hidraulica'] ?: 'No asignado' ?></p>

  <div class="btn-group-right">
    <a class="btn" href="../servicios/editar_vencimientos.php?patente=<?= urlencode($patente) ?>"><i class="fa-solid fa-pen"></i></a>
    <a class="btn" href="../admin/autos/admin-panel.php">←</a>
  </div>
</div>

</body>
</html>
