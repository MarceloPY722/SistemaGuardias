<?php
session_start();

// Incluir el archivo de conexión a la base de datos
// Asegúrate de que la ruta y el nombre del archivo/función sean correctos.
require_once '../database/cnx.php'; 

$error_message = '';
$success_message = '';
$step = 1; // 1 para verificación de código, 2 para formulario de admin

try {
    $pdo = conectar(); // Llama a tu función de conexión

    // --- Paso 1: Verificación del Código ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_codigo'])) {
        $submitted_codigo = trim($_POST['codigo'] ?? '');

        if (empty($submitted_codigo)) {
            $error_message = 'Por favor, ingrese el código de verificación.';
        } else {
            $stmt = $pdo->prepare("SELECT codigo FROM credenciales WHERE id = 1");
            $stmt->execute();
            $credencial = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($credencial && $submitted_codigo == $credencial['codigo']) {
                $_SESSION['credential_verified'] = true;
                $step = 2;
            } else {
                $error_message = 'Código incorrecto. Inténtalo de nuevo.';
                $_SESSION['credential_verified'] = false;
            }
        }
    }

    // Determinar el paso actual basado en la sesión y las acciones POST
    if (isset($_SESSION['credential_verified']) && $_SESSION['credential_verified'] === true) {
        // Si está verificado, por defecto va al paso 2, a menos que se esté enviando el formulario de admin
        $step = 2;
    } else {
        // Si no está verificado, siempre es el paso 1
        $step = 1;
    }
    
    // Si se está enviando el formulario de código, siempre es el paso 1 (para mostrar errores si los hay)
    if (isset($_POST['submit_codigo'])) {
        if (! (isset($_SESSION['credential_verified']) && $_SESSION['credential_verified'] === true) ) {
             $step = 1;
        }
    }


    // --- Paso 2: Creación del Usuario Administrador ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_admin'])) {
        if (isset($_SESSION['credential_verified']) && $_SESSION['credential_verified'] === true) {
            $usuario = trim($_POST['usuario'] ?? '');
            $password = $_POST['password'] ?? ''; // No trimear passwords por si tienen espacios intencionales

            if (empty($usuario) || empty($password)) {
                $error_message = 'El nombre de usuario y la contraseña son obligatorios.';
                $step = 2; // Permanecer en el formulario de admin
            } else {
                // Hashear la contraseña
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                try {
                    $stmt_check_user = $pdo->prepare("SELECT id_admin FROM admin WHERE usuario = :usuario");
                    $stmt_check_user->bindParam(':usuario', $usuario);
                    $stmt_check_user->execute();

                    if ($stmt_check_user->fetch()) {
                        $error_message = 'El nombre de usuario ya existe. Por favor, elija otro.';
                        $step = 2;
                    } else {
                        $stmt_insert = $pdo->prepare("INSERT INTO admin (usuario, password) VALUES (:usuario, :password)");
                        $stmt_insert->bindParam(':usuario', $usuario);
                        $stmt_insert->bindParam(':password', $hashed_password);
                        
                        if ($stmt_insert->execute()) {
                            unset($_SESSION['credential_verified']); // Limpiar la sesión de verificación
                            // Redirigir al index principal
                            header('Location: ../index.php'); // Redirige a c:\laragon\www\Guardias\index.php
                            exit; // Asegurar que el script se detiene después de la redirección
                        } else {
                            $error_message = 'Error al crear el usuario administrador. Inténtelo de nuevo.';
                            $step = 2;
                        }
                    }
                } catch (PDOException $e) {
                    // errorInfo[1] es el código de error específico del driver, 1062 es para entrada duplicada en MySQL
                    if ($e->errorInfo[1] == 1062) { 
                        $error_message = 'Error: El nombre de usuario ya existe.';
                    } else {
                        $error_message = 'Error de base de datos al crear usuario: ' . $e->getMessage();
                         error_log('DB Error (Admin Creation): ' . $e->getMessage());
                    }
                    $step = 2;
                }
            }
        } else {
            $error_message = 'Acceso no autorizado. Se requiere verificación de credenciales.';
            $step = 1; // Forzar la verificación de credenciales
            unset($_SESSION['credential_verified']);
        }
    }
    
    // Si el usuario está verificado y accede por GET, mostrar el paso 2
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['credential_verified']) && $_SESSION['credential_verified'] === true) {
        $step = 2;
    }


} catch (PDOException $e) {
    $error_message = "Error de conexión a la base de datos: " . $e->getMessage();
    error_log('DB Connection/General Error: ' . $e->getMessage());
    $step = 1; // O mostrar una página de error genérica
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Administrador</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; margin-bottom: 25px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        input[type="text"], input[type="password"], input[type="number"] { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 16px; }
        input[type="submit"] { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; transition: background-color 0.3s ease; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 4px; text-align: center; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!empty($error_message)): ?>
            <p class="message error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <p class="message success"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <h2>Verificación de Acceso</h2>
            <form action="index.php" method="POST" novalidate>
                <div>
                    <label for="codigo">Código de Verificación:</label>
                    <input type="number" id="codigo" name="codigo" required>
                </div>
                <input type="submit" name="submit_codigo" value="Verificar Código">
            </form>
        <?php elseif ($step == 2): ?>
            <h2>Crear Usuario Administrador</h2>
            <form action="index.php" method="POST" novalidate>
                <div>
                    <label for="usuario">Nombre de Usuario:</label>
                    <input type="text" id="usuario" name="usuario" required>
                </div>
                <div>
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <input type="submit" name="submit_admin" value="Crear Administrador">
            </form>
        <?php endif; ?>
    </div>
</body>
</html>