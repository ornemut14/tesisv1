<?php
session_start();
require_once '../conexion.php';
require_once '../admin/includes/header-admin.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login/login.html");
    exit();
}

$patente = $_GET['patente'] ?? '';

if (empty($patente)) {
    echo "<p>Patente no proporcionada.</p><a href='../admin/autos/admin-panel.php'>Volver</a>";
    exit();
}

// Traer datos + 4 URLs desde la tabla `imagenes` por tipo (Opción B)
$stmt = $conexion->prepare("
  SELECT v.modelo, v.patente,
         c.id AS cliente_id, c.nombre AS cliente, c.correo, c.telefono,
         i1.url AS dni_frente_url,
         i2.url AS dni_dorso_url,
         i3.url AS tarjeta_frente_url,
         i4.url AS tarjeta_dorso_url
  FROM vehiculos v
  JOIN clientes c ON v.cliente_id = c.id
  LEFT JOIN imagenes i1 ON i1.vehiculo_patente = v.patente AND i1.tipo = 'dni_frente'
  LEFT JOIN imagenes i2 ON i2.vehiculo_patente = v.patente AND i2.tipo = 'dni_dorso'
  LEFT JOIN imagenes i3 ON i3.vehiculo_patente = v.patente AND i3.tipo = 'tarjeta_frente'
  LEFT JOIN imagenes i4 ON i4.vehiculo_patente = v.patente AND i4.tipo = 'tarjeta_dorso'
  WHERE v.patente = ?
");
$stmt->bind_param("s", $patente);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    echo "<p>Patente no encontrada.</p><a href='../admin/autos/admin-panel.php'>Volver</a>";
    exit();
}

$auto = $resultado->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Ficha del Cliente</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      --verde: #0b4d23;
      --verde-hover: #09361a;
      --gris-fondo: #f9f9f9;
      --sombra: rgba(0, 0, 0, 0.08);
    }
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: var(--gris-fondo);
      padding: 6rem 1rem 2rem;
    }
        /* ================== HEADER VERDE ================== */
      /* Forzar que las opciones estén siempre a la derecha */
.header {
  display: flex;
  align-items: center;
  justify-content: space-between; /* separa logo a la izq, links a la der */
}

.nav-links {
  margin-left: auto; /* empuja las opciones hacia la derecha */
  display: flex;
  gap: 1.5rem;
}
    /* Logo fijo arriba a la izquierda sobre el header */
.logo-slot {
  position: fixed;
  top: 0.5rem;    /* distancia desde arriba */
  left: 1rem;     /* distancia desde la izquierda */
  height: 50px;   /* tamaño del contenedor */
  z-index: 1100;  /* más que el header (que tiene 1000) */
}

