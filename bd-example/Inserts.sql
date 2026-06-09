-- =====================================================================
-- INSERCIÓN Y POBLACIÓN DE DATOS REALEZADOS
-- =====================================================================

-- ROLES Y PERMISOS
INSERT INTO Rol (nombre) VALUES 
('Administrador'),
('Coordinador Académico'),
('Docente'),
('Postulante');

INSERT INTO Permiso (modulo, descripcion) VALUES 
('Seguridad', 'Gestión total de usuarios, roles y accesos de la bitácora'),
('Convocatorias', 'Creación y parametrización de gestiones y convocatorias'),
('Inscripciones', 'Registro de postulantes y asignación logística de grupos'),
('Evaluaciones', 'Configuración de ponderaciones y subida masiva de calificaciones'),
('Pagos', 'Monitoreo de transacciones de la pasarela Stripe');

INSERT INTO Rol_Permiso (id_rol, id_permiso) VALUES 
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
(2, 2), (2, 3), (2, 4),
(3, 4);

-- GESTIÓN Y CONVOCATORIAS
INSERT INTO Gestion (nombre, fecha_inicio, fecha_fin, estado) VALUES 
('Gestión Académica 2026', '2026-01-01', '2026-12-31', 'ACTIVA');

INSERT INTO Convocatoria (id_gestion, nombre, fecha_creacion, fecha_limite_inscripcion, estado) VALUES 
(1, 'Primer PSA FICCT 2026', '2026-01-05', '2026-02-15', 'ABIERTA'),
(1, 'Examen de Suficiencia Cupo Directo', '2026-01-10', '2026-02-20', 'ABIERTA');

-- ENTORNO ACADÉMICO, GRUPOS Y INFRAESTRUCTURA
INSERT INTO Aula (nombre, capacidad, ubicacion) VALUES 
('Aula 236-1 (Laboratorio)', 40, 'Módulo 236, Planta Baja'),
('Aula 236-5', 60, 'Módulo 236, Piso 2'),
('Paraninfo FICCT', 150, 'Módulo 236, Planta Baja');

INSERT INTO Grupo (sigla, nombre, turno, capacidad_max) VALUES 
('Z1', 'Grupo PSA Mañana - Sistemas', 'Mañana', 70),
('Z2', 'Grupo PSA Tarde - Informática', 'Tarde', 70);

INSERT INTO Materia (sigla, nombre, area) VALUES 
('MAT001', 'Matemáticas Avanzadas', 'Exactas'),
('FIS001', 'Física General', 'Exactas'),
('COM001', 'Introducción a la Computación', 'Tecnología');

INSERT INTO Area (nombre, descripcion) VALUES 
('Área de Exactas', 'Desarrollo de competencias lógicas, cálculo y física base'),
('Área de Tecnología', 'Fundamentos de programación, algoritmos y arquitectura de software');

INSERT INTO Carrera (nombre, modalidad, codigo, plan, area) VALUES 
('Ingeniería en Sistemas', 'Presencial', '187-3', 'Plan 2015', 'Informática'),
('Ingeniería Informática', 'Presencial', '187-4', 'Plan 2015', 'Informática'),
('Ingeniería en Redes y Telecomunicaciones', 'Presencial', '187-5', 'Plan 2016', 'Telecomunicaciones');

-- UNIDADES EDUCATIVAS
INSERT INTO Unidad_Educativa (nombre, tipo, provincia) VALUES 
('Colegio La Salle', 'Privado', 'Andrés Ibáñez'),
('Colegio Nacional Florida', 'Fiscal', 'Andrés Ibáñez'),
('Colegio Cardenal Cushing', 'Convenio', 'Andrés Ibáñez'),
('Colegio Gabriel René Moreno', 'Fiscal', 'Obispo Santistevan'),
('Colegio Marista', 'Privado', 'Andrés Ibáñez');

