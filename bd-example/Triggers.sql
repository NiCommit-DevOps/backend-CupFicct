-- =====================================================================
-- SCRIPT DE DISPARADORES (TRIGGERS) - POSTGRESQL
-- =====================================================================
-- ---------------------------------------------------------------------
-- ENUNCIADO 1: Auditoría Automática al Registrar un Usuario.
-- Cada vez que se inserte un nuevo registro en la tabla 'Usuario', el trigger
-- debe registrar automáticamente el evento en la tabla 'Bitacora' en formato JSONB.
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_tr_auditar_insert_usuario()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO Bitacora (tabla, operacion, datos_anteriores, datos_nuevos, id_usuario, ip_origen, user_agent)
    VALUES (
        'Usuario', 
        'INSERT', 
        NULL, 
        json_build_object('id_usuario', NEW.id_usuario, 'correo', NEW.correo, 'ci', NEW.ci)::jsonb,
        NULL, -- Se asume nulo o modificable por la app web
        '127.0.0.1', 
        'System Automated Trigger'
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_auditar_insert_usuario
AFTER INSERT ON Usuario
FOR EACH ROW
EXECUTE FUNCTION fn_tr_auditar_insert_usuario();


-- ---------------------------------------------------------------------
-- ENUNCIADO 2: Validación preventiva de fechas de Convocatoria.
-- Evitar que se cree una convocatoria si la fecha límite de inscripción es 
-- menor o igual a la fecha de creación (fecha actual).
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_tr_validar_fechas_convocatoria()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.fecha_limite_inscripcion <= CURRENT_DATE THEN
        RAISE EXCEPTION 'Error: La fecha límite de inscripción (%) no puede ser menor o igual a la fecha actual.', NEW.fecha_limite_inscripcion;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_validar_fechas_convocatoria
BEFORE INSERT OR UPDATE ON Convocatoria
FOR EACH ROW
EXECUTE FUNCTION fn_tr_validar_fechas_convocatoria();
-- ---------------------------------------------------------------------
-- ENUNCIADO 3: Evitar la inscripción de Postulantes inactivos.
-- Antes de procesar una fila en la tabla 'Inscripcion', verificar que el
-- usuario en la tabla 'Usuario' (base del Postulante) tenga 'EstaActivo' en TRUE.
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_tr_verificar_postulante_activo()
RETURNS TRIGGER AS $$
DECLARE
    v_activo BOOLEAN;
BEGIN
    SELECT EstaActivo INTO v_activo FROM Usuario WHERE id_usuario = NEW.id_postulante;
    
    IF v_activo = FALSE THEN
        RAISE EXCEPTION 'Inscripción denegada: El postulante con ID % se encuentra inactivo en el sistema.', NEW.id_postulante;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_verificar_postulante_activo
BEFORE INSERT ON Inscripcion
FOR EACH ROW
EXECUTE FUNCTION fn_tr_verificar_postulante_activo();


-- ---------------------------------------------------------------------
-- ENUNCIADO 4: Sincronizar herencia en cascada lógica al borrar un Usuario.
-- Si un usuario es eliminado, el trigger debe asegurar que se eliminen o limpien
-- sus rastros en bitácoras previas asignadas a él si quedasen huérfanas (Buenas prácticas).
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_tr_limpiar_auditoria_usuario()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE Bitacora SET id_usuario = NULL WHERE id_usuario = OLD.id_usuario;
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_limpiar_auditoria_usuario
BEFORE DELETE ON Usuario
FOR EACH ROW
EXECUTE FUNCTION fn_tr_limpiar_auditoria_usuario();


-- ---------------------------------------------------------------------
-- ENUNCIADO 5: Controlar que las aulas no excedan su capacidad física.
-- Al intentar asignar o modificar una infraestructura en 'Materia_Grupo', 
-- el trigger verifica que la capacidad del aula sea mayor a cero (doble check de la restricción).
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_tr_validar_capacidad_aula()
RETURNS TRIGGER AS $$
DECLARE
    v_capacidad INT;
END;
$$; -- Declaración vacía corregida abajo en la lógica limpia:

CREATE OR REPLACE FUNCTION fn_tr_validar_capacidad_aula()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.capacidad <= 0 THEN
        RAISE EXCEPTION 'La capacidad de un aula nueva o editada debe ser estrictamente mayor a 0.';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_validar_capacidad_aula
BEFORE INSERT OR UPDATE ON Aula
FOR EACH ROW
EXECUTE FUNCTION fn_tr_validar_capacidad_aula();


-- ---------------------------------------------------------------------
-- ENUNCIADO 6: Recálculo Automatizado de la Evaluación Final.
-- Cada vez que se inserte una 'SubCalificacion' (nota de una materia), el trigger
-- debe recalcular la ponderación correspondiente (nota_materia_cruda * porcentaje / 100)
-- y sumarla de forma automática a la columna 'nota_final_calculada' de la tabla 'Evaluacion'.
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_tr_calcular_nota_final_automatica()
RETURNS TRIGGER AS $$
DECLARE
    v_porcentaje INT;
    v_puntos_ponderados NUMERIC(5,2);
BEGIN
    -- Obtener el porcentaje asignado a la materia en la regla
    SELECT porcentaje INTO v_porcentaje 
    FROM Regla_Evaluacion 
    WHERE id_regla_evaluacion = NEW.id_regla_evaluacion;

    -- Calcular los puntos reales sobre la nota base de 100
    v_puntos_ponderados := (NEW.nota_materia_cruda * v_porcentaje) / 100.00;

    -- Actualizar de manera incremental la cabecera de la evaluación
    UPDATE Evaluacion 
    SET nota_final_calculada = nota_final_calculada + v_puntos_ponderados
    WHERE id_evaluacion = NEW.id_evaluacion;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_calcular_nota_final_automatica
AFTER INSERT ON SubCalificacion
FOR EACH ROW
EXECUTE FUNCTION fn_tr_calcular_nota_final_automatica();


-- ---------------------------------------------------------------------
-- ENUNCIADO 7: Validación de Límites de Notas Crudas.
-- Impedir el almacenamiento de calificaciones menores a 0 o mayores a 100 
-- en la tabla 'SubCalificacion', lanzando una excepción personalizada.
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_tr_validar_limite_nota()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.nota_materia_cruda < 0 OR NEW.nota_materia_cruda > 100 THEN
        RAISE EXCEPTION 'Calificación Inválida: La nota cruda asignada (%) debe estar estrictamente en el rango de 0 a 100.', NEW.nota_materia_cruda;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_validar_limite_nota
BEFORE INSERT OR UPDATE ON SubCalificacion
FOR EACH ROW
EXECUTE FUNCTION fn_tr_validar_limite_nota();


-- ---------------------------------------------------------------------
-- ENUNCIADO 8: Control del Estado de Convocatoria para Inscripciones.
-- No permitir nuevas filas en la tabla 'Inscripcion' si la convocatoria asociada
-- ya se encuentra en estado 'CONCLUIDA'.
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_tr_validar_estado_convocatoria()
RETURNS TRIGGER AS $$
DECLARE
    v_estado VARCHAR(20);
BEGIN
    SELECT estado INTO v_estado FROM Convocatoria WHERE id_convocatoria = NEW.id_convocatoria;

    IF v_estado = 'CONCLUIDA' THEN
        RAISE EXCEPTION 'Proceso cerrado: No se admiten inscripciones en una convocatoria que ya ha CONCLUIDO.';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_validar_estado_convocatoria
BEFORE INSERT ON Inscripcion
FOR EACH ROW
EXECUTE FUNCTION fn_tr_validar_estado_convocatoria();



-- ---------------------------------------------------------------------
-- ENUNCIADO 9: Forzar mayúsculas en las siglas de los Grupos y Materias.
-- Para mantener la consistencia de los datos en la base de datos (Ej: G1, MAT101),
-- el trigger transforma automáticamente las siglas ingresadas a mayúsculas antes de guardar.
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_tr_transformar_siglas_mayusculas()
RETURNS TRIGGER AS $$
BEGIN
    NEW.sigla := UPPER(NEW.sigla);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_materia_sigla_mayuscula
BEFORE INSERT OR UPDATE ON Materia
FOR EACH ROW
EXECUTE FUNCTION fn_tr_transformar_siglas_mayusculas();

CREATE TRIGGER tr_grupo_sigla_mayuscula
BEFORE INSERT OR UPDATE ON Grupo
FOR EACH ROW
EXECUTE FUNCTION fn_tr_transformar_siglas_mayusculas();


-- ---------------------------------------------------------------------
-- ENUNCIADO 10: Auditoría de Modificaciones de Calificaciones (UPDATE).
-- Cuando un docente o coordinador modifique una nota en 'Evaluacion', el trigger
-- guardará automáticamente el estado anterior y el nuevo dentro de la bitácora.
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_tr_auditar_cambio_notas()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO Bitacora (tabla, operacion, datos_anteriores, datos_nuevos, id_usuario, ip_origen, user_agent)
    VALUES (
        'Evaluacion', 
        'UPDATE', 
        json_build_object('id_evaluacion', OLD.id_evaluacion, 'nota_anterior', OLD.nota_final_calculada)::jsonb, 
        json_build_object('id_evaluacion', NEW.id_evaluacion, 'nota_nueva', NEW.nota_final_calculada)::jsonb,
        NULL,
        '127.0.0.1', 
        'Trigger de Auditoría de Notas'
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_auditar_cambio_notas
AFTER UPDATE ON Evaluacion
FOR EACH ROW
EXECUTE FUNCTION fn_tr_auditar_cambio_notas();
