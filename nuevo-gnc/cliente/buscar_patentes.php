<?php
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_OFF);

require_once '../conexion.php'; // ajustá si tu conexion.php está en otra ruta

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$q = mb_strtoupper($q, 'UTF-8');

if ($q === '') { echo json_encode([]); exit; }

$pattern = $q . '%'; // "empieza con" (cambiar a "%$q%" si querés "contiene")

$stmt = $conexion->prepare("
  SELECT patente
  FROM vehiculos
  WHERE activo = 1
    AND UPPER(patente) LIKE ?
  ORDER BY patente
  LIMIT 10
");
$stmt->bind_param('s', $pattern);
$stmt->execute();
$res = $stmt->get_result();

$patentes = [];
while ($row = $res->fetch_assoc()) {
  $patentes[] = $row['patente'];
}

$stmt->close();
echo json_encode($patentes);
