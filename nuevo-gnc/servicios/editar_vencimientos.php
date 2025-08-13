<?php
require_once '../conexion.php';
require_once '../admin/includes/header-admin.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../login/login.html");
  exit();
}

$patente = $_GET['patente'] ?? '';

if ($patente === '') {
  echo "<p>Error: Patente no proporcionada.</p><a href='../admin/autos/admin-panel.php'>Volver</a>";
  exit();
}

// Obtener los vencimientos actuales
$stmt = $conexion->prepare("SELECT oblea, prueba_hidraulica, reprueba FROM vencimientos WHERE vehiculo_patente = ?");
$stmt->bind_param("s", $patente);
$stmt->execute();
$result = $stmt->get_result();
$vencimientos = $result->fetch_assoc();
$stmt->close();

if (!$vencimientos) {
  $vencimientos = ["oblea" => '', "prueba_hidraulica" => '', "reprueba" => ''];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Vencimientos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../admin/assets/css/header-admin.css">
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

    label {
      font-weight: 600;
      margin-bottom: 0.5rem;
      display: block;
      color: #333;
    }

    input[type="date"] {
      width: 100%;
      padding: 0.8rem;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
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

    /* Enlace volver */
    a {
      display: block;
      margin-top: 1.5rem;
      text-align: center;
      color: var(--verde);
      font-weight: bold;
      text-decoration: none;
    }

    a:hover {
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
</head>
<body>
  <div class="logo-slot">
  <img src="../landing/assets/img/LOGO.png" alt="Logo">
</div>
  <div class="card">
    <h2>Editar Vencimientos para <?= htmlspecialchars($patente) ?></h2>
    <form action="guardar_vencimientos.php" method="POST">
      <input type="hidden" name="patente" value="<?= htmlspecialchars($patente) ?>">

      <div class="full-width">
        <label for="oblea">Fecha de Oblea:</label>
        <input type="date" name="oblea" value="<?= $vencimientos['oblea'] ?>">
      </div>

      <div class="full-width">
        <label for="prueba_hidraulica">Fecha Prueba Hidráulica:</label>
        <input type="date" name="prueba_hidraulica" value="<?= $vencimientos['prueba_hidraulica'] ?>">
      </div>

      <button type="submit">Guardar Cambios</button>
    </form>

    <a href="../clientes/ficha-cliente.php?patente=<?= htmlspecialchars($patente) ?>">← Volver a la ficha</a>
  </div>
</body>
</html>
