<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../database/cnx.php';

$usuario_actual = $_SESSION['admin_usuario'] ?? 'Administrador';

// Obtener estadísticas básicas
try {
    $pdo = conectar();
    
    // Contar personal activo
    $stmt_personal = $pdo->query("SELECT COUNT(*) FROM personal WHERE activo = TRUE");
    $total_personal = $stmt_personal->fetchColumn();
    
    // Contar guardias pendientes
    $stmt_guardias = $pdo->query("SELECT COUNT(*) FROM guardia WHERE estado = 'Pendiente'");
    $guardias_pendientes = $stmt_guardias->fetchColumn();
    
    // Contar departamentos
    $stmt_departamentos = $pdo->query("SELECT COUNT(*) FROM departamento");
    $total_departamentos = $stmt_departamentos->fetchColumn();
    
    // Contar ausencias activas
    $fecha_actual = date('Y-m-d');
    $stmt_ausencias = $pdo->prepare("SELECT COUNT(*) FROM ausencia WHERE fecha_fin >= :fecha_actual");
    $stmt_ausencias->bindParam(':fecha_actual', $fecha_actual);
    $stmt_ausencias->execute();
    $ausencias_activas = $stmt_ausencias->fetchColumn();
    
} catch (PDOException $e) {
    error_log('Error al obtener estadísticas: ' . $e->getMessage());
    $total_personal = 0;
    $guardias_pendientes = 0;
    $total_departamentos = 0;
    $ausencias_activas = 0;
}

// Función para obtener el módulo actual
function getModulo() {
    $modulo = $_GET['modulo'] ?? 'dashboard';
    $modulos_permitidos = ['dashboard', 'personal', 'guardias', 'departamentos', 'ausencias', 'reportes', 'configuracion'];
    
    return in_array($modulo, $modulos_permitidos) ? $modulo : 'dashboard';
}

$modulo_actual = getModulo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Guardias - Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --danger-color: #e74c3c;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            min-height: 100vh;
            display: flex;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background-color: var(--primary-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .sidebar-menu {
            padding: 0;
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 15px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: var(--secondary-color);
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .top-bar {
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-body {
            padding: 20px;
        }
        
        .stat-card {
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
        }
        
        .stat-icon.bg-primary {
            background-color: rgba(52, 152, 219, 0.2);
            color: var(--secondary-color);
        }
        
        .stat-icon.bg-success {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success-color);
        }
        
        .stat-icon.bg-warning {
            background-color: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }
        
        .stat-icon.bg-danger {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }
        
        .stat-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .stat-info p {
            margin: 0;
            color: #7f8c8d;
        }
        
        /* Content Sections */
        .content-section {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 20px 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
                text-align: center;
            }
            
            .sidebar-header h3 {
                display: none;
            }
            
            .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a i {
                margin-right: 0;
                font-size: 20px;
            }
            
            .main-content {
                margin-left: 80px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Sistema de Guardias</h3>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="?modulo=dashboard" class="<?php echo $modulo_actual === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="?modulo=personal" class="<?php echo $modulo_actual === 'personal' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Personal</span>
                </a>
            </li>
            <li>
                <a href="?modulo=guardias" class="<?php echo $modulo_actual === 'guardias' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i>
                    <span>Guardias</span>
                </a>
            </li>
            <li>
                <a href="?modulo=departamentos" class="<?php echo $modulo_actual === 'departamentos' ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i>
                    <span>Departamentos</span>
                </a>
            </li>
            <li>
                <a href="?modulo=ausencias" class="<?php echo $modulo_actual === 'ausencias' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-minus"></i>
                    <span>Ausencias</span>
                </a>
            </li>
            <li>
                <a href="?modulo=reportes" class="<?php echo $modulo_actual === 'reportes' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                </a>
            </li>
            <li>
                <a href="?modulo=configuracion" class="<?php echo $modulo_actual === 'configuracion' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </a>
            </li>
            <li>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h4>Panel de Administración</h4>
            <div class="user-info">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($usuario_actual); ?>&background=random" alt="User Avatar">
                <span><?php echo htmlspecialchars($usuario_actual); ?></span>
            </div>
        </div>
        
        <?php
        // Cargar el contenido del módulo correspondiente
        $archivo_modulo = "{$modulo_actual}/index.php";
        if (file_exists($archivo_modulo)) {
            include $archivo_modulo;
        } else {
            echo '<div class="alert alert-danger">El módulo solicitado no existe.</div>';
        }
        ?>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Sistema de Guardias. Todos los derechos reservados.</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>