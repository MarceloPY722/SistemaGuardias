<?php
// Verificar que este archivo no sea accedido directamente
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(__FILE__)));
}

// Asegurarse de que el usuario esté autenticado
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}
?>

<!-- Dashboard Content -->
<div class="dashboard-cards">
    <div class="card">
        <div class="card-body">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_personal; ?></h3>
                    <p>Personal Activo</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $guardias_pendientes; ?></h3>
                    <p>Guardias Pendientes</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_departamentos; ?></h3>
                    <p>Departamentos</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-card">
                <div class="stat-icon bg-danger">
                    <i class="fas fa-calendar-minus"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $ausencias_activas; ?></h3>
                    <p>Ausencias Activas</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-section">
    <div class="section-header">
        <h2>Guardias Recientes</h2>
        <a href="?modulo=guardias" class="btn btn-primary">Ver Todas</a>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Personal</th>
                    <th>Puesto</th>
                    <th>Fecha</th>
                    <th>Turno</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $stmt_guardias_recientes = $pdo->query("
                        SELECT g.id_guardia, CONCAT(p.nombre, ' ', p.apellido) as nombre_personal, 
                               pg.nombre as puesto, g.fecha_guardia, t.nombre as turno, g.estado
                        FROM guardia g
                        JOIN personal p ON g.id_personal = p.id_personal
                        JOIN puesto_guardia pg ON g.id_puesto = pg.id_puesto
                        JOIN turno t ON g.id_turno = t.id_turno
                        ORDER BY g.fecha_guardia DESC
                        LIMIT 5
                    ");
                    
                    while ($guardia = $stmt_guardias_recientes->fetch(PDO::FETCH_ASSOC)) {
                        $estado_class = '';
                        switch ($guardia['estado']) {
                            case 'Pendiente':
                                $estado_class = 'warning';
                                break;
                            case 'En progreso':
                                $estado_class = 'info';
                                break;
                            case 'Completada':
                                $estado_class = 'success';
                                break;
                            case 'Incumplida':
                                $estado_class = 'danger';
                                break;
                            case 'Reasignada':
                                $estado_class = 'secondary';
                                break;
                        }
                        
                        echo '<tr>';
                        echo '<td>' . $guardia['id_guardia'] . '</td>';
                        echo '<td>' . htmlspecialchars($guardia['nombre_personal']) . '</td>';
                        echo '<td>' . htmlspecialchars($guardia['puesto']) . '</td>';
                        echo '<td>' . date('d/m/Y', strtotime($guardia['fecha_guardia'])) . '</td>';
                        echo '<td>' . htmlspecialchars($guardia['turno']) . '</td>';
                        echo '<td><span class="badge bg-' . $estado_class . '">' . $guardia['estado'] . '</span></td>';
                        echo '<td>
                                <a href="?modulo=guardias&accion=ver&id=' . $guardia['id_guardia'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                <a href="?modulo=guardias&accion=editar&id=' . $guardia['id_guardia'] . '" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                              </td>';
                        echo '</tr>';
                    }
                    
                    if ($stmt_guardias_recientes->rowCount() === 0) {
                        echo '<tr><td colspan="7" class="text-center">No hay guardias registradas</td></tr>';
                    }
                    
                } catch (PDOException $e) {
                    echo '<tr><td colspan="7" class="text-center text-danger">Error al cargar las guardias recientes</td></tr>';
                    error_log('Error al cargar guardias recientes: ' . $e->getMessage());
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="content-section">
            <div class="section-header">
                <h2>Personal Reciente</h2>
                <a href="?modulo=personal" class="btn btn-primary">Ver Todos</a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Rango</th>
                            <th>Departamento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt_personal_reciente = $pdo->query("
                                SELECT p.id_personal, CONCAT(p.nombre, ' ', p.apellido) as nombre_completo, 
                                       r.nombre as rango, d.nombre as departamento
                                FROM personal p
                                JOIN rango r ON p.id_rango = r.id_rango
                                LEFT JOIN departamento d ON p.id_departamento = d.id_departamento
                                WHERE p.activo = TRUE
                                ORDER BY p.fecha_creacion DESC
                                LIMIT 5
                            ");
                            
                            while ($personal = $stmt_personal_reciente->fetch(PDO::FETCH_ASSOC)) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($personal['nombre_completo']) . '</td>';
                                echo '<td>' . htmlspecialchars($personal['rango']) . '</td>';
                                echo '<td>' . htmlspecialchars($personal['departamento'] ?? 'Sin asignar') . '</td>';
                                echo '<td>
                                        <a href="?modulo=personal&accion=ver&id=' . $personal['id_personal'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                        <a href="?modulo=personal&accion=editar&id=' . $personal['id_personal'] . '" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                      </td>';
                                echo '</tr>';
                            }
                            
                            if ($stmt_personal_reciente->rowCount() === 0) {
                                echo '<tr><td colspan="4" class="text-center">No hay personal registrado</td></tr>';
                            }
                            
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="4" class="text-center text-danger">Error al cargar el personal reciente</td></tr>';
                            error_log('Error al cargar personal reciente: ' . $e->getMessage());
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="content-section">
            <div class="section-header">
                <h2>Ausencias Recientes</h2>
                <a href="?modulo=ausencias" class="btn btn-primary">Ver Todas</a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Personal</th>
                            <th>Tipo</th>
                            <th>Período</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt_ausencias_recientes = $pdo->query("
                                SELECT a.id_ausencia, CONCAT(p.nombre, ' ', p.apellido) as nombre_personal, 
                                       ta.nombre as tipo_ausencia, a.fecha_inicio, a.fecha_fin, a.aprobado
                                FROM ausencia a
                                JOIN personal p ON a.id_personal = p.id_personal
                                JOIN tipo_ausencia ta ON a.id_tipo_ausencia = ta.id_tipo_ausencia
                                ORDER BY a.fecha_creacion DESC
                                LIMIT 5
                            ");
                            
                            while ($ausencia = $stmt_ausencias_recientes->fetch(PDO::FETCH_ASSOC)) {
                                $estado = $ausencia['aprobado'] ? '<span class="badge bg-success">Aprobada</span>' : '<span class="badge bg-warning">Pendiente</span>';
                                $periodo = date('d/m/Y', strtotime($ausencia['fecha_inicio'])) . ' - ' . date('d/m/Y', strtotime($ausencia['fecha_fin']));
                                
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($ausencia['nombre_personal']) . '</td>';
                                echo '<td>' . htmlspecialchars($ausencia['tipo_ausencia']) . '</td>';
                                echo '<td>' . $periodo . '</td>';
                                echo '<td>' . $estado . '</td>';
                                echo '</tr>';
                            }
                            
                            if ($stmt_ausencias_recientes->rowCount() === 0) {
                                echo '<tr><td colspan="4" class="text-center">No hay ausencias registradas</td></tr>';
                            }
                            
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="4" class="text-center text-danger">Error al cargar las ausencias recientes</td></tr>';
                            error_log('Error al cargar ausencias recientes: ' . $e->getMessage());
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>