-- POBLACIÓN CONJUNTA DE USUARIOS (14 REGISTROS EN TOTAL)
-- Contraseñas hasheadas en formato bcrypt estándar para ambiente de pruebas
INSERT INTO Usuario (id_rol, ci, nombres, apellidos, correo, telefono1, fecha_nacimiento, sexo, contrasena, EstaActivo) VALUES 
(1, '5544332', 'Nicolas Junior', 'Verduguez Teran', 'nicolas@uagrm.edu.bo', '71020304', '2001-05-12', 'M', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE), -- ID 1
(2, '6655443', 'Rodrigo', 'Zurita Alvarez', 'rodrigo@uagrm.edu.bo', '72030405', '2000-08-22', 'M', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE), -- ID 2
(3, '1122334', 'Angelica', 'Garzon Cuellar', 'agarzon@uagrm.edu.bo', '73040506', '1985-03-15', 'F', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE), -- ID 3 (Docente 1)
(3, '4433221', 'Alberto', 'Molles Ureña', 'amolles@uagrm.edu.bo', '74050607', '1979-11-02', 'M', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE), -- ID 4 (Docente 2)
(4, '8877665', 'Juan Carlos', 'Pérez Choque', 'juan.perez@gmail.com', '61010203', '2007-04-18', 'M', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE), -- ID 5 (Postulante 1)
(4, '9988776', 'María Fernanda', 'Gómez Rojas', 'mafer.gomez@gmail.com', '62020304', '2008-01-25', 'F', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE), -- ID 6 (Postulante 2)
(1, '8833221', 'Carlos Eduardo', 'Mendoza Arteaga', 'carlos.mendoza@uagrm.edu.bo', '75011223', '1995-02-10', 'M', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE), -- ID 7
(2, '7744112', 'Claudia Vanessa', 'Roca Justiniano', 'claudia.roca@uagrm.edu.bo', '76044556', '1992-07-30', 'F', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE), -- ID 8
(3, '3322115', 'Evans', 'Balcazar Camacho', 'ebalcazar@uagrm.edu.bo', '71234567', '1980-04-05', 'M', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE), -- ID 9 (Docente 3)
(3, '4455661', 'Mario Lopez', 'Valenzuela', 'mlopez@uagrm.edu.bo', '72145678', '1975-09-14', 'M', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE), -- ID 10 (Docente 4)
(4, '10203040', 'Kevin Bryan', 'Mamani Quispe', 'kevin.mamani@gmail.com', '60011223', '2008-03-12', 'M', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE), -- ID 11 (Postulante 3)
(4, '10304050', 'Camila Alejandra', 'Suarez Vargas', 'camila.suarez@gmail.com', '60044556', '2007-11-05', 'F', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE), -- ID 12 (Postulante 4)
(4, '10405060', 'Diego Armando', 'Flores Choque', 'diego.flores@gmail.com', '60077889', '2008-01-20', 'M', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE), -- ID 13 (Postulante 5)
(4, '10506070', 'Luciana Belen', 'Torres Mendez', 'luciana.torres@gmail.com', '65011223', '2007-06-15', 'F', '$2b$10$EixZaYVK1Ewnd7b2fxgGfeG5K63aW', TRUE); -- ID 14 (Postulante 6)

-- ENTIDAD ESPECIALIZADA: DOCENTES
INSERT INTO Docente (id_docente, profesion, carga_horaria, especialidad, tiene_maestria, tiene_diplomado) VALUES 
(3, 'Ingeniera de Sistemas', 40, 'Gestión de Bases de Datos e Ingeniería de Software', TRUE, TRUE),
(4, 'Licenciado en Ciencias Físicas', 32, 'Física Cuántica y Mecánica Racional', FALSE, TRUE),
(9, 'Ingeniero Informático', 40, 'Estructuras de Datos y Desarrollo de Algoritmos', TRUE, TRUE),
(10, 'Licenciado en Matemáticas', 16, 'Álgebra Lineal y Cálculo Numérico', FALSE, FALSE);

INSERT INTO Area_Docente (id_area, id_docente) VALUES 
(2, 3), (1, 4), (2, 9), (1, 10);

-- LOGÍSTICA ACADÉMICA (ASIGNACIONES DE GRUPOS Y PLANIFICACIÓN HORARIA)
INSERT INTO Materia_Grupo (id_grupo, id_materia, id_aula, id_docente) VALUES 
(1, 3, 1, 3), -- ID 1: Computación, G_Z1, Aula 1, Docente Angelica
(1, 1, 2, 4); -- ID 2: Matemáticas, G_Z1, Aula 2, Docente Alberto

INSERT INTO Horario (id_materia_grupo, dia_semana, hora_inicio, hora_fin) VALUES 
(1, 'Lunes', '07:30:00', '09:45:00'),
(1, 'Miércoles', '07:30:00', '09:45:00'),
(2, 'Martes', '09:45:00', '12:00:00'),
(2, 'Jueves', '09:45:00', '12:00:00');

