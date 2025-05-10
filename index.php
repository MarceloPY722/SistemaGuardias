<?php
session_start();

// Verificar si ya hay una sesión activa
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/index.php');
    exit;
}

// Incluir archivo de conexión
require_once 'database/cnx.php';

$error = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        try {
            $pdo = conectar();
            
            // Consultar el usuario en la base de datos
            $stmt = $pdo->prepare("SELECT id_admin, usuario, password FROM admin WHERE usuario = :usuario");
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Credenciales correctas, iniciar sesión
                $_SESSION['admin_id'] = $user['id_admin'];
                $_SESSION['admin_usuario'] = $user['usuario'];
                
                // Redirigir al panel de administración
                header('Location: admin/index.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $error = 'Error de conexión. Por favor, intente más tarde.';
            error_log('Error de login: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Guardias</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        
        .login-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            width: 400px;
            padding: 40px;
            transition: transform 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            color: var(--dark-color);
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .login-form .form-group i {
            position: absolute;
            left: 15px;
            top: 15px;
            color: #95a5a6;
        }
        
        .login-form input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .login-form input:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.5);
            outline: none;
        }
        
        .login-form button {
            width: 100%;
            padding: 15px;
            background-color: var(--secondary-color);
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .login-form button:hover {
            background-color: #2980b9;
        }
        
        .error-message {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--accent-color);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        /* Animación de fondo */
        @keyframes gradientAnimation {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color), #9b59b6, #2980b9);
            background-size: 400% 400%;
            animation: gradientAnimation 15s ease infinite;
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Sistema de Guardias</h2>
            <p>Ingrese sus credenciales para acceder</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" name="usuario" placeholder="Nombre de usuario" value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Contraseña" required>
            </div>
            
            <button type="submit">Iniciar Sesión</button>
        </form>
        
        <div class="footer">
            &copy; <?php echo date('Y'); ?> Sistema de Gestión de Guardias
        </div>
    </div>
</body>
</html>