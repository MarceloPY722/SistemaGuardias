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

// Procesar formulario de nuevo departamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_departamento'])) {
    try {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $id_responsable = !empty($_POST['id_responsable']) ? intval($_POST['id_responsable']) : null;
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validaciones básicas
        $errores = [];
        
        if (empty($codigo)) {
            $errores[] = "El código es obligatorio";
        }
        
        if (empty($nombre)) {
            $errores[] = "El nombre es obligatorio";
        }
        
        // Si no hay errores, guardar en la base de datos
        if (empty($errores)) {
            // Verificar si el código ya existe
            $stmt_check = $pdo->prepare("SELECT id_departamento FROM departamento WHERE codigo = :codigo");
            $stmt_check->bindParam(':codigo', $codigo);
            $stmt_check->execute();
            
            if ($stmt_check->fetch()) {
                $errores[] = "Ya existe un departamento con este código";
            } else {
                // Insertar nuevo departamento
                $stmt = $pdo->prepare("
                    INSERT INTO departamento (
                        codigo, nombre, descripcion, id_responsable, activo
                    ) VALUES (
                        :codigo, :nombre, :descripcion, :id_responsable, :activo
                    )
                ");
                
                $stmt->bindParam(':codigo', $codigo);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':id_responsable', $id_responsable);
                $stmt->bindParam(':activo', $activo);
                
                if ($stmt->execute()) {
                    $mensaje_exito = "Departamento agregado correctamente";
                    // Redirigir a la lista después de agregar
                    header("Location: ?modulo=departamentos&mensaje=" . urlencode($mensaje_exito));
                    exit;
                } else {
                    $errores[] = "Error al guardar los datos";
                }
            }
        }
    } catch (PDOException $e) {
        $errores[] = "Error de base de datos: " . $e->getMessage();
        error_log("Error al agregar departamento: " . $e->getMessage());
    }
}