-- ENTIDAD ESPECIALIZADA: POSTULANTES
INSERT INTO Postulante (id_postulante, id_unidad, codigo_tramite, procedencia, titulo_bachiller, anio_egreso) VALUES 
(5, 1, 2026001, 'Santa Cruz de la Sierra', TRUE, 2025),  -- Juan Carlos (La Salle)
(6, 2, 2026002, 'Montero', TRUE, 2025),                 -- María Fernanda (Nac. Florida)
(11, 3, 2026003, 'Santa Cruz de la Sierra', TRUE, 2025), -- Kevin (Cushing)
(12, 4, 2026004, 'Montero', TRUE, 2025),                 -- Camila (G.R.M Montero)
(13, 5, 2026005, 'Santa Cruz de la Sierra', TRUE, 2025), -- Diego (Marista)
(14, 1, 2026006, 'Cotoca', TRUE, 2025);                 -- Luciana (La Salle)

-- PROCESO DE INSCRIPCIONES
INSERT INTO Inscripcion (id_postulante, id_grupo, id_convocatoria, turno_preferencia, fecha_inscripcion, estado_academico) VALUES 
(5, 1, 1, 'MAÑANA', '2026-01-15', 'PENDIENTE'),  -- Inscripcion 1
(6, 1, 1, 'MAÑANA', '2026-01-18', 'PENDIENTE'),  -- Inscripcion 2
(11, 1, 1, 'MAÑANA', '2026-01-20', 'PENDIENTE'), -- Inscripcion 3
(12, 1, 1, 'MAÑANA', '2026-01-22', 'PENDIENTE'), -- Inscripcion 4
(13, 2, 1, 'TARDE', '2026-01-25', 'PENDIENTE'),  -- Inscripcion 5
(14, 2, 1, 'TARDE', '2026-01-26', 'PENDIENTE');  -- Inscripcion 6

INSERT INTO Carrera_Inscripcion (id_carrera, id_inscripcion) VALUES 
(1, 1), -- Juan Carlos -> Sistemas
(2, 2), -- María Fernanda -> Informática
(1, 3), -- Kevin -> Sistemas
(2, 4), -- Camila -> Informática
(3, 5), -- Diego -> Redes
(3, 6); -- Luciana -> Redes

-- REGLAS DE NEGOCIO, NOTAS Y SUBCALIFICACIONES (CICLO 2)
INSERT INTO Regla_Evaluacion (id_convocatoria, id_carrera, id_materia, porcentaje) VALUES 
(1, 1, 3, 40), -- Computación vale 40% en Sistemas
(1, 1, 1, 60); -- Matemáticas vale 60% en Sistemas

INSERT INTO Evaluacion (id_inscripcion, numero_examen, nota_final_calculada, fecha) VALUES 
(1, 1, 82.00, '2026-02-25'),
(2, 1, 45.00, '2026-02-25');

INSERT INTO SubCalificacion (id_evaluacion, id_regla_evaluacion, nota_materia_cruda) VALUES 
(1, 1, 85.00), -- 85 * 0.40 = 34
(1, 2, 80.00), -- 80 * 0.60 = 48 -> Total: 82.00
(2, 1, 50.00), -- 50 * 0.40 = 20
(2, 2, 41.66); -- 41.66 * 0.60 = 25 -> Total: 45.00

-- PASARELA DE PAGOS (STRIPE INTEGRATION)
INSERT INTO Pago (id_inscripcion, fecha, monto, estado, metodo, transaccion_id, seguridad_hash) VALUES 
(1, '2026-01-15 14:22:10', 700.00, 'APROBADO', 'Tarjeta de Crédito', 'ch_3Mv8L2LkdIwHu7ix1AbCDeFg', 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'),
(2, '2026-01-18 09:05:43', 700.00, 'APROBADO', 'Pago con QR (Stripe)', 'ch_3Mv8M5LkdIwHu7ix2XyZwVut', '7f83b1657ff1fc53b92dc18148a1d65dfc2d4b1fa3d677284addd200126d9069'),
(3, '2026-01-20 11:40:00', 700.00, 'APROBADO', 'Tarjeta de Débito', 'ch_3Mv8N9LkdIwHu7ix3TpKqWrs', '9a8b7c6d5e4f3g2h1i0j9k8l7m6n5o4p3q2r1s0t9u8v7w6x5y4z3a2b1c0d9e8f');

-- BITÁCORA DE AUDITORÍA
INSERT INTO Bitacora (tabla, operacion, datos_anteriores, datos_nuevos, id_usuario, ip_origen, user_agent) VALUES 
('Convocatoria', 'INSERT', NULL, '{"nombre": "Primer PSA FICCT 2026", "id_gestion": 1}', 1, '192.168.1.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/121.0.0.0'),
('Evaluacion', 'INSERT', NULL, '{"id_inscripcion": 1, "nota_final_calculada": 82.00}', 2, '192.168.1.20', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/605.1.15');
