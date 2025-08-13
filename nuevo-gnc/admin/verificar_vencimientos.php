<?php
session_start();
require_once 'includes/header-admin.php'; // Header admin

// Verificar rol admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login/login.html");
    exit();
}

require_once '../conexion.php';
require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fecha de mañana
$fecha_mananana = date('Y-m-d', strtotime('+1 day'));

$sql = "
SELECT v.vehiculo_patente, v.oblea, v.prueba_hidraulica, c.nombre, c.correo
FROM vencimientos v
JOIN vehiculos vh ON v.vehiculo_patente = vh.patente
JOIN clientes c ON vh.cliente_id = c.id
WHERE v.oblea = ? OR v.prueba_hidraulica = ?
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ss", $fecha_mananana, $fecha_mananana);
$stmt->execute();
$result = $stmt->get_result();

$registros = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $servicio = ($row['oblea'] == $fecha_mananana) ? "Oblea" : "Prueba Hidráulica";
        $estado = "❌ No enviado";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'gncrivadavia1@gmail.com';
            $mail->Password = 'dvcloeuomtxsbhun';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom('gncrivadavia1@gmail.com', 'Taller GNC Rivadavia');
            $mail->addAddress($row['correo'], $row['nombre']);

            $mail->isHTML(true);
            $mail->Subject = "⏳ Aviso: Su $servicio vence mañana";
            $mail->Body = "
                <p>Hola <strong>{$row['nombre']}</strong>,</p>
                <p>Le recordamos que mañana vence el siguiente servicio de su vehículo:</p>
                <ul>
                    <li><strong>Vehículo:</strong> {$row['vehiculo_patente']}</li>
                    <li><strong>Servicio:</strong> $servicio</li>
                    <li><strong>Fecha de vencimiento:</strong> $fecha_mananana</li>
                </ul>
                <p>Para evitar inconvenientes, le recomendamos agendar un turno para la renovación.</p>
                <p>📞 Teléfono: <strong>264-123-4567</strong><br>
                   📍 Dirección: <strong>Av. Libertador 1234, Rivadavia, San Juan</strong></p>
                <p>Muchas gracias por confiar en <strong>Taller GNC Rivadavia</strong>.</p>
            ";

            if ($mail->send()) {
                $estado = "✅ Enviado";
            }
        } catch (Exception $e) {
            $estado = "❌ Error: {$mail->ErrorInfo}";
        }

        $registros[] = [
            "patente" => $row['vehiculo_patente'],
            "servicio" => $servicio,
            "fecha" => $fecha_mananana,
            "estado" => $estado
        ];
    }
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Notificaciones de vencimientos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- CSS del header -->
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

    .estado-ok {
      color: green;
      font-weight: bold;
    }

    .estado-error {
      color: red;
      font-weight: bold;
    }

    .no-vencimientos {
      text-align: center;
      color: #777;
      font-size: 1.1rem;
      padding: 1.5rem;
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
  </style>
</head>
<body>
    <!-- HEADER VERDE CON LOGO -->
<div class="header-bar">
  <img src="../landing/assets/img/LOGO.png" alt="Logo Taller GNC">
</div>
  <div class="container">
    <h2>📅 Notificaciones de vencimientos</h2>

    <?php if (count($registros) > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Patente</th>
            <th>Servicio</th>
            <th>Fecha Vencimiento</th>
            <th>Estado Notificación</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($registros as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['patente']) ?></td>
              <td><?= htmlspecialchars($r['servicio']) ?></td>
              <td><?= htmlspecialchars($r['fecha']) ?></td>
              <td class="<?= strpos($r['estado'], '✅') !== false ? 'estado-ok' : 'estado-error' ?>">
                <?= htmlspecialchars($r['estado']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="no-vencimientos">
        ✅ No hay vencimientos para mañana.
      </div>
    <?php endif; ?>

    <div style="text-align:center;">
      <a class="back-link" href="autos/admin-panel.php">← Volver al panel principal</a>
    </div>
  </div>

</body>
</html>
