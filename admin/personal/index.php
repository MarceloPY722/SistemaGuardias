<?php
// Verificar que este archivo no sea accedido directamente
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(__FILE__)));
}

// Asegurarse de que el usuario esté autenticado
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Incluir archivo de funciones
require_once BASE_PATH . '/includes/funciones.php';

// Obtener la acción actual
$accion = $_GET['accion'] ?? 'listar';

// Procesar formulario de nuevo personal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_personal'])) {
    try {
        $cedula = trim($_POST['cedula'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $id_rango = intval($_POST['id_rango'] ?? 0);
        $id_departamento = !empty($_POST['id_departamento']) ? intval($_POST['id_departamento']) : null;
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
        $telefono = trim($_POST['telefono'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validaciones básicas
        $errores = [];
        
        if (empty($cedula)) {
            $errores[] = "La cédula es obligatoria";
        }
        
        if (empty($nombre)) {
            $errores[] = "El nombre es obligatorio";
        }
        
        if (empty($apellido)) {
            $errores[] = "El apellido es obligatorio";
        }
        
        if ($id_rango <= 0) {
            $errores[] = "Debe seleccionar un rango válido";
        }
        
        // Si no hay errores, guardar en la base de datos
        if (empty($errores)) {
            // Verificar si la cédula ya existe
            $stmt_check = $pdo->prepare("SELECT id_personal FROM personal WHERE cedula = :cedula");
            $stmt_check->bindParam(':cedula', $cedula);
            $stmt_check->execute();
            
            if ($stmt_check->fetch()) {
                $errores[] = "Ya existe un personal con esta cédula";
            } else {
                // Insertar nuevo personal
                $stmt = $pdo->prepare("
                    INSERT INTO personal (
                        cedula, nombre, apellido, id_rango, id_departamento, 
                        fecha_nacimiento, telefono, correo, direccion, activo
                    ) VALUES (
                        :cedula, :nombre, :apellido, :id_rango, :id_departamento, 
                        :fecha_nacimiento, :telefono, :correo, :direccion, :activo
                    )
                ");
                
                $stmt->bindParam(':cedula', $cedula);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':apellido', $apellido);
                $stmt->bindParam(':id_rango', $id_rango);
                $stmt->bindParam(':id_departamento', $id_departamento);
                $stmt->bindParam(':fecha_nacimiento', $fecha_nacimiento);
                $stmt->bindParam(':telefono', $telefono);
                $stmt->bindParam(':correo', $correo);
                $stmt->bindParam(':direccion', $direccion);
                $stmt->bindParam(':activo', $activo);
                
                if ($stmt->execute()) {
                    $mensaje_exito = "Personal agregado correctamente";
                    // Redirigir a la lista después de agregar
                    header("Location: ?modulo=personal&mensaje=" . urlencode($mensaje_exito));
                    exit;
                } else {
                    $errores[] = "Error al guardar los datos";
                }
            }
        }
    } catch (PDOException $e) {
        $errores[] = "Error de base de datos: " . $e->getMessage();
        error_log("Error al agregar personal: " . $e->getMessage());
    }
}

// Procesar formulario de edición de personal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_personal'])) {
    try {
        $id_personal = intval($_POST['id_personal'] ?? 0);
        $cedula = trim($_POST['cedula'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $id_rango = intval($_POST['id_rango'] ?? 0);
        $id_departamento = !empty($_POST['id_departamento']) ? intval($_POST['id_departamento']) : null;
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
        $telefono = trim($_POST['telefono'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validaciones básicas
        $errores = [];
        
        if ($id_personal <= 0) {
            $errores[] = "ID de personal inválido";
        }
        
        if (empty($cedula)) {
            $errores[] = "La cédula es obligatoria";
        }
        
        if (empty($nombre)) {
            $errores[] = "El nombre es obligatorio";
        }
        
        if (empty($apellido)) {
            $errores[] = "El apellido es obligatorio";
        }
        
        if ($id_rango <= 0) {
            $errores[] = "Debe seleccionar un rango válido";
        }
        
        // Si no hay errores, actualizar en la base de datos
        if (empty($errores)) {
            // Verificar si la cédula ya existe para otro personal
            $stmt_check = $pdo->prepare("SELECT id_personal FROM personal WHERE cedula = :cedula AND id_personal != :id_personal");
            $stmt_check->bindParam(':cedula', $cedula);
            $stmt_check->bindParam(':id_personal', $id_personal);
            $stmt_check->execute();
            
            if ($stmt_check->fetch()) {
                $errores[] = "Ya existe otro personal con esta cédula";
            } else {
                // Actualizar personal
                $stmt = $pdo->prepare("
                    UPDATE personal SET
                        cedula = :cedula,
                        nombre = :nombre,
                        apellido = :apellido,
                        id_rango = :id_rango,
                        id_departamento = :id_departamento,
                        fecha_nacimiento = :fecha_nacimiento,
                        telefono = :telefono,
                        correo = :correo,
                        direccion = :direccion,
                        activo = :activo,
                        fecha_modificacion = CURRENT_TIMESTAMP
                    WHERE id_personal = :id_personal
                ");
                
                $stmt->bindParam(':id_personal', $id_personal);
                $stmt->bindParam(':cedula', $cedula);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':apellido', $apellido);
                $stmt->bindParam(':id_rango', $id_rango);
                $stmt->bindParam(':id_departamento', $id_departamento);
                $stmt->bindParam(':fecha_nacimiento', $fecha_nacimiento);
                $stmt->bindParam(':telefono', $telefono);
                $stmt->bindParam(':correo', $correo);
                $stmt->bindParam(':direccion', $direccion);
                $stmt->bindParam(':activo', $activo);
                
                if ($stmt->execute()) {
                    $mensaje_exito = "Personal actualizado correctamente";
                    // Redirigir a la lista después de editar
                    header("Location: ?modulo=personal&mensaje=" . urlencode($mensaje_exito));
                    exit;
                } else {
                    $errores[] = "Error al actualizar los datos";
                }
            }
        }
    } catch (PDOException $e) {
        $errores[] = "Error de base de datos: " . $e->getMessage();
        error_log("Error al editar personal: " . $e->getMessage());
    }
}

// Procesar eliminación de personal
if ($accion === 'eliminar' && isset($_GET['id'])) {
    try {
        $id_personal = intval($_GET['id']);
        
        // Verificar si el personal existe
        $stmt_check = $pdo->prepare("SELECT id_personal FROM personal WHERE id_personal = :id_personal");
        $stmt_check->bindParam(':id_personal', $id_personal);
        $stmt_check->execute();
        
        if (!$stmt_check->fetch()) {
            $mensaje_error = "El personal no existe";
            header("Location: ?modulo=personal&error=" . urlencode($mensaje_error));
            exit;
        }
        
        // Verificar si el personal tiene guardias asignadas
        $stmt_guardias = $pdo->prepare("SELECT COUNT(*) FROM guardia WHERE id_personal = :id_personal");
        $stmt_guardias->bindParam(':id_personal', $id_personal);
        $stmt_guardias->execute();
        $tiene_guardias = ($stmt_guardias->fetchColumn() > 0);
        
        // Verificar si el personal tiene ausencias registradas
        $stmt_ausencias = $pdo->prepare("SELECT COUNT(*) FROM ausencia WHERE id_personal = :id_personal");
        $stmt_ausencias->bindParam(':id_personal', $id_personal);
        $stmt_ausencias->execute();
        $tiene_ausencias = ($stmt_ausencias->fetchColumn() > 0);
        
        if ($tiene_guardias || $tiene_ausencias) {
            $mensaje_error = "No se puede eliminar el personal porque tiene guardias o ausencias asociadas";
            header("Location: ?modulo=personal&error=" . urlencode($mensaje_error));
            exit;
        }
        
        // Eliminar el personal
        $stmt_delete = $pdo->prepare("DELETE FROM personal WHERE id_personal = :id_personal");
        $stmt_delete->bindParam(':id_personal', $id_personal);
        
        if ($stmt_delete->execute()) {
            $mensaje_exito = "Personal eliminado correctamente";
            header("Location: ?modulo=personal&mensaje=" . urlencode($mensaje_exito));
            exit;
        } else {
            $mensaje_error = "Error al eliminar el personal";
            header("Location: ?modulo=personal&error=" . urlencode($mensaje_error));
            exit;
        }
    } catch (PDOException $e) {
        $mensaje_error = "Error de base de datos: " . $e->getMessage();
        error_log("Error al eliminar personal: " . $e->getMessage());
        header("Location: ?modulo=personal&error=" . urlencode($mensaje_error));
        exit;
    }
}

// Mostrar mensajes de éxito o error
if (isset($_GET['mensaje'])) {
    $mensaje_exito = $_GET['mensaje'];
}

if (isset($_GET['error'])) {
    $mensaje_error = $_GET['error'];
}
?>

<!-- Contenido del módulo de Personal -->
<?php if ($accion === 'listar'): ?>
    <!-- Listado de Personal -->
    <div class="content-section">
        <div class="section-header">
            <h2>Gestión de Personal</h2>
            <a href="?modulo=personal&accion=nuevo" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Personal</a>
        </div>
        
        <?php if (isset($mensaje_exito)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensaje_exito); ?></div>
        <?php endif; ?>
        
        <?php if (isset($mensaje_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($mensaje_error); ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Rango</th>
                        <th>Departamento</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $stmt_personal = $pdo->query("
                            SELECT p.id_personal, p.cedula, CONCAT(p.nombre, ' ', p.apellido) as nombre_completo, 
                                   r.nombre as rango, d.nombre as departamento, p.activo
                            FROM personal p
                            JOIN rango r ON p.id_rango = r.id_rango
                            LEFT JOIN departamento d ON p.id_departamento = d.id_departamento
                            ORDER BY p.apellido, p.nombre
                        ");
                        
                        while ($personal = $stmt_personal->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . $personal['id_personal'] . '</td>';
                            echo '<td>' . htmlspecialchars($personal['cedula']) . '</td>';
                            echo '<td>' . htmlspecialchars($personal['nombre_completo']) . '</td>';
                            echo '<td>' . htmlspecialchars($personal['rango']) . '</td>';
                            echo '<td>' . htmlspecialchars($personal['departamento'] ?? 'Sin asignar') . '</td>';
                            echo '<td>';
                            if ($personal['activo']) {
                                echo '<span class="badge bg-success">Activo</span>';
                            } else {
                                echo '<span class="badge bg-danger">Inactivo</span>';
                            }
                            echo '</td>';
                            echo '<td>
                                    <a href="?modulo=personal&accion=ver&id=' . $personal['id_personal'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    <a href="?modulo=personal&accion=editar&id=' . $personal['id_personal'] . '" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <a href="?modulo=personal&accion=eliminar&id=' . $personal['id_personal'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'¿Está seguro de que desea eliminar este registro?\')"><i class="fas fa-trash"></i></a>
                                  </td>';
                            echo '</tr>';
                        }
                        
                        if ($stmt_personal->rowCount() === 0) {
                            echo '<tr><td colspan="7" class="text-center">No hay personal registrado</td></tr>';
                        }
                        
                    } catch (PDOException $e) {
                        echo '<tr><td colspan="7" class="text-center text-danger">Error al cargar el personal</td></tr>';
                        error_log('Error al cargar personal: ' . $e->getMessage());
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($accion === 'nuevo'): ?>
    <!-- Formulario para agregar nuevo personal -->
    <div class="content-section">
        <div class="section-header">
            <h2>Nuevo Personal</h2>
            <a href="?modulo=personal" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
        
        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="?modulo=personal&accion=nuevo" class="row g-3">
            <div class="col-md-4">
                <label for="cedula" class="form-label">Cédula *</label>
                <input type="text" class="form-control" id="cedula" name="cedula" required value="<?php echo isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : ''; ?>">
            </div>
            
            <div class="col-md-4">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            </div>
            
            <div class="col-md-4">
                <label for="apellido" class="form-label">Apellido *</label>
                <input type="text" class="form-control" id="apellido" name="apellido" required value="<?php echo isset($_POST['apellido']) ? htmlspecialchars($_POST['apellido']) : ''; ?>">
            </div>
            
            <div class="col-md-4">
                <label for="id_rango" class="form-label">Rango *</label>
                <select class="form-select" id="id_rango" name="id_rango" required>
                    <option value="">Seleccione un rango</option>
                    <?php
                    try {
                        $stmt_rangos = $pdo->query("SELECT id_rango, nombre FROM rango ORDER BY nivel_jerarquico");
                        while ($rango = $stmt_rangos->fetch(PDO::FETCH_ASSOC)) {
                            $selected = (isset($_POST['id_rango']) && $_POST['id_rango'] == $rango['id_rango']) ? 'selected' : '';
                            echo '<option value="' . $rango['id_rango'] . '" ' . $selected . '>' . htmlspecialchars($rango['nombre']) . '</option>';
                        }
                    } catch (PDOException $e) {
                        error_log('Error al cargar rangos: ' . $e->getMessage());
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="id_departamento" class="form-label">Departamento</label>
                <select class="form-select" id="id_departamento" name="id_departamento">
                    <option value="">Sin asignar</option>
                    <?php
                    try {
                        $stmt_departamentos = $pdo->query("SELECT id_departamento, nombre FROM departamento ORDER BY nombre");
                        while ($departamento = $stmt_departamentos->fetch(PDO::FETCH_ASSOC)) {
                            $selected = (isset($_POST['id_departamento']) && $_POST['id_departamento'] == $departamento['id_departamento']) ? 'selected' : '';
                            echo '<option value="' . $departamento['id_departamento'] . '" ' . $selected . '>' . htmlspecialchars($departamento['nombre']) . '</option>';
                        }
                    } catch (PDOException $e) {
                        error_log('Error al cargar departamentos: ' . $e->getMessage());
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo isset($_POST['fecha_nacimiento']) ? htmlspecialchars($_POST['fecha_nacimiento']) : ''; ?>">
            </div>
            
            <div class="col-md-4">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
            </div>
            
            <div class="col-md-4">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo" value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>">
            </div>
            
            <div class="col-md-4">
                <div class="form-check mt-4">
                    <?php
                    $checked = '';
                    if (isset($_POST['activo'])) {
                        $checked = $_POST['activo'] ? 'checked' : '';
                    } elseif ($accion === 'editar' && isset($personal['activo'])) {
                        $checked = $personal['activo'] ? 'checked' : '';
                    } else {
                        $checked = 'checked'; // Por defecto, activo para nuevos registros
                    }
                    ?>
                    <input class="form-check-input" type="checkbox" id="activo" name="activo" <?php echo $checked; ?>>
                    <label class="form-check-label" for="activo">
                        Activo
                    </label>
                </div>
            </div>
            
            <div class="col-12">
                <label for="direccion" class="form-label">Dirección</label>
                <textarea class="form-control" id="direccion" name="direccion" rows="3"><?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?></textarea>
            </div>
            
            <div class="col-12 mt-3">
                <button type="submit" name="guardar_personal" class="btn btn-primary">Guardar</button>
                <a href="?modulo=personal" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
<?php elseif ($accion === 'ver' && isset($_GET['id'])): ?>
    <!-- Ver detalles de personal -->
    <?php
    try {
        $id_personal = intval($_GET['id']);
        
        $stmt_personal = $pdo->prepare("
            SELECT p.*, r.nombre as rango, d.nombre as departamento
            FROM personal p
            JOIN rango r ON p.id_rango = r.id_rango
            LEFT JOIN departamento d ON p.id_departamento = d.id_departamento
            WHERE p.id_personal = :id_personal
        ");
        $stmt_personal->bindParam(':id_personal', $id_personal);
        $stmt_personal->execute();
        
        $personal = $stmt_personal->fetch(PDO::FETCH_ASSOC);
        
        if (!$personal) {
            echo '<div class="alert alert-danger">El personal solicitado no existe.</div>';
            echo '<a href="?modulo=personal" class="btn btn-secondary">Volver a la lista</a>';
            exit;
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error al cargar los datos del personal.</div>';
        error_log('Error al cargar datos de personal: ' . $e->getMessage());
        echo '<a href="?modulo=personal" class="btn btn-secondary">Volver a la lista</a>';
        exit;
    }
    ?>
    
    <div class="content-section">
        <div class="section-header">
            <h2>Detalles del Personal</h2>
            <div>
                <a href="?modulo=personal&accion=editar&id=<?php echo $personal['id_personal']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Editar</a>
                <a href="?modulo=personal" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-title"><?php echo htmlspecialchars($personal['nombre'] . ' ' . $personal['apellido']); ?></h5>
                        <h6 class="card-subtitle mb-3 text-muted"><?php echo htmlspecialchars($personal['rango']); ?></h6>
                        
                        <p><strong>Cédula:</strong> <?php echo htmlspecialchars($personal['cedula']); ?></p>
                        <p><strong>Departamento:</strong> <?php echo htmlspecialchars($personal['departamento'] ?? 'Sin asignar'); ?></p>
                        <p><strong>Fecha de Nacimiento:</strong> <?php echo $personal['fecha_nacimiento'] ? formatearFecha($personal['fecha_nacimiento']) : 'No registrada'; ?></p>
                        <p><strong>Estado:</strong> <?php echo $personal['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>'; ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($personal['telefono'] ?? 'No registrado'); ?></p>
                        <p><strong>Correo Electrónico:</strong> <?php echo htmlspecialchars($personal['correo'] ?? 'No registrado'); ?></p>
                        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($personal['direccion'] ?? 'No registrada'); ?></p>
                        <p><strong>Fecha de Registro:</strong> <?php echo formatearFecha($personal['fecha_creacion']); ?></p>
                        <?php if ($personal['fecha_modificacion']): ?>
                            <p><strong>Última Modificación:</strong> <?php echo formatearFecha($personal['fecha_modificacion']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sección de guardias asignadas -->
        <div class="mt-4">
            <h4>Guardias Asignadas</h4>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
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
                            $stmt_guardias = $pdo->prepare("
                                SELECT g.id_guardia, pg.nombre as puesto, g.fecha_guardia, t.nombre as turno, g.estado
                                FROM guardia g
                                JOIN puesto_guardia pg ON g.id_puesto = pg.id_puesto
                                JOIN turno t ON g.id_turno = t.id_turno
                                WHERE g.id_personal = :id_personal
                                ORDER BY g.fecha_guardia DESC
                                LIMIT 10
                            ");
                            $stmt_guardias->bindParam(':id_personal', $id_personal);
                            $stmt_guardias->execute();
                            
                            while ($guardia = $stmt_guardias->fetch(PDO::FETCH_ASSOC)) {
                                echo '<tr>';
                                echo '<td>' . $guardia['id_guardia'] . '</td>';
                                echo '<td>' . htmlspecialchars($guardia['puesto']) . '</td>';
                                echo '<td>' . formatearFecha($guardia['fecha_guardia']) . '</td>';
                                echo '<td>' . htmlspecialchars($guardia['turno']) . '</td>';
                                echo '<td>' . generarBadgeEstado($guardia['estado']) . '</td>';
                                echo '<td>
                                        <a href="?modulo=guardias&accion=ver&id=' . $guardia['id_guardia'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                      </td>';
                                echo '</tr>';
                            }
                            
                            if ($stmt_guardias->rowCount() === 0) {
                                echo '<tr><td colspan="6" class="text-center">No hay guardias asignadas</td></tr>';
                            }
                            
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="6" class="text-center text-danger">Error al cargar las guardias</td></tr>';
                            error_log('Error al cargar guardias del personal: ' . $e->getMessage());
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Sección de ausencias registradas -->
        <div class="mt-4">
            <h4>Ausencias Registradas</h4>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt_ausencias = $pdo->prepare("
                                SELECT a.id_ausencia, ta.nombre as tipo_ausencia, a.fecha_inicio, a.fecha_fin, a.aprobado
                                FROM ausencia a
                                JOIN tipo_ausencia ta ON a.id_tipo_ausencia = ta.id_tipo_ausencia
                                WHERE a.id_personal = :id_personal
                                ORDER BY a.fecha_inicio DESC
                                LIMIT 10
                            ");
                            $stmt_ausencias->bindParam(':id_personal', $id_personal);
                            $stmt_ausencias->execute();
                            
                            while ($ausencia = $stmt_ausencias->fetch(PDO::FETCH_ASSOC)) {
                                echo '<tr>';
                                echo '<td>' . $ausencia['id_ausencia'] . '</td>';
                                echo '<td>' . htmlspecialchars($ausencia['tipo_ausencia']) . '</td>';
                                echo '<td>' . formatearFecha($ausencia['fecha_inicio']) . '</td>';
                                echo '<td>' . formatearFecha($ausencia['fecha_fin']) . '</td>';
                                echo '<td>';
                                if ($ausencia['aprobado']) {
                                    echo '<span class="badge bg-success">Aprobada</span>';
                                } else {
                                    echo '<span class="badge bg-warning">Pendiente</span>';
                                }
                                echo '</td>';
                                echo '<td>
                                        <a href="?modulo=ausencias&accion=ver&id=' . $ausencia['id_ausencia'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                      </td>';
                                echo '</tr>';
                            }
                            
                            if ($stmt_ausencias->rowCount() === 0) {
                                echo '<tr><td colspan="6" class="text-center">No hay ausencias registradas</td></tr>';
                            }
                            
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="6" class="text-center text-danger">Error al cargar las ausencias</td></tr>';
                            error_log('Error al cargar ausencias del personal: ' . $e->getMessage());
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($accion === 'editar' && isset($_GET['id'])): ?>
    <!-- Formulario para editar personal -->
    <?php
    try {
        $id_personal = intval($_GET['id']);
        
        // Si no es una solicitud POST, cargar los datos del personal
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['editar_personal'])) {
            $stmt_personal = $pdo->prepare("
                SELECT *
                FROM personal
                WHERE id_personal = :id_personal
            ");
            $stmt_personal->bindParam(':id_personal', $id_personal);
            $stmt_personal->execute();
            
            $personal = $stmt_personal->fetch(PDO::FETCH_ASSOC);
            
            if (!$personal) {
                echo '<div class="alert alert-danger">El personal solicitado no existe.</div>';
                echo '<a href="?modulo=personal" class="btn btn-secondary">Volver a la lista</a>';
                exit;
            }
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error al cargar los datos del personal.</div>';
        error_log('Error al cargar datos de personal para editar: ' . $e->getMessage());
        echo '<a href="?modulo=personal" class="btn btn-secondary">Volver a la lista</a>';
        exit;
    }
    ?>
    
    <div class="content-section">
        <div class="section-header">
            <h2>Editar Personal</h2>
            <a href="?modulo=personal" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
        
        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="?modulo=personal&accion=editar&id=<?php echo $id_personal; ?>" class="row g-3">
            <input type="hidden" name="id_personal" value="<?php echo $id_personal; ?>">
            
            <div class="col-md-4">
                <label for="cedula" class="form-label">Cédula *</label>
                <input type="text" class="form-control" id="cedula" name="cedula" required 
                       value="<?php echo isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : htmlspecialchars($personal['cedula']); ?>">
            </div>
            
            <div class="col-md-4">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required 
                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : htmlspecialchars($personal['nombre']); ?>">
            </div>
            
            <div class="col-md-4">
                <label for="apellido" class="form-label">Apellido *</label>
                <input type="text" class="form-control" id="apellido" name="apellido" required 
                       value="<?php echo isset($_POST['apellido']) ? htmlspecialchars($_POST['apellido']) : htmlspecialchars($personal['apellido']); ?>">
            </div>
            
            <div class="col-md-4">
                <label for="id_rango" class="form-label">Rango *</label>
                <select class="form-select" id="id_rango" name="id_rango" required>
                    <option value="">Seleccione un rango</option>
                    <?php
                    try {
                        $stmt_rangos = $pdo->query("SELECT id_rango, nombre FROM rango ORDER BY nivel_jerarquico");
                        while ($rango = $stmt_rangos->fetch(PDO::FETCH_ASSOC)) {
                            $selected = '';
                            if (isset($_POST['id_rango'])) {
                                $selected = ($_POST['id_rango'] == $rango['id_rango']) ? 'selected' : '';
                            } else {
                                $selected = ($personal['id_rango'] == $rango['id_rango']) ? 'selected' : '';
                            }
                            echo '<option value="' . $rango['id_rango'] . '" ' . $selected . '>' . htmlspecialchars($rango['nombre']) . '</option>';
                        }
                    } catch (PDOException $e) {
                        error_log('Error al cargar rangos: ' . $e->getMessage());
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="id_departamento" class="form-label">Departamento</label>
                <select class="form-select" id="id_departamento" name="id_departamento">
                    <option value="">Sin asignar</option>
                    <?php
                    try {
                        $stmt_departamentos = $pdo->query("SELECT id_departamento, nombre FROM departamento ORDER BY nombre");
                        while ($departamento = $stmt_departamentos->fetch(PDO::FETCH_ASSOC)) {
                            $selected = '';
                            if (isset($_POST['id_departamento'])) {
                                $selected = ($_POST['id_departamento'] == $departamento['id_departamento']) ? 'selected' : '';
                            } else {
                                $selected = ($personal['id_departamento'] == $departamento['id_departamento']) ? 'selected' : '';
                            }
                            echo '<option value="' . $departamento['id_departamento'] . '" ' . $selected . '>' . htmlspecialchars($departamento['nombre']) . '</option>';
                        }
                    } catch (PDOException $e) {
                        error_log('Error al cargar departamentos: ' . $e->getMessage());
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                       value="<?php echo isset($_POST['fecha_nacimiento']) ? htmlspecialchars($_POST['fecha_nacimiento']) : htmlspecialchars($personal['fecha_nacimiento'] ?? ''); ?>">
            </div>
            
            <div class="col-md-4">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="tel" class="form-control" id="telefono" name="telefono" 
                       value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : htmlspecialchars($personal['telefono'] ?? ''); ?>">
            </div>
            
            <div class="col-md-4">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo" 
                       value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : htmlspecialchars($personal['correo'] ?? ''); ?>">
            </div>
            
            <div class="col-md-4">
                <div class="form-check mt-4">
                    <?php
                    $checked = '';
                    if (isset($_POST['activo'])) {
                        $checked = $_POST['activo'] ? 'checked' : '';
                    } else {
                        $checked = $personal['activo'] ? 'checked' : '';
                    }
                    ?>
                    <input class="form-check-input" type="checkbox" id="activo" name="activo" <?php echo $checked; ?>>
                    <label class="form-check-label" for="activo">
                        Activo
                    </label>
                </div>
            </div>
            
            <div class="col-12">
                <label for="direccion" class="form-label">Dirección</label>
                <textarea class="form-control" id="direccion" name="direccion" rows="3"><?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : htmlspecialchars($personal['direccion'] ?? ''); ?></textarea>
            </div>
            
            <div class="col-12 mt-4">
                <button type="submit" name="editar_personal" class="btn btn-primary">Guardar Cambios</button>
                <a href="?modulo=personal" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Acción no válida -->
    <div class="alert alert-danger">Acción no válida</div>
    <a href="?modulo=personal" class="btn btn-secondary">Volver a la lista</a>
<?php endif; ?>