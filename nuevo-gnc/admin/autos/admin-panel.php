<?php
session_start();
require_once '../../conexion.php';
require_once '../includes/header-admin.php'; // <- Incluye tu header

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login/login.html");
    exit();
}

// Obtener autos
$autos = [];
$sql = "SELECT patente, modelo FROM vehiculos WHERE activo = 1";
$resultado = $conexion->query($sql);
if ($resultado && $resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        $autos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Panel Administrador</title>

  <!-- ✅ Estilo del header -->
  <link rel="stylesheet" href="../assets/css/header-admin.css">

  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: Arial, sans-serif;
      background: url('../../landing/assets/img/fondo_auto (2).jpg') no-repeat center center fixed;
      background-size: cover;
      padding-top: 80px;
      color: white;
    }
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
    .contenido-principal {
      width: 100%;
      padding: 2rem 1rem;
    }

    h1 {
      color: yellow;
      text-align: center;
      margin-bottom: 1rem;
    }

    .bloque-buscador {
      max-width: 600px;
      margin: 0 auto 2rem;
      padding: 1.5rem;
      background-color: rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      text-align: center;
    }

    .bloque-buscador input[type="text"] {
      width: 100%;
      padding: 0.6rem;
      border: 2px solid white;
      border-radius: 6px;
      background: transparent;
      color: white;
      font-size: 1rem;
      margin-bottom: 0.8rem;
    }

    .bloque-buscador input::placeholder {
      color: rgba(255, 255, 255, 0.6);
    }

    .bloque-buscador button {
      padding: 0.6rem 1.2rem;
      background-color: #0b4d23;
      border: none;
      color: white;
      border-radius: 6px;
      font-weight: bold;
      margin-bottom: 0.5rem;
      cursor: pointer;
    }

    .bloque-buscador button:hover {
      background-color: #09441e;
    }

    .bloque-buscador .btn {
      display: inline-block;
      background: #f1c40f;
      color: black;
      padding: 0.6rem 1.2rem;
      text-decoration: none;
      border-radius: 6px;
      font-weight: bold;
    }

    .tabla-pantalla {
      width: 100%;
      overflow-x: auto;
      padding: 0 1rem;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: rgba(255, 255, 255, 0.15);
      margin-top: 1rem;
      min-width: 600px;
    }

    th, td {
      padding: 0.75rem;
      border: 1px solid rgba(255, 255, 255, 0.4);
      text-align: left;
      color: white;
    }

    td a.btn {
      background: #f1c40f;
      color: black;
      padding: 0.4rem 0.8rem;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
    }

    td a.btn:hover {
      background: #d4ac0d;
    }
  </style>
  <link rel="stylesheet" href="../assets/header-admin.css">
</head>
<body>
  <div class="logo-slot">
  <img src="../../landing/assets/img/LOGO.png" alt="Logo">
</div>
<!-- CONTENIDO -->
<div class="contenido-principal">
  <h1>Panel del Administrador</h1>

  <div class="bloque-buscador">
    <form method="get">
      <input type="text" name="buscar" placeholder="Buscar patente..." value="<?php echo $_GET['buscar'] ?? '' ?>">
      <button type="submit">Buscar</button>
      <a href="agregar_auto.php" class="btn">+ Agregar Vehículo</a>
    </form>
  </div>

  <div class="tabla-pantalla">
    <table>
      <thead>
        <tr>
          <th>Patente</th>
          <th>Modelo</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $busqueda = $_GET['buscar'] ?? '';
        foreach ($autos as $auto) {
          if ($busqueda === '' || stripos($auto['patente'], $busqueda) !== false) {
              echo "<tr>
                      <td>{$auto['patente']}</td>
                      <td>{$auto['modelo']}</td>
                      <td><a class='btn' href='../../clientes/ficha-cliente.php?patente={$auto['patente']}'>Ver Ficha</a></td>
                    </tr>";
          }
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