// Procesar formulario de edición de departamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_departamento'])) {
    try {
        $id_departamento = intval($_POST['id_departamento'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $id_responsable = !empty($_POST['id_responsable']) ? intval($_POST['id_responsable']) : null;
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validaciones básicas
        $errores = [];
        
        if ($id_departamento <= 0) {
            $errores[] = "ID de departamento inválido";
        }
        
        if (empty($codigo)) {
            $errores[] = "El código es obligatorio";
        }
        
        if (empty($nombre)) {
            $errores[] = "El nombre es obligatorio";
        }
        
        // Si no hay errores, actualizar en la base de datos
        if (empty($errores)) {
            // Verificar si el código ya existe para otro departamento
            $stmt_check = $pdo->prepare("SELECT id_departamento FROM departamento WHERE codigo = :codigo AND id_departamento != :id_departamento");
            $stmt_check->bindParam(':codigo', $codigo);
            $stmt_check->bindParam(':id_departamento', $id_departamento);
            $stmt_check->execute();
            
            if ($stmt_check->fetch()) {
                $errores[] = "Ya existe otro departamento con este código";
            } else {
                // Actualizar departamento
                $stmt = $pdo->prepare("
                    UPDATE departamento SET
                        codigo = :codigo,
                        nombre = :nombre,
                        descripcion = :descripcion,
                        id_responsable = :id_responsable,
                        activo = :activo,
                        fecha_modificacion = CURRENT_TIMESTAMP
                    WHERE id_departamento = :id_departamento
                ");
                
                $stmt->bindParam(':id_departamento', $id_departamento);
                $stmt->bindParam(':codigo', $codigo);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':id_responsable', $id_responsable);
                $stmt->bindParam(':activo', $activo);
                
                if ($stmt->execute()) {
                    $mensaje_exito = "Departamento actualizado correctamente";
                    // Redirigir a la lista después de editar
                    header("Location: ?modulo=departamentos&mensaje=" . urlencode($mensaje_exito));
                    exit;
                } else {
                    $errores[] = "Error al actualizar los datos";
                }
            }
        }
    } catch (PDOException $e) {
        $errores[] = "Error de base de datos: " . $e->getMessage();
        error_log("Error al editar departamento: " . $e->getMessage());
    }
}

// Procesar eliminación de departamento
if ($accion === 'eliminar' && isset($_GET['id'])) {
    try {
        $id_departamento = intval($_GET['id']);
        
        // Verificar si el departamento existe
        $stmt_check = $pdo->prepare("SELECT id_departamento FROM departamento WHERE id_departamento = :id_departamento");
        $stmt_check->bindParam(':id_departamento', $id_departamento);
        $stmt_check->execute();
        
        if (!$stmt_check->fetch()) {
            $mensaje_error = "El departamento no existe";
            header("Location: ?modulo=departamentos&error=" . urlencode($mensaje_error));
            exit;
        }
        
        // Verificar si hay personal asignado a este departamento
        $stmt_personal = $pdo->prepare("SELECT COUNT(*) FROM personal WHERE id_departamento = :id_departamento");
        $stmt_personal->bindParam(':id_departamento', $id_departamento);
        $stmt_personal->execute();
        $tiene_personal = ($stmt_personal->fetchColumn() > 0);
        
        if ($tiene_personal) {
            $mensaje_error = "No se puede eliminar el departamento porque tiene personal asignado";
            header("Location: ?modulo=departamentos&error=" . urlencode($mensaje_error));
            exit;
        }
        
        // Eliminar el departamento
        $stmt_delete = $pdo->prepare("DELETE FROM departamento WHERE id_departamento = :id_departamento");
        $stmt_delete->bindParam(':id_departamento', $id_departamento);
        
        if ($stmt_delete->execute()) {
            $mensaje_exito = "Departamento eliminado correctamente";
            header("Location: ?modulo=departamentos&mensaje=" . urlencode($mensaje_exito));
            exit;
        } else {
            $mensaje_error = "Error al eliminar el departamento";
            header("Location: ?modulo=departamentos&error=" . urlencode($mensaje_error));
            exit;
        }
    } catch (PDOException $e) {
        $mensaje_error = "Error de base de datos: " . $e->getMessage();
        error_log("Error al eliminar departamento: " . $e->getMessage());
        header("Location: ?modulo=departamentos&error=" . urlencode($mensaje_error));
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

<!-- Contenido del módulo de Departamentos -->
<?php if ($accion === 'listar'): ?>
    <!-- Listado de Departamentos -->
    <div class="content-section">
        <div class="section-header">
            <h2>Gestión de Departamentos</h2>
            <a href="?modulo=departamentos&accion=nuevo" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Departamento</a>
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
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Responsable</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $stmt_departamentos = $pdo->query("
                            SELECT d.id_departamento, d.codigo, d.nombre, 
                                   CONCAT(p.nombre, ' ', p.apellido) as responsable, d.activo
                            FROM departamento d
                            LEFT JOIN personal p ON d.id_responsable = p.id_personal
                            ORDER BY d.nombre
                        ");
                        
                        while ($departamento = $stmt_departamentos->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . $departamento['id_departamento'] . '</td>';
                            echo '<td>' . htmlspecialchars($departamento['codigo']) . '</td>';
                            echo '<td>' . htmlspecialchars($departamento['nombre']) . '</td>';
                            echo '<td>' . htmlspecialchars($departamento['responsable'] ?? 'Sin asignar') . '</td>';
                            echo '<td>';
                            if ($departamento['activo']) {
                                echo '<span class="badge bg-success">Activo</span>';
                            } else {
                                echo '<span class="badge bg-danger">Inactivo</span>';
                            }
                            echo '</td>';
                            echo '<td>
                                    <a href="?modulo=departamentos&accion=ver&id=' . $departamento['id_departamento'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    <a href="?modulo=departamentos&accion=editar&id=' . $departamento['id_departamento'] . '" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <a href="?modulo=departamentos&accion=eliminar&id=' . $departamento['id_departamento'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'¿Está seguro de que desea eliminar este registro?\')"><i class="fas fa-trash"></i></a>
                                  </td>';
                            echo '</tr>';
                        }
                        
                        if ($stmt_departamentos->rowCount() === 0) {
                            echo '<tr><td colspan="6" class="text-center">No hay departamentos registrados</td></tr>';
                        }
                        
                    } catch (PDOException $e) {
                        echo '<tr><td colspan="6" class="text-center text-danger">Error al cargar los departamentos</td></tr>';
                        error_log('Error al cargar departamentos: ' . $e->getMessage());
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($accion === 'nuevo'): ?>
    <!-- Formulario para agregar nuevo departamento -->
    <div class="content-section">
        <div class="section-header">
            <h2>Nuevo Departamento</h2>
            <a href="?modulo=departamentos" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
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
        
        <form method="POST" action="?modulo=departamentos&accion=nuevo" class="row g-3">
            <div class="col-md-4">
                <label for="codigo" class="form-label">Código *</label>
                <input type="text" class="form-control" id="codigo" name="codigo" required value="<?php echo isset($_POST['codigo']) ? htmlspecialchars($_POST['codigo']) : ''; ?>">
            </div>
            
            <div class="col-md-8">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            </div>
            
            <div class="col-md-6">
                <label for="id_responsable" class="form-label">Responsable</label>
                <select class="form-select" id="id_responsable" name="id_responsable">
                    <option value="">Sin asignar</option>
                    <?php
                    try {
                        $stmt_personal = $pdo->query("
                            SELECT p.id_personal, CONCAT(p.nombre, ' ', p.apellido, ' (', r.nombre, ')') as nombre_completo
                            FROM personal p
                            JOIN rango r ON p.id_rango = r.id_rango
                            WHERE p.activo = TRUE
                            ORDER BY r.nivel_jerarquico DESC, p.apellido, p.nombre
                        ");
                        while ($personal = $stmt_personal->fetch(PDO::FETCH_ASSOC)) {
                            $selected = (isset($_POST['id_responsable']) && $_POST['id_responsable'] == $personal['id_personal']) ? 'selected' : '';
                            echo '<option value="' . $personal['id_personal'] . '" ' . $selected . '>' . htmlspecialchars($personal['nombre_completo']) . '</option>';
                        }
                    } catch (PDOException $e) {
                        error_log('Error al cargar personal: ' . $e->getMessage());
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-6">
                <div class="form-check mt-4">
                    <?php
                    $checked = '';
                    if (isset($_POST['activo'])) {
                        $checked = $_POST['activo'] ? 'checked' : '';
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
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
            </div>
            
            <div class="col-12 mt-3">
                <button type="submit" name="guardar_departamento" class="btn btn-primary">Guardar</button>
                <a href="?modulo=departamentos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
<?php elseif ($accion === 'ver' && isset($_GET['id'])): ?>
    <!-- Ver detalles de departamento -->
    <?php
    try {
        $id_departamento = intval($_GET['id']);
        
        $stmt_departamento = $pdo->prepare("
            SELECT d.*, CONCAT(p.nombre, ' ', p.apellido) as responsable
            FROM departamento d
            LEFT JOIN personal p ON d.id_responsable = p.id_personal
            WHERE d.id_departamento = :id_departamento
        ");
        $stmt_departamento->bindParam(':id_departamento', $id_departamento);
        $stmt_departamento->execute();
        
        $departamento = $stmt_departamento->fetch(PDO::FETCH_ASSOC);
        
        if (!$departamento) {
            echo '<div class="alert alert-danger">El departamento solicitado no existe.</div>';
            echo '<a href="?modulo=departamentos" class="btn btn-secondary">Volver a la lista</a>';
            exit;
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error al cargar los datos del departamento.</div>';
        error_log('Error al cargar datos de departamento: ' . $e->getMessage());
        echo '<a href="?modulo=departamentos" class="btn btn-secondary">Volver a la lista</a>';
        exit;
    }
    ?>
    
    <div class="content-section">
        <div class="section-header">
            <h2>Detalles del Departamento</h2>
            <div>
                <a href="?modulo=departamentos&accion=editar&id=<?php echo $departamento['id_departamento']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Editar</a>
                <a href="?modulo=departamentos" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-title"><?php echo htmlspecialchars($departamento['nombre']); ?></h5>
                        <h6 class="card-subtitle mb-3 text-muted">Código: <?php echo htmlspecialchars($departamento['codigo']); ?></h6>
                        
                        <p><strong>Responsable:</strong> <?php echo htmlspecialchars($departamento['responsable'] ?? 'Sin asignar'); ?></p>
                        <p><strong>Estado:</strong> <?php echo $departamento['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>'; ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <p><strong>Descripción:</strong> <?php echo htmlspecialchars($departamento['descripcion'] ?? 'No registrada'); ?></p>
                        <p><strong>Fecha de Registro:</strong> <?php echo formatearFecha($departamento['fecha_creacion']); ?></p>
                        <?php if ($departamento['fecha_modificacion']): ?>
                            <p><strong>Última Modificación:</strong> <?php echo formatearFecha($departamento['fecha_modificacion']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sección de personal asignado -->
        <div class="mt-4">
            <h4>Personal Asignado</h4>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Rango</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt_personal = $pdo->prepare("
                                SELECT p.id_personal, p.cedula, CONCAT(p.nombre, ' ', p.apellido) as nombre_completo, 
                                       r.nombre as rango, p.activo
                                FROM personal p
                                JOIN rango r ON p.id_rango = r.id_rango
                                WHERE p.id_departamento = :id_departamento
                                ORDER BY r.nivel_jerarquico DESC, p.apellido, p.nombre
                            ");
                            $stmt_personal->bindParam(':id_departamento', $id_departamento);
                            $stmt_personal->execute();
                            
                            while ($personal = $stmt_personal->fetch(PDO::FETCH_ASSOC)) {
                                echo '<tr>';
                                echo '<td>' . $personal['id_personal'] . '</td>';
                                echo '<td>' . htmlspecialchars($personal['cedula']) . '</td>';
                                echo '<td>' . htmlspecialchars($personal['nombre_completo']) . '</td>';
                                echo '<td>' . htmlspecialchars($personal['rango']) . '</td>';
                                echo '<td>';
                                if ($personal['activo']) {
                                    echo '<span class="badge bg-success">Activo</span>';
                                } else {
                                    echo '<span class="badge bg-danger">Inactivo</span>';
                                }
                                echo '</td>';
                                echo '<td>
                                        <a href="?modulo=personal&accion=ver&id=' . $personal['id_personal'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                      </td>';
                                echo '</tr>';
                            }
                            
                            if ($stmt_personal->rowCount() === 0) {
                                echo '<tr><td colspan="6" class="text-center">No hay personal asignado a este departamento</td></tr>';
                            }
                            
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="6" class="text-center text-danger">Error al cargar el personal</td></tr>';
                            error_log('Error al cargar personal del departamento: ' . $e->getMessage());
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($accion === 'editar' && isset($_GET['id'])): ?>
    <!-- Formulario para editar departamento -->
    <?php
    try {
        $id_departamento = intval($_GET['id']);
        
        // Si no es una solicitud POST, cargar los datos del departamento
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['editar_departamento'])) {
            $stmt_departamento = $pdo->prepare("
                SELECT *
                FROM departamento
                WHERE id_departamento = :id_departamento
            ");
            $stmt_departamento->bindParam(':id_departamento', $id_departamento);
            $stmt_departamento->execute();
            
            $departamento = $stmt_departamento->fetch(PDO::FETCH_ASSOC);
            
            if (!$departamento) {
                echo '<div class="alert alert-danger">El departamento solicitado no existe.</div>';
                echo '<a href="?modulo=departamentos" class="btn btn-secondary">Volver a la lista</a>';
                exit;
            }
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error al cargar los datos del departamento.</div>';
        error_log('Error al cargar datos de departamento para editar: ' . $e->getMessage());
        echo '<a href="?modulo=departamentos" class="btn btn-secondary">Volver a la lista</a>';
        exit;
    }
    ?>
    
    <div class="content-section">
        <div class="section-header">
            <h2>Editar Departamento</h2>
            <a href="?modulo=departamentos" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
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
        
        <form method="POST" action="?modulo=departamentos&accion=editar&id=<?php echo $id_departamento; ?>" class="row g-3">
            <input type="hidden" name="id_departamento" value="<?php echo $id_departamento; ?>">
            
            <div class="col-md-4">
                <label for="codigo" class="form-label">Código *</label>
                <input type="text" class="form-control" id="codigo" name="codigo" required 
                       value="<?php echo isset($_POST['codigo']) ? htmlspecialchars($_POST['codigo']) : htmlspecialchars($departamento['codigo']); ?>">
            </div>
            
            <div class="col-md-8">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required 
                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : htmlspecialchars($departamento['nombre']); ?>">
            </div>
            
            <div class="col-md-6">
                <label for="id_responsable" class="form-label">Responsable</label>
                <select class="form-select" id="id_responsable" name="id_responsable">
                    <option value="">Sin asignar</option>
                    <?php
                    try {
                        $stmt_personal = $pdo->query("
                            SELECT p.id_personal, CONCAT(p.nombre, ' ', p.apellido, ' (', r.nombre, ')') as nombre_completo
                            FROM personal p
                            JOIN rango r ON p.id_rango = r.id_rango
                            WHERE p.activo = TRUE
                            ORDER BY r.nivel_jerarquico DESC, p.apellido, p.nombre
                        ");
                        while ($personal = $stmt_personal->fetch(PDO::FETCH_ASSOC)) {
                            $selected = '';
                            if (isset($_POST['id_responsable'])) {
                                $selected = ($_POST['id_responsable'] == $personal['id_personal']) ? 'selected' : '';
                            } else {
                                $selected = ($departamento['id_responsable'] == $personal['id_personal']) ? 'selected' : '';
                            }
                            echo '<option value="' . $personal['id_personal'] . '" ' . $selected . '>' . htmlspecialchars($personal['nombre_completo']) . '</option>';
                        }
                    } catch (PDOException $e) {
                        error_log('Error al cargar personal: ' . $e->getMessage());
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-6">
                <div class="form-check mt-4">
                    <?php
                    $checked = '';
                    if (isset($_POST['activo'])) {
                        $checked = $_POST['activo'] ? 'checked' : '';
                    } else {
                        $checked = $departamento['activo'] ? 'checked' : '';
                    }
                    ?>
                    <input class="form-check-input" type="checkbox" id="activo" name="activo" <?php echo $checked; ?>>
                    <label class="form-check-label" for="activo">
                        Activo
                    </label>
                </div>
            </div>
            
            <div class="col-12">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : htmlspecialchars($departamento['descripcion'] ?? ''); ?></textarea>
            </div>
            
            <div class="col-12 mt-3">
                <button type="submit" name="editar_departamento" class="btn btn-primary">Guardar Cambios</button>
                <a href="?modulo=departamentos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Acción no válida -->
    <div class="alert alert-danger">Acción no válida</div>
    <a href="?modulo=departamentos" class="btn btn-secondary">Volver a la lista</a>
<?php endif; ?>