-- ==========================================
-- 1. TABLAS BASE: ROLES, PERMISOS Y GESTIÓN
-- ==========================================
CREATE TABLE Rol (
    id_rol SERIAL PRIMARY KEY,
    nombre VARCHAR(50) UNIQUE NOT NULL
);

CREATE TABLE Permiso (
    id_permiso SERIAL PRIMARY KEY,
    modulo VARCHAR(50) NOT NULL,
    descripcion TEXT
);

CREATE TABLE Rol_Permiso (
	id_rol_permiso SERIAL PRIMARY KEY,
    id_rol INT REFERENCES Rol(id_rol) ON DELETE CASCADE,
    id_permiso INT REFERENCES Permiso(id_permiso) ON DELETE CASCADE
);

CREATE TABLE Gestion (
    id_gestion SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL, -- Ej: "Gestión 2026"
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado VARCHAR(20) DEFAULT 'ACTIVA' CHECK (estado IN ('ACTIVA', 'CERRADA'))
);

CREATE TABLE Convocatoria (
    id_convocatoria SERIAL PRIMARY KEY,
    id_gestion INT REFERENCES Gestion(id_gestion) ON DELETE CASCADE, 
    nombre VARCHAR(50) NOT NULL, -- Ej: "Primer PSA 2026", "Cupo Directo 2026"
    fecha_creacion DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_limite_inscripcion DATE NOT NULL,
    estado VARCHAR(20) DEFAULT 'ABIERTA' CHECK (estado IN ('ABIERTA', 'PROCESO_EVALUACION', 'CONCLUIDA'))
);

-- ==========================================
-- 2. USUARIOS (Estructura de Generalización)
-- ==========================================

CREATE TABLE Usuario (
    id_usuario SERIAL PRIMARY KEY,
    id_rol INT REFERENCES Rol(id_rol) ON DELETE SET NULL,
    ci VARCHAR(20) UNIQUE NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    telefono1 VARCHAR(20),
    telefono2 VARCHAR(20),
    fecha_nacimiento DATE NOT NULL,
    sexo VARCHAR(10) CHECK (sexo IN ('M', 'F', 'Otro')),
    contrasena VARCHAR(255) NOT NULL,
    EstaActivo BOOLEAN DEFAULT TRUE
);

-- ==========================================
-- 3. INFRAESTRUCTURA, MATERIAS Y GRUPOS
-- ==========================================

CREATE TABLE Aula (
    id_aula SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    capacidad INT NOT NULL CHECK (capacidad > 0),
    ubicacion VARCHAR(100)
);

CREATE TABLE Grupo (
    id_grupo SERIAL PRIMARY KEY,
    sigla VARCHAR(20) UNIQUE NOT NULL, -- Ej: G1, G2
    nombre VARCHAR(50) NOT NULL,
    turno VARCHAR(20) CHECK (turno IN ('Mañana', 'Tarde', 'Noche')),
    capacidad_max INT DEFAULT 70
);

CREATE TABLE Materia (
    id_materia SERIAL PRIMARY KEY,
    sigla VARCHAR(20) UNIQUE NOT NULL, -- Ej: MAT001 (Matemáticas), FIS001 (Física)
    nombre VARCHAR(100) NOT NULL,
    area VARCHAR(50)
);

-- ==========================================
-- 4. ESPECIALIZACIÓN: DOCENTES Y ÁREAS
-- ==========================================

CREATE TABLE Area (
    id_area SERIAL PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL, 
    descripcion TEXT
);

CREATE TABLE Docente (
    id_docente INT PRIMARY KEY REFERENCES Usuario(id_usuario) ON DELETE CASCADE,
    profesion VARCHAR(100),
    carga_horaria INT,
    especialidad VARCHAR(100),
    tiene_maestria BOOLEAN DEFAULT FALSE,
    tiene_diplomado BOOLEAN DEFAULT FALSE
);

CREATE TABLE Area_Docente (
    id_area_docente SERIAL PRIMARY KEY,
    id_area INT REFERENCES Area(id_area) ON DELETE CASCADE,
    id_docente INT REFERENCES Docente(id_docente) ON DELETE CASCADE
);

-- ==========================================
-- 5. ENTIDAD INTERMEDIA: MATERIA_GRUPO Y HORARIOS
-- ==========================================

CREATE TABLE Materia_Grupo (
    id_materia_grupo SERIAL PRIMARY KEY,
    id_grupo INT REFERENCES Grupo(id_grupo) ON DELETE CASCADE,
    id_materia INT REFERENCES Materia(id_materia) ON DELETE CASCADE,
    id_aula INT REFERENCES Aula(id_aula) ON DELETE SET NULL,
    id_docente INT REFERENCES Docente(id_docente) ON DELETE SET NULL
);

CREATE TABLE Horario (
    id_horario SERIAL PRIMARY KEY,
    id_materia_grupo INT REFERENCES Materia_Grupo(id_materia_grupo) ON DELETE CASCADE,
    dia_semana VARCHAR(20) CHECK (dia_semana IN ('Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado')),
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL
);

-- ==========================================
-- 6. ESPECIALIZACIÓN: POSTULANTES E INSCRIPCIÓN
-- ==========================================

CREATE TABLE Unidad_Educativa (
    id_unidad SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo VARCHAR(20) CHECK (tipo IN ('Fiscal', 'Convenio', 'Privado', 'Otro')),
    provincia VARCHAR(100)
);

