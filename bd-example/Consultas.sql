-- =====================================================================
-- PARTE 1: CONSULTAS NORMALES
-- =====================================================================

-- 1. Listar todos los usuarios registrados que se encuentran activos en el sistema.
SELECT id_usuario, ci, nombres, apellidos, correo, EstaActivo 
FROM Usuario 
WHERE EstaActivo = TRUE;

-- 2. Obtener las convocatorias cuyo estado actual sea 'ABIERTA'.
SELECT id_convocatoria, nombre, fecha_creacion, fecha_limite_inscripcion, estado 
FROM Convocatoria 
WHERE estado = 'ABIERTA';

-- 3. Mostrar los grupos disponibles para el turno de la 'Mañana'.
SELECT id_grupo, sigla, nombre, turno, capacidad_max 
FROM Grupo 
WHERE turno = 'Mañana';

-- 4. Listar todas las carreras presenciales registradas en la facultad.
SELECT id_carrera, nombre, codigo, plan, area 
FROM Carrera 
WHERE modalidad = 'Presencial';

-- 5. Mostrar los colegios (unidades educativas) que pertenecen al tipo 'Fiscal'.
SELECT id_unidad, nombre, tipo, provincia 
FROM Unidad_Educativa 
WHERE tipo = 'Fiscal';

-- 6. Obtener todos los pagos que han sido 'APROBADO' por la pasarela de pagos.
SELECT id_pago, id_inscripcion, monto, estado, metodo, transaccion_id 
FROM Pago 
WHERE estado = 'APROBADO';

-- 7. Mostrar los docentes que tienen el grado académico de Maestría registrado.
SELECT id_docente, profesion, especialidad, tiene_maestria 
FROM Docente 
WHERE tiene_maestria = TRUE;

-- 8. Listar las materias que pertenecen al área de conocimiento 'Exactas'.
SELECT id_materia, sigla, nombre, area 
FROM Materia 
WHERE area = 'Exactas';

-- 9. Mostrar los registros de la bitácora que correspondan exclusivamente a operaciones de inserción ('INSERT').
SELECT id_log, tabla, operacion, id_usuario, fecha 
FROM Bitacora 
WHERE operacion = 'INSERT';

-- 10. Listar los postulantes que egresaron de sus colegios en el año 2025.
SELECT id_postulante, codigo_tramite, procedencia, anio_egreso 
FROM Postulante 
WHERE anio_egreso = 2025;


-- =====================================================================
-- PARTE 2: CONSULTAS MÚLTIPLES
-- =====================================================================

-- 11. Obtener los nombres, apellidos y el nombre del rol de todos los usuarios del sistema.
SELECT U.id_usuario, U.nombres, U.apellidos, R.nombre AS nombre_rol
FROM Usuario U, Rol R
WHERE U.id_rol = R.id_rol;

-- 12. Mostrar el nombre de la convocatoria y los datos generales de la gestión a la que pertenece.
SELECT C.nombre AS convocatoria, G.nombre AS gestion, G.fecha_inicio, G.fecha_fin
FROM Convocatoria C, Gestion G
WHERE C.id_gestion = G.id_gestion;

-- 13. Listar los permisos asignados a cada rol, mostrando el nombre del rol y el módulo afectado.
SELECT R.nombre AS rol, P.modulo, P.descripcion
FROM Rol R, Permiso P, Rol_Permiso RP
WHERE RP.id_rol = R.id_rol 
  AND RP.id_permiso = P.id_permiso;

-- 14. Obtener los datos personales de los usuarios que son específicamente Docentes, incluyendo su profesión y especialidad.
SELECT U.nombres, U.apellidos, D.profesion, D.especialidad
FROM Usuario U, Docente D
WHERE U.id_usuario = D.id_docente;

-- 15. Mostrar los datos personales de los Postulantes junto con el código de trámite y el nombre del colegio de donde provienen.
SELECT U.nombres, U.apellidos, P.codigo_tramite, UE.nombre AS colegio
FROM Usuario U, Postulante P, Unidad_Educativa UE
WHERE U.id_usuario = P.id_postulante 
  AND P.id_unidad = UE.id_unidad;

-- 16. Listar las materias asignadas a los grupos, mostrando la sigla de la materia, el nombre del grupo y el aula donde se dicta.
SELECT M.sigla, M.nombre AS materia, G.sigla AS grupo, A.nombre AS aula
FROM Materia_Grupo MG, Materia M, Grupo G, Aula A
WHERE MG.id_materia = M.id_materia 
  AND MG.id_grupo = G.id_grupo 
  AND MG.id_aula = A.id_aula;

-- 17. Mostrar los horarios planificados detallando el día, horas, nombre del grupo y nombre de la materia.
SELECT H.dia_semana, H.hora_inicio, H.hora_fin, G.nombre AS grupo, M.nombre AS materia
FROM Horario H, Materia_Grupo MG, Grupo G, Materia M
WHERE H.id_materia_grupo = MG.id_materia_grupo 
  AND MG.id_grupo = G.id_grupo 
  AND MG.id_materia = M.id_materia;

-- 18. Listar las inscripciones vigentes mostrando el nombre del postulante, el grupo asignado y la convocatoria en la que participa.
SELECT U.nombres, U.apellidos, G.sigla AS grupo, C.nombre AS convocatoria, I.estado_academico
FROM Inscripcion I, Postulante P, Usuario U, Grupo G, Convocatoria C
WHERE I.id_postulante = P.id_postulante 
  AND P.id_postulante = U.id_usuario 
  AND I.id_grupo = G.id_grupo 
  AND I.id_convocatoria = C.id_convocatoria;

