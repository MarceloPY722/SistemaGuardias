
-- Tabla de Rangos
CREATE TABLE rango (
    id_rango INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    nivel_jerarquico INT NOT NULL COMMENT 'Mayor número, mayor jerarquía',
    descripcion TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de Departamentos
CREATE TABLE departamento (
    id_departamento INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    id_supervisor INT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de Personal
CREATE TABLE personal (
    id_personal INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    id_rango INT NOT NULL,
    id_departamento INT,
    telefono VARCHAR(20),
    email VARCHAR(100),
    fecha_ingreso DATE NOT NULL,
    fecha_nacimiento DATE,
    direccion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rango) REFERENCES rango(id_rango),
    FOREIGN KEY (id_departamento) REFERENCES departamento(id_departamento)
);

-- Actualizar la referencia al supervisor en departamento
ALTER TABLE departamento
ADD CONSTRAINT fk_supervisor FOREIGN KEY (id_supervisor) REFERENCES personal(id_personal);

-- Tabla de Tipos de Ausencia
CREATE TABLE tipo_ausencia (
    id_tipo_ausencia INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    afecta_guardia BOOLEAN DEFAULT TRUE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de Ausencias
CREATE TABLE ausencia (
    id_ausencia INT AUTO_INCREMENT PRIMARY KEY,
    id_personal INT NOT NULL,
    id_tipo_ausencia INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    motivo TEXT,
    documento_respaldo VARCHAR(255),
    aprobado BOOLEAN DEFAULT FALSE,
    observaciones TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_personal) REFERENCES personal(id_personal),
    FOREIGN KEY (id_tipo_ausencia) REFERENCES tipo_ausencia(id_tipo_ausencia)
);

-- Tabla de Puestos de Guardia
CREATE TABLE puesto_guardia (
    id_puesto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    ubicacion VARCHAR(255) NOT NULL,
    descripcion TEXT,
    oficiales_requeridos INT DEFAULT 1,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de Turnos
CREATE TABLE turno (
    id_turno INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    duracion_horas INT GENERATED ALWAYS AS (
        TIMESTAMPDIFF(HOUR, hora_inicio, CASE WHEN hora_fin < hora_inicio THEN ADDTIME(hora_fin, '24:00:00') ELSE hora_fin END)
    ) STORED,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de Guardias
CREATE TABLE guardia (
    id_guardia INT AUTO_INCREMENT PRIMARY KEY,
    id_personal INT NOT NULL,
    id_puesto INT NOT NULL,
    id_turno INT NOT NULL,
    fecha_guardia DATE NOT NULL,
    estado ENUM('Pendiente', 'En progreso', 'Completada', 'Incumplida', 'Reasignada') DEFAULT 'Pendiente',
    observaciones TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_personal) REFERENCES personal(id_personal),
    FOREIGN KEY (id_puesto) REFERENCES puesto_guardia(id_puesto),
    FOREIGN KEY (id_turno) REFERENCES turno(id_turno),
    UNIQUE KEY uk_guardia_personal_fecha (id_personal, fecha_guardia, id_turno) COMMENT 'Impide que una persona tenga más de una guardia el mismo día en el mismo turno'
);

-- Tabla de Historial de Guardias
CREATE TABLE historial_guardia (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_guardia INT NOT NULL,
    id_personal INT NOT NULL,
    fecha_asignada DATE NOT NULL,
    estado_cumplimiento ENUM('Cumplida', 'Parcial', 'Incumplida'),
    observaciones TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_guardia) REFERENCES guardia(id_guardia),
    FOREIGN KEY (id_personal) REFERENCES personal(id_personal)
);

-- Tabla para seguimiento de conteo de guardias asignadas
CREATE TABLE contador_guardias_mensual (
    id_contador INT AUTO_INCREMENT PRIMARY KEY,
    id_personal INT NOT NULL,
    mes INT NOT NULL,
    anio INT NOT NULL,
    guardias_asignadas INT DEFAULT 0,
    ultima_asignacion DATE,
    FOREIGN KEY (id_personal) REFERENCES personal(id_personal),
    UNIQUE KEY uk_personal_mes_anio (id_personal, mes, anio)
);

-- Vista para obtener personal disponible para guardias
CREATE VIEW v_personal_disponible AS
SELECT 
    p.id_personal,
    p.nombre,
    p.apellido,
    r.nombre AS rango,
    r.nivel_jerarquico
FROM 
    personal p
JOIN 
    rango r ON p.id_rango = r.id_rango
WHERE 
    p.activo = TRUE
    AND p.id_personal NOT IN (
        SELECT id_personal 
        FROM ausencia 
        WHERE CURRENT_DATE BETWEEN fecha_inicio AND fecha_fin
        AND aprobado = TRUE
    )
ORDER BY 
    r.nivel_jerarquico, p.fecha_ingreso;

-- Procedimiento almacenado para asignación automática de guardias
DELIMITER //

CREATE PROCEDURE sp_asignar_guardias(
    IN fecha_asignacion DATE, 
    IN id_puesto_param INT, 
    IN id_turno_param INT
)
BEGIN
    -- Declaración de variables
    DECLARE done INT DEFAULT FALSE;
    DECLARE id_persona INT;
    DECLARE nivel_rango INT;
    DECLARE guardias_mes INT;
    DECLARE mes_actual INT;
    DECLARE anio_actual INT;
    DECLARE oficiales_req INT;
    
    -- Declaración del cursor para personal disponible
    DECLARE cur_personal CURSOR FOR
        SELECT 
            p.id_personal,
            r.nivel_jerarquico,
            COALESCE(cm.guardias_asignadas, 0) as guardias_mes
        FROM 
            personal p
        JOIN 
            rango r ON p.id_rango = r.id_rango
        LEFT JOIN 
            contador_guardias_mensual cm ON p.id_personal = cm.id_personal 
                AND cm.mes = MONTH(fecha_asignacion) 
                AND cm.anio = YEAR(fecha_asignacion)
        WHERE 
            p.activo = TRUE
            AND p.id_personal NOT IN (
                -- Excluir personal con ausencias en la fecha
                SELECT id_personal 
                FROM ausencia 
                WHERE fecha_asignacion BETWEEN fecha_inicio AND fecha_fin
                AND aprobado = TRUE
            )
            AND p.id_personal NOT IN (
                -- Excluir personal con guardia ya asignada ese día/turno
                SELECT id_personal 
                FROM guardia 
                WHERE fecha_guardia = fecha_asignacion
                AND id_turno = id_turno_param
            )
        ORDER BY 
            -- Primero los de menor rango que tengan menos de 2 guardias
            CASE WHEN COALESCE(cm.guardias_asignadas, 0) < 2 THEN 0 ELSE 1 END,
            r.nivel_jerarquico ASC,
            COALESCE(cm.guardias_asignadas, 0) ASC,
            p.fecha_ingreso ASC; -- Antigüedad como último criterio
    
    -- Declaración del manejador de errores
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Inicialización de variables
    SET mes_actual = MONTH(fecha_asignacion);
    SET anio_actual = YEAR(fecha_asignacion);
    
    -- Obtener el número de oficiales requeridos para el puesto
    SELECT oficiales_requeridos INTO oficiales_req 
    FROM puesto_guardia 
    WHERE id_puesto = id_puesto_param;
    
    -- Abrir el cursor
    OPEN cur_personal;
    
    -- Bucle para asignar guardias
    asignar_loop: LOOP
        FETCH cur_personal INTO id_persona, nivel_rango, guardias_mes;
        
        IF done OR oficiales_req <= 0 THEN
            LEAVE asignar_loop;
        END IF;
        
        -- Insertar la guardia
        INSERT INTO guardia (id_personal, id_puesto, id_turno, fecha_guardia)
        VALUES (id_persona, id_puesto_param, id_turno_param, fecha_asignacion);
        
        -- Actualizar o insertar contador de guardias
        INSERT INTO contador_guardias_mensual (id_personal, mes, anio, guardias_asignadas, ultima_asignacion)
        VALUES (id_persona, mes_actual, anio_actual, 1, fecha_asignacion)
        ON DUPLICATE KEY UPDATE 
            guardias_asignadas = guardias_asignadas + 1,
            ultima_asignacion = fecha_asignacion;
            
        SET oficiales_req = oficiales_req - 1;
    END LOOP;
    
    -- Cerrar el cursor
    CLOSE cur_personal;
END //

DELIMITER ;

-- Trigger para registrar historial cuando se completa una guardia
DELIMITER //

CREATE TRIGGER after_guardia_update
AFTER UPDATE ON guardia
FOR EACH ROW
BEGIN
    IF NEW.estado = 'Completada' AND OLD.estado != 'Completada' THEN
        INSERT INTO historial_guardia (id_guardia, id_personal, fecha_asignada, estado_cumplimiento)
        VALUES (NEW.id_guardia, NEW.id_personal, NEW.fecha_guardia, 'Cumplida');
    ELSEIF NEW.estado = 'Incumplida' AND OLD.estado != 'Incumplida' THEN
        INSERT INTO historial_guardia (id_guardia, id_personal, fecha_asignada, estado_cumplimiento)
        VALUES (NEW.id_guardia, NEW.id_personal, NEW.fecha_guardia, 'Incumplida');
    END IF;
END //

DELIMITER ;

-- Tabla de Administradores (para inicio de sesión)
CREATE TABLE admin (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Se recomienda almacenar contraseñas hasheadas
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de Credenciales
CREATE TABLE credenciales (
    id INT PRIMARY KEY,
    codigo INT NOT NULL
);

-- Insertar credencial específica
INSERT INTO credenciales (id, codigo) VALUES (1, 2002);

-- Inserción de datos de ejemplo
INSERT INTO rango (nombre, nivel_jerarquico, descripcion) VALUES
('Oficial', 1, 'Rango base'),
('Cabo', 2, 'Segundo nivel'),
('Sargento', 3, 'Tercer nivel'),
('Subteniente', 4, 'Cuarto nivel'),
('Teniente', 5, 'Quinto nivel'),
('Capitán', 6, 'Sexto nivel'),
('Mayor', 7, 'Séptimo nivel'),
('Teniente Coronel', 8, 'Octavo nivel'),
('Coronel', 9, 'Noveno nivel'),
('General', 10, 'Décimo nivel');

INSERT INTO tipo_ausencia (nombre, descripcion, afecta_guardia) VALUES
('Vacaciones', 'Período de descanso anual', TRUE),
('Reposo médico', 'Ausencia por enfermedad o lesión', TRUE),
('Permiso especial', 'Permiso por motivos personales', TRUE),
('Comisión de servicio', 'Asignación temporal a otra unidad', TRUE),
('Capacitación', 'Asistencia a cursos o entrenamientos', FALSE);

INSERT INTO puesto_guardia (nombre, ubicacion, descripcion, oficiales_requeridos) VALUES
('Entrada Principal', 'Edificio Central', 'Punto de control de acceso principal', 2),
('Área de Celdas', 'Subsuelo', 'Vigilancia de área de detención temporal', 1),
('Perímetro Sur', 'Exterior', 'Vigilancia del perímetro exterior zona sur', 2),
('Centro de Monitoreo', 'Segundo Piso', 'Control de cámaras y sistemas de seguridad', 1),
('Armería', 'Primer Piso', 'Control de acceso a armamento', 1);

INSERT INTO turno (nombre, hora_inicio, hora_fin) VALUES
('Mañana', '06:00:00', '14:00:00'),
('Tarde', '14:00:00', '22:00:00'),
('Noche', '22:00:00', '06:00:00');