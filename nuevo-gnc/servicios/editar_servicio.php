<?php
require_once '../conexion.php';
require_once '../admin/includes/header-admin.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../login/login.html");
  exit();
}

$id = $_GET['id'] ?? '';
$patente = $_GET['patente'] ?? '';

if ($id === '' || $patente === '') {
  echo "<p>Error: datos inválidos.</p><a href='../admin/autos/admin-panel.php'>Volver</a>";
  exit();
}

// Buscar el servicio en la base de datos
$stmt = $conexion->prepare("SELECT fecha, descripcion FROM servicios WHERE id = ? AND vehiculo_patente = ?");
$stmt->bind_param("is", $id, $patente);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo "<p>Servicio no encontrado.</p><a href='../admin/autos/admin-panel.php'>Volver</a>";
  exit();
}

$servicio = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Servicio</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      --verde: #0b4d23; /* verde del header */
      --verde-hover: #09361a; /* verde más oscuro para hover */
      --gris-fondo: #f9f9f9;
      --sombra: rgba(0, 0, 0, 0.08);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: var(--gris-fondo);
      padding: 6rem 1rem 2rem;
    }
    /* ================== HEADER VERDE ================== */
    .header-bar {
      padding: 0.8rem 2rem;
      display: flex;
      align-items: center;
      position: fixed;
      top: 0; left: 0;
      width: 100%;
      z-index: 1000;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .header-bar img { height: 45px; }
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

    label {
      font-weight: 600;
      margin-bottom: 0.5rem;
      display: block;
      color: #333;
    }

    input[type="date"],
    textarea {
      width: 100%;
      padding: 0.8rem;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
    }

    textarea {
      resize: vertical;
      min-height: 120px;
    }

    .full-width {
      grid-column: 1 / 3;
    }

    /* Botón en verde como el header */
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

    button:hover {
      background-color: var(--verde-hover);
    }

    .link-volver {
      text-align: center;
      margin-top: 2rem;
    }

    .link-volver a {
      color: var(--verde);
      font-weight: bold;
      text-decoration: none;
    }

    .link-volver a:hover {
      text-decoration: underline;
    }

    @media (max-width: 768px) {
      form {
        grid-template-columns: 1fr;
      }
      .full-width,
      button {
        grid-column: 1 / 2;
      }
    }
  </style>
  <link rel="stylesheet" href="../admin/assets/css/header-admin.css">
</head>
<body>
    <!-- HEADER VERDE CON LOGO -->
<div class="header-bar">
  <img src="../landing/assets/img/LOGO.png" alt="Logo Taller GNC">
</div>
  <div class="card">
    <h2>Editar Servicio para <?= htmlspecialchars($patente); ?></h2>
    <form action="guardar_edicion_servicio.php" method="POST">
      <input type="hidden" name="id" value="<?= htmlspecialchars($id); ?>">
      <input type="hidden" name="patente" value="<?= htmlspecialchars($patente); ?>">

      <div class="full-width">
        <label>Fecha del Servicio:</label>
        <input type="date" name="fecha" value="<?= $servicio['fecha']; ?>" required>
      </div>

      <div class="full-width">
        <label>Descripción:</label>
        <textarea name="descripcion" rows="4" required><?= $servicio['descripcion']; ?></textarea>
      </div>

      <button type="submit">Guardar Cambios</button>
    </form>

    <div class="link-volver">
      <a href="../clientes/ficha-cliente.php?patente=<?= htmlspecialchars($patente); ?>">← Volver a la ficha</a>
    </div>
  </div>
</body>
</html>