-- 19. Obtener la lista de postulantes inscritos y las carreras a las que están aplicando en su trámite.
SELECT U.nombres, U.apellidos, C.nombre AS carrera_postulada
FROM Inscripcion I, Postulante P, Usuario U, Carrera_Inscripcion CI, Carrera C
WHERE I.id_postulante = P.id_postulante 
  AND P.id_postulante = U.id_usuario 
  AND CI.id_inscripcion = I.id_inscripcion 
  AND CI.id_carrera = C.id_carrera;

-- 20. Mostrar el control de pagos aprobados detallando el nombre del postulante que pagó, el monto y el ID de transacción de Stripe.
SELECT U.nombres, U.apellidos, PA.monto, PA.metodo, PA.transaccion_id
FROM Pago PA, Inscripcion I, Postulante P, Usuario U
WHERE PA.id_inscripcion = I.id_inscripcion 
  AND I.id_postulante = P.id_postulante 
  AND P.id_postulante = U.id_usuario;


-- =====================================================================
-- PARTE 3: CONSULTAS CON SUBCONSULTAS
-- =====================================================================

-- 21. Obtener los datos de los usuarios que poseen el rol de 'Postulante' utilizando subconsulta en el WHERE.
SELECT id_usuario, ci, nombres, apellidos, correo 
FROM Usuario 
WHERE id_rol = (
    SELECT id_rol 
    FROM Rol 
    WHERE nombre = 'Postulante'
);

-- 22. Listar las convocatorias que pertenecen a gestiones que se encuentran con estado 'ACTIVA'.
SELECT id_convocatoria, nombre, estado 
FROM Convocatoria 
WHERE id_gestion IN (
    SELECT id_gestion 
    FROM Gestion 
    WHERE estado = 'ACTIVA'
);

-- 23. Listar los docentes que dictan clases en el área de conocimiento de 'Área de Tecnología' usando subconsultas en cascada.
SELECT id_docente, profesion, especialidad 
FROM Docente 
WHERE id_docente IN (
    SELECT id_docente 
    FROM Area_Docente 
    WHERE id_area = (
        SELECT id_area 
        FROM Area 
        WHERE nombre = 'Área de Technology' OR nombre = 'Área de Tecnología'
    )
);

-- 24. Mostrar las materias que tienen asignado un porcentaje de ponderación mayor al 50% en alguna regla de evaluación.
SELECT id_materia, sigla, nombre 
FROM Materia 
WHERE id_materia IN (
    SELECT id_materia 
    FROM Regla_Evaluacion 
    WHERE porcentaje >= 50
);

-- 25. Obtener los postulantes cuya procedencia sea de colegios ubicados fuera de la provincia 'Andrés Ibáñez' (por ejemplo, 'Obispo Santistevan').
SELECT id_postulante, codigo_tramite, procedencia 
FROM Postulante 
WHERE id_unidad IN (
    SELECT id_unidad 
    FROM Unidad_Educativa 
    WHERE provincia <> 'Andrés Ibáñez'
);

-- 26. Seleccionar las inscripciones de aquellos postulantes que ya realizaron y aprobaron su pago correspondiente.
SELECT id_inscripcion, id_postulante, fecha_inscripcion, estado_academico 
FROM Inscripcion 
WHERE id_inscripcion IN (
    SELECT id_inscripcion 
    FROM Pago 
    WHERE estado = 'APROBADO'
);

-- 27. Mostrar los datos de las evaluaciones (notas finales) que pertenecen a estudiantes que postulan específicamente a la carrera de 'Ingeniería en Sistemas'.
SELECT id_evaluacion, numero_examen, nota_final_calculada 
FROM Evaluacion 
WHERE id_inscripcion IN (
    SELECT id_inscripcion 
    FROM Carrera_Inscripcion 
    WHERE id_carrera = (
        SELECT id_carrera 
        FROM Carrera 
        WHERE nombre = 'Ingeniería en Sistemas'
    )
);

-- 28. Encontrar los nombres de las materias que forman parte del examen número 1 y en las cuales se registraron subcalificaciones crudas.
SELECT id_materia, nombre 
FROM Materia 
WHERE id_materia IN (
    SELECT id_materia 
    FROM Regla_Evaluacion 
    WHERE id_regla_evaluacion IN (
        SELECT id_regla_evaluacion 
        FROM SubCalificacion 
        WHERE id_evaluacion IN (
            SELECT id_evaluacion 
            FROM Evaluacion 
            WHERE numero_examen = 1
        )
    )
);

-- 29. Listar las aulas cuya capacidad de espacio físico sea mayor que el promedio general de capacidad de todas las aulas registradas.
SELECT id_aula, nombre, capacidad, ubicacion 
FROM Aula 
WHERE capacidad > (
    SELECT AVG(capacidad) 
    FROM Aula
);

-- 30. Obtener las bitácoras de auditoría realizadas por usuarios que pertenecen al rol de 'Administrador'.
SELECT id_log, tabla, operacion, fecha 
FROM Bitacora 
WHERE id_usuario IN (
    SELECT id_usuario 
    FROM Usuario 
    WHERE id_rol = (
        SELECT id_rol 
        FROM Rol 
        WHERE nombre = 'Administrador'
    )
);