.logo-slot img {
  max-height: 100%;
  height: auto;
  width: auto;
  display: block;
  background: transparent; /* evita fondo blanco por CSS */
  border: none;            /* elimina bordes */
  outline: none;           /* elimina contorno */
}

    .card {
      background-color: white;
      max-width: 1200px;
      margin: auto;
      padding: 3rem 2rem;
      border-radius: 12px;
      box-shadow: 0 8px 20px var(--sombra);
    }
    h2 {
      text-align: center;
      color: var(--verde);
      margin-bottom: 2rem;
    }
    form {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
    }
    form label {
      font-weight: 600;
      margin-bottom: 0.5rem;
      display: block;
      color: #333;
    }
    form input[type="text"],
    form input[type="email"],
    form input[type="file"],
    form input[type="tel"] {
      width: 100%;
      padding: 0.8rem;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      background: #fff;
    }
    img {
      max-width: 150px;
      border: 2px solid #eee;
      border-radius: 6px;
      margin-top: 0.5rem;
      display: block;
    }
    .full-width { grid-column: 1 / 3; }
    .bloque-imagenes {
      grid-column: 1 / 3;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem 2rem;
      background: #fafafa;
      border: 1px dashed #ddd;
      border-radius: 10px;
      padding: 1rem;
    }
    .bloque-imagenes h3 {
      grid-column: 1 / 3;
      color: var(--verde);
      margin-bottom: 0.5rem;
      font-size: 1.1rem;
    }
    .grupo { text-align: left; }
    .grupo p { margin: 0.25rem 0 0.5rem; font-weight: 600; }
    button {
      grid-column: 1 / 3;
      padding: 1rem;
      background-color: var(--verde);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1.1rem;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    button:hover { background-color: var(--verde-hover); }

    @media (max-width: 768px) {
      form { grid-template-columns: 1fr; }
      .full-width, .bloque-imagenes, button { grid-column: 1 / 2; }
      .bloque-imagenes { grid-template-columns: 1fr; }
    }
  </style>
  <link rel="stylesheet" href="../admin/assets/css/header-admin.css">
</head>
<body>
        <!-- HEADER VERDE CON LOGO -->
  <div class="logo-slot">
  <img src="../landing/assets/img/LOGO.png" alt="Logo">
</div>
  <div class="card">
    <h2>Editar Ficha del Cliente</h2>

    <form action="guardar_ficha.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="patente" value="<?= htmlspecialchars($patente) ?>">
      <input type="hidden" name="cliente_id" value="<?= htmlspecialchars($auto['cliente_id']) ?>">

      <div>
        <label>Modelo:</label>
        <input type="text" name="modelo" value="<?= htmlspecialchars($auto['modelo']) ?>">
      </div>

      <div>
        <label>Nombre del Cliente:</label>
        <input type="text" name="cliente" value="<?= htmlspecialchars($auto['cliente']) ?>">
      </div>

      <div>
        <label>Correo:</label>
        <input type="email" name="correo" value="<?= htmlspecialchars($auto['correo']) ?>">
      </div>

      <div>
        <label>Teléfono:</label>
        <input
          type="tel"
          name="telefono"
          value="<?= htmlspecialchars($auto['telefono'] ?? '') ?>"
          pattern="[0-9+()\- ]{6,20}"
          placeholder="Ej: +54 9 264 123 4567"
        >
      </div>

      <!-- Bloque: imágenes actuales + inputs de reemplazo -->
      <div class="bloque-imagenes">
        <h3>DNI</h3>

        <div class="grupo">
          <p>DNI - Frente (actual)</p>
          <?php if (!empty($auto['dni_frente_url'])): ?>
            <img src="<?= htmlspecialchars($auto['dni_frente_url']) ?>" alt="DNI Frente">
          <?php else: ?>
            <small>Sin imagen</small>
          <?php endif; ?>
          <label style="margin-top: .5rem;">Reemplazar (opcional):</label>
          <input type="file" name="dni_frente" accept="image/*">
        </div>

        <div class="grupo">
          <p>DNI - Dorso (actual)</p>
          <?php if (!empty($auto['dni_dorso_url'])): ?>
            <img src="<?= htmlspecialchars($auto['dni_dorso_url']) ?>" alt="DNI Dorso">
          <?php else: ?>
            <small>Sin imagen</small>
          <?php endif; ?>
          <label style="margin-top: .5rem;">Reemplazar (opcional):</label>
          <input type="file" name="dni_dorso" accept="image/*">
        </div>
      </div>

      <div class="bloque-imagenes">
        <h3>Tarjeta Verde</h3>

        <div class="grupo">
          <p>Tarjeta - Frente (actual)</p>
          <?php if (!empty($auto['tarjeta_frente_url'])): ?>
            <img src="<?= htmlspecialchars($auto['tarjeta_frente_url']) ?>" alt="Tarjeta Frente">
          <?php else: ?>
            <small>Sin imagen</small>
          <?php endif; ?>
          <label style="margin-top: .5rem;">Reemplazar (opcional):</label>
          <input type="file" name="tarjeta_frente" accept="image/*">
        </div>

        <div class="grupo">
          <p>Tarjeta - Dorso (actual)</p>
          <?php if (!empty($auto['tarjeta_dorso_url'])): ?>
            <img src="<?= htmlspecialchars($auto['tarjeta_dorso_url']) ?>" alt="Tarjeta Dorso">
          <?php else: ?>
            <small>Sin imagen</small>
          <?php endif; ?>
          <label style="margin-top: .5rem;">Reemplazar (opcional):</label>
          <input type="file" name="tarjeta_dorso" accept="image/*">
        </div>
      </div>

      <button type="submit">Guardar Cambios</button>
    </form>
  </div>
</body>
</html>
