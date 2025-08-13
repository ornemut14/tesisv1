<?php
session_start();

require_once 'includes/header-admin.php'; // Header admin

// Verificar rol admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login/login.html");
    exit();
}

require_once '../conexion.php';

// Consulta de turnos con datos del cliente
$sql = "
    SELECT 
        t.fecha_turno,
        t.vehiculo_patente,
        t.servicio,
        c.nombre AS cliente_nombre,
        c.correo AS cliente_correo
    FROM turnos t
    JOIN vehiculos v ON t.vehiculo_patente = v.patente
    JOIN clientes c ON v.cliente_id = c.id
    WHERE t.fecha_turno >= CURDATE()
    ORDER BY t.fecha_turno ASC
";


$result = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Turnos Registrados</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="assets/css/header-admin.css">


  <style>
    :root {
      --verde: #016b3b;
      --verde-hover: #014d2a;
      --gris-fondo: #f4f4f4;
      --blanco: #ffffff;
      --sombra: rgba(0, 0, 0, 0.05);
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: var(--gris-fondo);
      padding: 7rem 1rem 2rem; /* para que el header no tape contenido */
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
      max-width: 1300px;
      margin: auto;
      background-color: var(--blanco);
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 6px 20px var(--sombra);
    }

    h2 {
      text-align: center;
      color: var(--verde);
      margin-bottom: 2rem;
      font-size: 1.8rem;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 1rem;
    }

    thead {
      background-color: var(--verde);
      color: white;
    }

    th, td {
      padding: 0.8rem;
      border: 1px solid #ddd;
      text-align: center;
    }

    tbody tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    tbody tr:hover {
      background-color: rgba(1, 107, 59, 0.1);
    }

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

    .back-link:hover {
      background-color: var(--verde-hover);
    }

    @media (max-width: 768px) {
      table {
        font-size: 0.85rem;
      }
      th, td {
        padding: 0.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="logo-slot">
  <img src="../landing/assets/img/LOGO.png" alt="Logo">
</div>
  <div class="container">
    <h2>📅 Turnos Registrados</h2>

    <?php if ($result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Patente</th>
            <th>Fecha y Hora</th>
            <th>Servicio</th>
            <th>Cliente</th>
            <th>Correo</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($fila = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($fila['vehiculo_patente']) ?></td>
              <td><?= date("d/m/Y H:i", strtotime($fila['fecha_turno'])) ?></td>
              <td><?= htmlspecialchars($fila['servicio']) ?></td>
              <td><?= htmlspecialchars($fila['cliente_nombre']) ?></td>
              <td><?= htmlspecialchars($fila['cliente_correo']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="text-align:center; color:#777;">No hay turnos registrados.</p>
    <?php endif; ?>

    <div style="text-align:center;">
     <a class="back-link" href="autos/admin-panel.php">← Volver al panel principal</a>
    </div>
  </div>

</body>
</html>
