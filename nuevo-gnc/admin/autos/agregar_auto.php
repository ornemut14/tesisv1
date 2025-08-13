<?php
session_start();

require_once '../includes/header-admin.php'; // <- Incluye tu header

// Verificar que sea un administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../login/login.html");
  exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Vehículo | Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      --verde: #0b4d23; /* verde del header */
      --verde-hover: #09361a; /* verde más oscuro para hover */
      --gris-fondo: #f4f4f4;
      --blanco: #ffffff;
      --sombra: rgba(0, 0, 0, 0.05);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: var(--gris-fondo);
      padding: 7rem 1rem 2rem;
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
    .container {
      width: 100%;
      max-width: 1200px;
      margin: auto;
      background-color: var(--blanco);
      padding: 3rem 2rem;
      border-radius: 12px;
      box-shadow: 0 6px 20px var(--sombra);
    }

    h2 { text-align: center; color: var(--verde); margin-bottom: 2rem; }

    form {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }

    form label { font-weight: 600; display: block; margin-bottom: 0.5rem; }

    form input[type="text"],
    form input[type="email"],
    form input[type="file"],
    form input[type="tel"] {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      background: #fff;
    }

    .full-width { grid-column: 1 / 3; }

    .grupo-imagenes {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem 1.5rem;
      grid-column: 1 / 3;
      background: #fafafa;
      border: 1px dashed #ddd;
      border-radius: 10px;
      padding: 1rem;
    }

    .grupo-imagenes h3 {
      grid-column: 1 / 3;
      color: var(--verde);
      margin-bottom: 0.5rem;
      font-size: 1.1rem;
    }

    /* Botón principal */
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

    /* Botón volver */
    .back-link {
      display: inline-block;
      margin-top: 1.5rem;
      padding: 0.6rem 1.2rem;
      background-color: var(--verde);
      color: white;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
      transition: background-color 0.3s;
    }
    .back-link:hover { background-color: var(--verde-hover); }

    @media (max-width: 768px) {
      form { grid-template-columns: 1fr; }
      .full-width { grid-column: 1 / 2; }
      .grupo-imagenes { grid-template-columns: 1fr; grid-column: 1 / 2; }
      button { grid-column: 1 / 2; }
    }
  </style>
  <link rel="stylesheet" href="../assets/css/header-admin.css">
</head>
<body>
    <div class="logo-slot">
  <img src="../../landing/assets/img/LOGO.png" alt="Logo">
</div>
  <div class="container">
    <h2>Registrar Nuevo Vehículo</h2>

    <form action="guardar_auto.php" method="POST" enctype="multipart/form-data">
      <div>
        <label for="patente">Patente:</label>
        <input type="text" name="patente" id="patente" required>
      </div>

      <div>
        <label for="modelo">Modelo:</label>
        <input type="text" name="modelo" id="modelo" required>
      </div>

      <div>
        <label for="cliente">Nombre del Cliente:</label>
        <input type="text" name="cliente" id="cliente" required>
      </div>

      <div>
        <label for="correo">Correo del Cliente:</label>
        <input type="email" name="correo" id="correo" required>
      </div>

      <div>
        <label for="telefono">Teléfono del Cliente:</label>
        <input type="tel" name="telefono" id="telefono" pattern="[0-9+()\- ]{6,20}" placeholder="Ej: +54 9 264 123 4567">
      </div>

      <!-- DNI -->
      <div class="grupo-imagenes">
        <h3>DNI</h3>

        <div>
          <label for="dni_frente">DNI Frente (JPG/PNG):</label>
          <input type="file" name="dni_frente" id="dni_frente" accept="image/jpeg, image/png" required>
        </div>

        <div>
          <label for="dni_dorso">DNI Dorso (JPG/PNG):</label>
          <input type="file" name="dni_dorso" id="dni_dorso" accept="image/jpeg, image/png" required>
        </div>
      </div>

      <!-- Tarjeta Verde -->
      <div class="grupo-imagenes">
        <h3>Tarjeta Verde</h3>

        <div>
          <label for="tarjeta_frente">Tarjeta Verde Frente (JPG/PNG):</label>
          <input type="file" name="tarjeta_frente" id="tarjeta_frente" accept="image/jpeg, image/png" required>
        </div>

        <div>
          <label for="tarjeta_dorso">Tarjeta Verde Dorso (JPG/PNG):</label>
          <input type="file" name="tarjeta_dorso" id="tarjeta_dorso" accept="image/jpeg, image/png" required>
        </div>
      </div>

      <button type="submit">Guardar Vehículo</button>
    </form>

    <div style="text-align:center;">
      <a class="back-link" href="admin-panel.php">← Volver al Panel</a>
    </div>
  </div>
</body>
</html>