CREATE TABLE Carrera (
    id_carrera SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    modalidad VARCHAR(50),
    codigo VARCHAR(20) UNIQUE NOT NULL,
    plan VARCHAR(50),
    area VARCHAR(50)
);

CREATE TABLE Postulante (
    id_postulante INT PRIMARY KEY REFERENCES Usuario(id_usuario) ON DELETE CASCADE,
    id_unidad INT REFERENCES Unidad_Educativa(id_unidad) ON DELETE SET NULL,
    codigo_tramite INT UNIQUE NOT NULL,
    procedencia VARCHAR(100),
    titulo_bachiller BOOLEAN DEFAULT TRUE,
    anio_egreso INT
);

CREATE TABLE Inscripcion (
    id_inscripcion SERIAL PRIMARY KEY,
    id_postulante INT REFERENCES Postulante(id_postulante) ON DELETE CASCADE,
    id_grupo INT REFERENCES Grupo(id_grupo) ON DELETE SET NULL, 
    id_convocatoria INT REFERENCES Convocatoria(id_convocatoria) ON DELETE CASCADE, -- Vinculación directa con la Convocatoria
    turno_preferencia VARCHAR(10) CHECK (turno_preferencia IN ('MAÑANA', 'TARDE', 'NOCHE')),
    fecha_inscripcion DATE NOT NULL DEFAULT CURRENT_DATE,
    estado_academico VARCHAR(30) DEFAULT 'PENDIENTE' CHECK (estado_academico IN ('PENDIENTE', 'ELEGIBLE', 'ADMITIDO', 'REPROBADO', 'APROBADO_SIN_CUPO'))
);

CREATE TABLE Carrera_Inscripcion (
    id_carrera_inscripcion SERIAL PRIMARY KEY,
    id_carrera INT REFERENCES Carrera(id_carrera) ON DELETE CASCADE,
    id_inscripcion INT REFERENCES Inscripcion(id_inscripcion) ON DELETE CASCADE
);

-- ==========================================
-- 7. REGLAS DE NEGOCIO Y EVALUACIONES (CAMBIO CLAVE)
-- ==========================================

-- Ahora la regla de evaluación depende de la Carrera Y de la Convocatoria.
-- Permite que una materia valga 40% en un examen y 30% en el siguiente de la misma gestión.
CREATE TABLE Regla_Evaluacion (
    id_regla_evaluacion SERIAL PRIMARY KEY,
    id_convocatoria INT REFERENCES Convocatoria(id_convocatoria) ON DELETE CASCADE,
    id_carrera INT REFERENCES Carrera(id_carrera) ON DELETE CASCADE,
    id_materia INT REFERENCES Materia(id_materia) ON DELETE CASCADE, -- Qué materia se pondera
    porcentaje INT NOT NULL CHECK (porcentaje BETWEEN 0 AND 100)
);

CREATE TABLE Evaluacion (
    id_evaluacion SERIAL PRIMARY KEY,
    id_inscripcion INT REFERENCES Inscripcion(id_inscripcion) ON DELETE CASCADE,
    numero_examen INT CHECK (numero_examen BETWEEN 1 AND 3), -- Examen 1, Examen 2, etc.
    nota_final_calculada NUMERIC(5,2) DEFAULT 0.00 CHECK (nota_final_calculada BETWEEN 0 AND 100),
    fecha DATE DEFAULT CURRENT_DATE
);

-- Desglosa la nota real obtenida por materia bajo las reglas de esa convocatoria específica
CREATE TABLE SubCalificacion (
    id_subcalificacion SERIAL PRIMARY KEY,
    id_evaluacion INT REFERENCES Evaluacion(id_evaluacion) ON DELETE CASCADE,
    id_regla_evaluacion INT REFERENCES Regla_Evaluacion(id_regla_evaluacion) ON DELETE CASCADE,
    nota_materia_cruda NUMERIC(5,2) NOT NULL CHECK (nota_materia_cruda BETWEEN 0 AND 100) -- Nota sobre 100 en la materia
);

-- ==========================================
-- 8. CONTROL DE PAGOS Y PASARELA (STRIPE)
-- ==========================================

CREATE TABLE Pago (
    id_pago SERIAL PRIMARY KEY,
    id_inscripcion INT REFERENCES Inscripcion(id_inscripcion) ON DELETE CASCADE,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    monto NUMERIC(10,2) NOT NULL,
    estado VARCHAR(20) DEFAULT 'PENDIENTE' CHECK (estado IN ('PENDIENTE', 'APROBADO', 'RECHAZADO')),
    metodo VARCHAR(50), 
    transaccion_id VARCHAR(100) UNIQUE NOT NULL, 
    seguridad_hash VARCHAR(255)
);

-- ==========================================
-- 9. SEGURIDAD Y AUDITORÍA (BITÁCORA)
-- ==========================================

CREATE TABLE Bitacora (
    id_log SERIAL PRIMARY KEY,
    tabla VARCHAR(100) NOT NULL,
    operacion VARCHAR(20) CHECK (operacion IN ('INSERT', 'UPDATE', 'DELETE')),
    datos_anteriores JSONB,
    datos_nuevos JSONB,
    id_usuario INT REFERENCES Usuario(id_usuario) ON DELETE SET NULL,
    ip_origen VARCHAR(50),
    user_agent TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);