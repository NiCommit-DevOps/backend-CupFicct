-- =====================================================================
-- SCRIPT DE PROCEDIMIENTOS ALMACENADOS (STORED PROCEDURES)
-- =====================================================================

-- ---------------------------------------------------------------------
-- ENUNCIADO 1: Registrar un nuevo rol en el sistema verificando que el 
-- nombre no esté duplicado de forma manual (para personalizar la excepción).
-- ---------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE sp_registrar_rol(
    IN p_nombre VARCHAR(50)
)
LANGUAGE plpgsql
AS $$
BEGIN
    IF EXISTS (SELECT 1 FROM Rol WHERE LOWER(nombre) = LOWER(p_nombre)) THEN
        RAISE EXCEPTION 'El rol % ya se encuentra registrado.', p_nombre;
    END IF;

    INSERT INTO Rol (nombre) VALUES (p_nombre);
END;
$$;


-- ---------------------------------------------------------------------
-- ENUNCIADO 2: Crear un nuevo usuario en la tabla base e insertar de forma
-- automática su registro correspondiente en la tabla especializada de Docentes.
-- ---------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE sp_registrar_docente(
    IN p_id_rol INT,
    IN p_ci VARCHAR(20),
    IN p_nombres VARCHAR(100),
    IN p_apellidos VARCHAR(100),
    IN p_correo VARCHAR(100),
    IN p_telefono VARCHAR(20),
    IN p_fecha_nac DATE,
    IN p_sexo VARCHAR(10),
    IN p_contrasena VARCHAR(255),
    IN p_profesion VARCHAR(100),
    IN p_carga_horaria INT,
    IN p_especialidad VARCHAR(100)
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_id_usuario INT;
BEGIN
    -- Insertar en la tabla padre (Usuario)
    INSERT INTO Usuario (id_rol, ci, nombres, apellidos, correo, telefono1, fecha_nacimiento, sexo, contrasena)
    VALUES (p_id_rol, p_ci, p_nombres, p_apellidos, p_correo, p_telefono, p_fecha_nac, p_sexo, p_contrasena)
    RETURNING id_usuario INTO v_id_usuario;

    -- Insertar en la tabla hija (Docente) utilizando el ID generado
    INSERT INTO Docente (id_docente, profesion, carga_horaria, especialidad)
    VALUES (v_id_usuario, p_profesion, p_carga_horaria, p_especialidad);
END;
$$;


-- ---------------------------------------------------------------------
-- ENUNCIADO 3: Procesar la inscripción de un postulante a un grupo, validando 
-- previamente si el grupo aún cuenta con cupos disponibles (capacidad_max).
-- ---------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE sp_inscribir_postulante(
    IN p_id_postulante INT,
    IN p_id_grupo INT,
    IN p_id_convocatoria INT,
    IN p_turno_pref VARCHAR(10)
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_capacidad_max INT;
    v_actualmente_inscritos INT;
BEGIN
    -- Obtener la capacidad máxima permitida para el grupo
    SELECT capacidad_max INTO v_capacidad_max FROM Grupo WHERE id_grupo = p_id_grupo;
    
    -- Contar cuántos alumnos ya están asignados a este grupo
    SELECT COUNT(*) INTO v_actualmente_inscritos FROM Inscripcion WHERE id_grupo = p_id_grupo;

    -- Validar disponibilidad de cupo
    IF v_actualmente_inscritos >= v_capacidad_max THEN
        RAISE EXCEPTION 'No se puede inscribir. El grupo seleccionado ha alcanzado su límite de % estudiantes.', v_capacidad_max;
    END IF;

    -- Registrar la inscripción si pasa la validación
    INSERT INTO Inscripcion (id_postulante, id_grupo, id_convocatoria, turno_preference)
    VALUES (p_id_postulante, p_id_grupo, p_id_convocatoria, UPPER(p_turno_pref));
END;
$$;


-- ---------------------------------------------------------------------
-- ENUNCIADO 4: Registrar un pago proveniente de Stripe. Si el estado es 
-- 'APROBADO', el procedimiento actualiza automáticamente el estado académico 
-- de la inscripción del postulante de 'PENDIENTE' a 'ELEGIBLE'.
-- ---------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE sp_procesar_pago_stripe(
    IN p_id_inscripcion INT,
    IN p_monto NUMERIC(10,2),
    IN p_metodo VARCHAR(50),
    IN p_transaccion_id VARCHAR(100),
    IN p_hash VARCHAR(255),
    IN p_estado VARCHAR(20)
)
LANGUAGE plpgsql
AS $$
BEGIN
    -- Registrar la transacción en la tabla de Pagos
    INSERT INTO Pago (id_inscripcion, monto, estado, metodo, transaccion_id, seguridad_hash)
    VALUES (p_id_inscripcion, p_monto, p_estado, p_metodo, p_transaccion_id, p_hash);

    -- Si el pago fue aprobado, actualizar el flujo del estudiante
    IF UPPER(p_estado) = 'APROBADO' THEN
        UPDATE Inscripcion 
        SET estado_academico = 'ELEGIBLE' 
        WHERE id_inscripcion = p_id_inscripcion;
    END IF;
END;
$$;


-- ---------------------------------------------------------------------
-- ENUNCIADO 5: Insertar de forma masiva o individual una nota cruda por materia 
-- (SubCalificacion) y calcular de manera automática la ponderación finalizada 
-- para actualizar la cabecera de la tabla 'Evaluacion'.
-- ---------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE sp_registrar_subcalificacion(
    IN p_id_evaluacion INT,
    IN p_id_regla_evaluacion INT,
    IN p_nota_cruda NUMERIC(5,2)
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_porcentaje INT;
    v_nota_ponderada NUMERIC(5,2);
BEGIN
    -- Insertar la nota obtenida en la materia
    INSERT INTO SubCalificacion (id_evaluacion, id_regla_evaluacion, nota_materia_cruda)
    VALUES (p_id_evaluacion, p_id_regla_evaluacion, p_nota_cruda);

    -- Obtener el porcentaje de peso que tiene esta materia según la regla de la convocatoria
    SELECT porcentaje INTO v_porcentaje FROM Regla_Evaluacion WHERE id_regla_evaluacion = p_id_regla_evaluacion;

    -- Calcular el equivalente ponderado
    v_nota_ponderada := (p_nota_cruda * v_porcentaje) / 100.00;

    -- Acumular sumando el valor calculado a la nota final de la evaluación correspondiente
    UPDATE Evaluacion 
    SET nota_final_calculada = nota_final_calculada + v_nota_ponderada
    WHERE id_evaluacion = p_id_evaluacion;
END;
$$;


-- ---------------------------------------------------------------------
-- ENUNCIADO 6: Realizar el cierre masivo de una gestión académica, cambiando
-- su estado a 'CERRADA' y modificando automáticamente todas las convocatorias 
-- asociadas a ella al estado de 'CONCLUIDA'.
-- ---------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE sp_cerrar_gestion_academica(
    IN p_id_gestion INT
)
LANGUAGE plpgsql
AS $$
BEGIN
    -- Actualizar el estado de la gestión principal
    UPDATE Gestion 
    SET estado = 'CERRADA' 
    WHERE id_gestion = p_id_gestion;

    -- Clausurar en cascada lógica todas las convocatorias dependientes
    UPDATE Convocatoria 
    SET estado = 'CONCLUIDA' 
    WHERE id_gestion = p_id_gestion;
END;
$$;


-- ---------------------------------------------------------------------
-- ENUNCIADO 7: Cambiar la asignación de un docente de un grupo a otro en la 
-- tabla 'Materia_Grupo', verificando previamente que el nuevo docente exista.
-- ---------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE sp_reasignar_docente_grupo(
    IN p_id_materia_grupo INT,
    IN p_id_nuevo_docente INT
)
LANGUAGE plpgsql
AS $$
BEGIN
    -- Validar que el ID pertenezca a un docente registrado
    IF NOT EXISTS (SELECT 1 FROM Docente WHERE id_docente = p_id_nuevo_docente) THEN
        RAISE EXCEPTION 'El identificador % no corresponde a un docente válido.', p_id_nuevo_docente;
    END IF;

    -- Modificar la asignación logística
    UPDATE Materia_Grupo 
    SET id_docente = p_id_nuevo_docente 
    WHERE id_materia_grupo = p_id_materia_grupo;
END;
$$;


-- ---------------------------------------------------------------------
-- ENUNCIADO 8: Dar de baja de manera lógica a un usuario del sistema (cambiando 
-- EstaActivo a FALSE) y registrar la acción de control en la Bitacora de auditoría.
-- ---------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE sp_desactivar_usuario(
    IN p_id_usuario_baja INT,
    IN p_id_usuario_auditor INT,
    IN p_ip VARCHAR(50),
    IN p_agent TEXT
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_datos_antiguos JSONB;
BEGIN
    -- Capturar el estado previo para guardarlo en formato JSONB en la auditoría
    SELECT json_build_object('id_usuario', id_usuario, 'EstaActivo', EstaActivo)::jsonb 
    INTO v_datos_antiguos 
    FROM Usuario 
    WHERE id_usuario = p_id_usuario_baja;

    -- Desactivar la cuenta
    UPDATE Usuario 
    SET EstaActivo = FALSE 
    WHERE id_usuario = p_id_usuario_baja;

    -- Insertar el registro de control en la Bitácora
    INSERT INTO Bitacora (tabla, operacion, datos_anteriores, datos_nuevos, id_usuario, ip_origen, user_agent)
    VALUES ('Usuario', 'UPDATE', v_datos_antiguos, '{"EstaActivo": false}'::jsonb, p_id_usuario_auditor, p_ip, p_agent);
END;
$$;


-- ---------------------------------------------------------------------
-- ENUNCIADO 9: Modificar la infraestructura física asignada a una sección de 
-- clases ('Materia_Grupo'), controlando que el aula asignada cumpla con la 
-- capacidad necesaria para albergar el máximo establecido del grupo asociado.
-- ---------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE sp_cambiar_aula_grupo(
    IN p_id_materia_grupo INT,
    IN p_id_nueva_aula INT
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_capacidad_aula INT;
    v_capacidad_max_grupo INT;
BEGIN
    -- Obtener el límite de estudiantes del grupo asociado
    SELECT G.capacidad_max INTO v_capacidad_max_grupo
    FROM Materia_Grupo MG, Grupo G
    WHERE MG.id_grupo = G.id_grupo AND MG.id_materia_grupo = p_id_materia_grupo;

    -- Obtener el aforo del espacio físico seleccionado
    SELECT capacidad INTO v_capacidad_aula FROM Aula WHERE id_aula = p_id_nueva_aula;

    -- Validar si el espacio físico es óptimo
    IF v_capacidad_aula < v_capacidad_max_grupo THEN
        RAISE EXCEPTION 'El aula seleccionada tiene capacidad de % campos, insuficiente para los % requeridos por el grupo.', 
                        v_capacidad_aula, v_capacidad_max_grupo;
    END IF;

    -- Si cumple la regla, realizar el cambio
    UPDATE Materia_Grupo 
    SET id_aula = p_id_nueva_aula 
    WHERE id_materia_grupo = p_id_materia_grupo;
END;
$$;


-- ---------------------------------------------------------------------
-- ENUNCIADO 10: Evaluar la calificación acumulada de un postulante al finalizar 
-- el proceso y determinar su estado académico final ('ADMITIDO' si la nota es 
-- mayor o igual a 51, o 'REPROBADO' en caso contrario).
-- ---------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE sp_finalizar_estado_postulante(
    IN p_id_inscripcion INT
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_nota_acumulada NUMERIC(5,2);
BEGIN
    -- Obtener la nota calculada desde la cabecera de evaluaciones (asumiendo examen final o consolidado)
    SELECT COALESCE(nota_final_calculada, 0.00) INTO v_nota_acumulada 
    FROM Evaluacion 
    WHERE id_inscripcion = p_id_inscripcion 
    ORDER BY numero_examen DESC LIMIT 1;

    -- Evaluar la nota final bajo la regla base de aprobación (51 puntos)
    IF v_nota_acumulada >= 51.00 THEN
        UPDATE Inscripcion 
        SET estado_academico = 'ADMITIDO' 
        WHERE id_inscripcion = p_id_inscripcion;
    ELSE
        UPDATE Inscripcion 
        SET estado_academico = 'REPROBADO' 
        WHERE id_inscripcion = p_id_inscripcion;
    END IF;
END;
$$;
