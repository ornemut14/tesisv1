<?php
require_once '../conexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../login/login.html");
        exit();
    }

    $cliente_id = $_SESSION['usuario_id'];
    $patente = $_POST['patente'];
    $servicio = $_POST['servicio'] === 'Otro' ? trim($_POST['otro_servicio']) : $_POST['servicio'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];

    // Validación básica
    if (empty($patente) || empty($servicio) || empty($fecha) || empty($hora)) {
        echo "Faltan datos.";
        exit();
    }

    // Validar rango horario permitido
    $horaTimestamp = strtotime($hora);
    $horaManianaDesde = strtotime("09:00");
    $horaManianaHasta = strtotime("12:00");
    $horaTardeDesde = strtotime("16:30");
    $horaTardeHasta = strtotime("20:00");

    if (
        !(($horaTimestamp >= $horaManianaDesde && $horaTimestamp <= $horaManianaHasta) ||
          ($horaTimestamp >= $horaTardeDesde && $horaTimestamp <= $horaTardeHasta))
    ) {
        echo "<p style='color:red; font-weight:bold;'>⛔ El horario debe ser entre 09:00-12:00 o 16:30-20:00.</p>";
        echo "<a href='cliente-panel.php'>Volver al panel</a>";
        exit();
    }

    $fechaHora = $fecha . ' ' . $hora;
$stmt = $conexion->prepare("INSERT INTO turnos (fecha_turno, vehiculo_patente, servicio) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $fechaHora, $patente, $servicio);


    if ($stmt->execute()) {
        // Enviar email solo si el turno fue guardado con éxito

        require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require '../vendor/phpmailer/phpmailer/src/SMTP.php';
        require '../vendor/phpmailer/phpmailer/src/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // Obtener correo y nombre del cliente desde la patente
        $stmtMail = $conexion->prepare("
            SELECT c.correo, c.nombre 
            FROM vehiculos v 
            JOIN clientes c ON v.cliente_id = c.id 
            WHERE v.patente = ?
        ");
        $stmtMail->bind_param("s", $patente);
        $stmtMail->execute();
        $resMail = $stmtMail->get_result();
        $clienteMail = $resMail->fetch_assoc();

        if ($clienteMail && $clienteMail['correo']) {
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'gncrivadavia1@gmail.com';
                $mail->Password = 'dvcloeuomtxsbhun'; // Contraseña de aplicación
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom('gncrivadavia1@gmail.com', 'Taller GNC Rivadavia');
                $mail->addAddress($clienteMail['correo'], $clienteMail['nombre']);

                $mail->Subject = '📅 Confirmación de turno solicitado';
                $mail->Body = "Hola Señor/a {$clienteMail['nombre']}, su turno ha sido registrado para el día $fecha a las $hora.\n\n¡Te esperamos en el taller!\nGracias por confiar en nosotros.";

                $mail->send();
            } catch (Exception $e) {
                error_log("❌ Error al enviar mail de turno: " . $mail->ErrorInfo);
            }
        }

        // Redirigir con éxito
        header("Location: cliente-panel.php?turno=ok");
        exit();
    } else {
        echo "Error al registrar turno.";
    }

    $stmt->close();
} else {
    echo "Acceso no permitido.";
}
