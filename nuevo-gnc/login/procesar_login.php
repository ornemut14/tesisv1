
<?php
session_start();
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // ✅ Datos del administrador hardcodeado
    $admin_email = "gncrivadavia1@gmail.com";
    $admin_password = "GNCRivadaviaDiego";

    // ✅ Validación del admin hardcodeado
    if ($email === $admin_email && $password === $admin_password) {
        $_SESSION['usuario_id'] = 0; // ID ficticio para el admin hardcodeado
        $_SESSION['nombre'] = "Administrador";
        $_SESSION['rol'] = "admin";

        header("Location: ../admin/autos/admin-panel.php");
        exit();
    }

    // 🔽 Validar clientes desde la base de datos
    $stmt = $conexion->prepare("SELECT id, nombre, contraseña, rol FROM usuarios WHERE email = ?");
    if (!$stmt) {
        die("Error en la consulta SQL: " . $conexion->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // Verificación de contraseña hasheada
        if (password_verify($password, $usuario['contraseña'])) {
            if ($usuario['rol'] === 'cliente') {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['cliente_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['rol'] = $usuario['rol'];

                header("Location: ../cliente/cliente-panel.php");
                exit();
            } else {
                echo "<p style='color: red;'>⚠️ Solo se permite el ingreso como cliente o administrador hardcodeado.</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Contraseña incorrecta.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No se encontró una cuenta con ese correo.</p>";
    }

    $stmt->close();
    $conexion->close();
}
?>
