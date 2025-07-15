<?php

require_once __DIR__ . '/../models/EstudianteModelo.php';

class EstudiantesController extends EstudiantesModelo{

    public static function consultar_materias_estudiante_logueado_controlador($id_sede, $codigo_institucion, $documento_estudiante)
    {
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($codigo_institucion));
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($id_sede));
        $documento_estudiante = MainModel::limpiar_cadenas($documento_estudiante);

        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_estudiante);

        $conexion = MainModel::conectar();

        // 1. Obtener grado y grupo del estudiante
        $stmt = $conexion->prepare("SELECT id_grado, id_grupo FROM matriculas 
                                WHERE documento = :documento 
                                  AND codigo_institucion = :codigo 
                                  AND id_sede = :sede");
        $stmt->execute([
            ':documento' => $documento_estudiante,
            ':codigo' => $codigo_institucion,
            ':sede' => $id_sede
        ]);
        $matricula = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$matricula) {
            return MainModel::jsonResponse("simple", "Sin matrícula", "No se encontró matrícula activa para el estudiante.", "warning");
        }

        $id_grado = $matricula['id_grado'];
        $id_grupo = $matricula['id_grupo'];

        // 2. Obtener nombres de grado y grupo
        $nombre_grado = MainModel::ejecutar_consultas_simples("SELECT nombre_grado FROM grados WHERE id_grado = '$id_grado'")->fetchColumn() ?? '';
        $nombre_grupo = MainModel::ejecutar_consultas_simples("SELECT nombre_grupo FROM grupos WHERE id_grupo = '$id_grupo'")->fetchColumn() ?? '';

        // 3. Obtener materias asignadas al grado
        $stmt = $conexion->prepare("SELECT materias_json FROM materias_por_grado 
                                WHERE id_grado = :grado 
                                  AND codigo_institucion = :codigo 
                                  AND id_sede = :sede");
        $stmt->execute([
            ':grado' => $id_grado,
            ':codigo' => $codigo_institucion,
            ':sede' => $id_sede
        ]);
        $materiasData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$materiasData) {
            return MainModel::jsonResponse("simple", "Sin materias", "No hay materias registradas para este grado.", "info");
        }

        $materiasArray = json_decode($materiasData['materias_json'], true);

        // 4. Armar respuesta final
        $respuesta = [
            'documento_estudiante' => $documento_estudiante,
            'id_grado' => MainModel::encryption($id_grado),
            'nombre_grado' => $nombre_grado,
            'id_grupo' => MainModel::encryption($id_grupo),
            'nombre_grupo' => $nombre_grupo,
            'materias' => []
        ];

        foreach ($materiasArray as $id_materia) {
            // Obtener nombre de la materia
            $stmtMateria = $conexion->prepare("SELECT nombre_materia FROM materias WHERE id_materia = :id");
            $stmtMateria->execute([':id' => $id_materia]);
            $nombreMateria = $stmtMateria->fetchColumn();

            if (!$nombreMateria) continue;

            // Buscar docente asignado y su imagen
            $stmtAsignado = $conexion->prepare("SELECT 
                                                u.nombres AS nombre_docente,
                                                p.imagen_materia,
                                                p.documento_docente
                                            FROM materias_asigandas_profesores p
                                            INNER JOIN usuarios u ON u.documento = p.documento_docente
                                            WHERE p.id_materia = :materia
                                              AND p.id_grado = :grado
                                              AND p.id_grupo = :grupo
                                              AND p.codigo_institucion = :codigo
                                              AND p.id_sede = :sede
                                              AND p.estado = 1
                                            LIMIT 1");
            $stmtAsignado->execute([
                ':materia' => $id_materia,
                ':grado' => $id_grado,
                ':grupo' => $id_grupo,
                ':codigo' => $codigo_institucion,
                ':sede' => $id_sede
            ]);
            $asignado = $stmtAsignado->fetch(PDO::FETCH_ASSOC);

            $respuesta['materias'][] = [
                'id_materia' => MainModel::encryption($id_materia),
                'nombre_materia' => $nombreMateria,
                'imagen_materia' => $asignado['imagen_materia'] ?? 'materiadefault.png',
                'docente' => $asignado['nombre_docente'] ?? null,
                'documento_docente' => $asignado['documento_docente'] ?? null
            ];
        }

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }
    public static function extraer_informacion_materia_seleccionada_controlador($datos)
    {
        $id_grado = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grado']));
        $id_grupo = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grupo']));
        $id_materia = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_materia']));
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'id grado' => $id_grado,
            'id grupo' => $id_grupo,
            'id materia' => $id_materia,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        $conexion = MainModel::conectar();

        // Obtener JSON con las materias del grado
        $sql = "SELECT materias_json 
            FROM materias_por_grado 
            WHERE id_grado = :id_grado 
              AND codigo_institucion = :codigo_institucion 
              AND id_sede = :id_sede 
            LIMIT 1";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":id_grado", $id_grado);
        $stmt->bindParam(":codigo_institucion", $codigo_institucion);
        $stmt->bindParam(":id_sede", $id_sede);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            echo json_encode(["error" => "No se encontraron materias para este grado"], JSON_UNESCAPED_UNICODE);
            return;
        }

        $materias_id = json_decode($fila['materias_json'], true);
        if (!in_array($id_materia, $materias_id)) {
            echo json_encode(["error" => "La materia seleccionada no pertenece a este grado"], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Buscar nombre de la materia seleccionada
        $stmtMateria = $conexion->prepare("SELECT nombre_materia FROM materias WHERE id_materia = :id_materia LIMIT 1");
        $stmtMateria->bindParam(":id_materia", $id_materia);
        $stmtMateria->execute();
        $nombreMateria = $stmtMateria->fetchColumn();

        // Obtener nombre del grado
        $stmtGrado = $conexion->prepare("SELECT nombre_grado FROM grados WHERE id_grado = :id_grado LIMIT 1");
        $stmtGrado->bindParam(":id_grado", $id_grado);
        $stmtGrado->execute();
        $nombreGrado = $stmtGrado->fetchColumn();

        // Obtener nombre del grupo
        $stmtGrupo = $conexion->prepare("SELECT nombre_grupo FROM grupos WHERE id_grupo = :id_grupo LIMIT 1");
        $stmtGrupo->bindParam(":id_grupo", $id_grupo);
        $stmtGrupo->execute();
        $nombreGrupo = $stmtGrupo->fetchColumn();

        // Resultado final
        echo json_encode([
            "nombre_grado" => $nombreGrado,
            "nombre_grupo" => $nombreGrupo,
            "nombre_materia" => $nombreMateria
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function obtener_temas_educativos_controlador($datos)
    {
        // 1. Limpiar y desencriptar datos
        $id_materia = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_materia']));
        $id_grado = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grado']));
        $id_grupo = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grupo']));
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));

        // 2. Validar campos requeridos
        MainModel::validar_campos_obligatorios([
            'id materia' => $id_materia,
            'id grado' => $id_grado,
            'id grupo' => $id_grupo,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        

        // 4. Obtener temas registrados por ese docente
        $temas = MainModel::ejecutar_consultas_simples("SELECT id_tema, titulo_tema, descripcion, orden, estado, fecha_creacion 
        FROM temas_materia 
        WHERE id_materia = '$id_materia' 
        AND id_grado = '$id_grado' 
        AND id_grupo = '$id_grupo' 
        AND codigo_institucion = '$codigo_institucion' 
        AND id_sede = '$id_sede' 
        ORDER BY orden ASC");

        $temasArray = $temas->fetchAll(PDO::FETCH_ASSOC);

        // 5. Responder
        echo json_encode($temasArray, JSON_UNESCAPED_UNICODE);
    }

    public static function crear_temas_discucion_foro_educativo_controlador($datos)
    {
        // 1. Limpiar y desencriptar datos
        $id_materia = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_materia']));
        $id_grado = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grado']));
        $id_grupo = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grupo']));
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $titulo_discusion = MainModel::limpiar_cadenas($datos['titulo_discusion']);
        $descripcion = $datos['descripcion'];
        $creado_por = MainModel::limpiar_cadenas($datos['creado_por']);
        $id_contenido = MainModel::limpiar_cadenas($datos['id_contenido']);
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);

        // 2. Validar campos requeridos
        MainModel::validar_campos_obligatorios([
            'id materia' => $id_materia,
            'id grado' => $id_grado,
            'id grupo' => $id_grupo,
            'institución' => $codigo_institucion,
            'sede' => $id_sede,
            'titulo discusión' => $titulo_discusion,
            'descripción' => $descripcion,
            'creado por' => $creado_por,
            'id contenido' => $id_contenido,
            'id tema' => $id_tema
        ]);

        // 3. Validaciones de existencia
        $check = MainModel::ejecutar_consultas_simples("SELECT id_materia FROM materias WHERE id_materia = '$id_materia'");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Materia no encontrada", "La materia seleccionada no existe en el sistema.", "warning");
        }

        $check = MainModel::ejecutar_consultas_simples("SELECT id_grado FROM grados WHERE id_grado = '$id_grado'");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Grado no encontrado", "El grado seleccionado no existe en el sistema.", "warning");
        }

        $check = MainModel::ejecutar_consultas_simples("SELECT id_grado FROM materias_asigandas_profesores WHERE id_grado = '$id_grado'");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Asignación inválida", "El grado no está asignado a ningún profesor.", "warning");
        }

        // 4. Verificar si el usuario ya tiene una discusión en ese contexto
        $consultaExistente = MainModel::conectar()->prepare("
        SELECT id_discusion FROM discusiones 
        WHERE id_contenido = :id_contenido 
          AND id_tema = :id_tema 
          AND id_materia = :id_materia 
          AND id_grado = :id_grado 
          AND id_grupo = :id_grupo 
          AND codigo_institucion = :codigo_institucion 
          AND id_sede = :id_sede 
          AND creado_por = :creado_por
        LIMIT 1
        ");

        $consultaExistente->bindParam(":id_contenido", $id_contenido);
        $consultaExistente->bindParam(":id_tema", $id_tema);
        $consultaExistente->bindParam(":id_materia", $id_materia);
        $consultaExistente->bindParam(":id_grado", $id_grado);
        $consultaExistente->bindParam(":id_grupo", $id_grupo);
        $consultaExistente->bindParam(":codigo_institucion", $codigo_institucion);
        $consultaExistente->bindParam(":id_sede", $id_sede);
        $consultaExistente->bindParam(":creado_por", $creado_por);
        $consultaExistente->execute();

        if ($consultaExistente->rowCount() > 0) {
            // Ya existe, actualizar
            $datosExistente = $consultaExistente->fetch();
            $id_discusion_existente = $datosExistente['id_discusion'];

            $update = MainModel::conectar()->prepare("
            UPDATE discusiones SET 
                titulo_discusion = :titulo_discusion, 
                descripcion = :descripcion, 
                fecha_creacion = NOW()
            WHERE id_discusion = :id_discusion
        ");

            $update->bindParam(":titulo_discusion", $titulo_discusion);
            $update->bindParam(":descripcion", $descripcion);
            $update->bindParam(":id_discusion", $id_discusion_existente);

            if ($update->execute()) {
                MainModel::jsonResponse("recargar", "Discusión actualizada", "Tu participación en el foro ha sido actualizada correctamente.", "success", [
                    "id_discusion" => $id_discusion_existente
                ]);
            } else {
                MainModel::jsonResponse("simple", "Error", "No se pudo actualizar la discusión existente.", "error");
            }

            return;
        }

        // 5. No existe → insertar nueva discusión
        $sql = MainModel::conectar()->prepare("
        INSERT INTO discusiones 
        (id_contenido, id_tema, id_materia, id_grado, id_grupo, codigo_institucion, id_sede, titulo_discusion, descripcion, creado_por)
        VALUES (:id_contenido, :id_tema, :id_materia, :id_grado, :id_grupo, :codigo_institucion, :id_sede, :titulo_discusion, :descripcion, :creado_por)
        ");

        $sql->bindParam(":id_contenido", $id_contenido);
        $sql->bindParam(":id_tema", $id_tema);
        $sql->bindParam(":id_materia", $id_materia);
        $sql->bindParam(":id_grado", $id_grado);
        $sql->bindParam(":id_grupo", $id_grupo);
        $sql->bindParam(":codigo_institucion", $codigo_institucion);
        $sql->bindParam(":id_sede", $id_sede);
        $sql->bindParam(":titulo_discusion", $titulo_discusion);
        $sql->bindParam(":descripcion", $descripcion);
        $sql->bindParam(":creado_por", $creado_por);

        if ($sql->execute()) {
            $id_discusion_insertado = MainModel::conectar()->lastInsertId(); // ✅ Obtiene el ID insertado
            MainModel::jsonResponse("recargar", "Discusión creada", "El tema de discusión fue creado correctamente.", "success", [
                "id_discusion" => $id_discusion_insertado
            ]);
        } else {
            MainModel::jsonResponse("simple", "Error inesperado", "No se pudo registrar la discusión. Intenta de nuevo más tarde.", "error");
        }
    }

    public static function obtener_discusiones_foro_educativo_controlador($datos)
    {
        // 1. Limpiar y desencriptar datos
        $id_materia = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_materia']));
        $id_grado = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grado']));
        $id_grupo = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grupo']));
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $id_contenido = MainModel::limpiar_cadenas($datos['id_contenido']);
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);

        // 2. Validar campos requeridos
        MainModel::validar_campos_obligatorios([
            'id materia' => $id_materia,
            'id grado' => $id_grado,
            'id grupo' => $id_grupo,
            'institución' => $codigo_institucion,
            'sede' => $id_sede,
            'id contenido' => $id_contenido,
            'id tema' => $id_tema,
        ]);

        // 3. Consultar discusiones
        $consulta = MainModel::conectar()->prepare("
        SELECT 
            d.id_discusion, 
            d.id_contenido, 
            d.id_tema, 
            d.id_materia, 
            d.id_grado, 
            d.id_grupo, 
            d.codigo_institucion, 
            d.id_sede, 
            d.titulo_discusion, 
            d.descripcion, 
            d.creado_por, 
            d.fecha_creacion,
            u.nombres AS nombre_usuario,
            u.imagen AS imagen_usuario
        FROM discusiones d
        INNER JOIN usuarios u ON d.creado_por = u.documento
        WHERE 
            d.id_materia = :id_materia 
            AND d.id_grado = :id_grado 
            AND d.id_grupo = :id_grupo 
            AND d.id_tema = :id_tema 
            AND d.codigo_institucion = :codigo_institucion 
            AND d.id_sede = :id_sede 
            AND d.id_contenido = :id_contenido
        ORDER BY d.fecha_creacion DESC
     ");

        $consulta->bindParam(":id_materia", $id_materia);
        $consulta->bindParam(":id_grado", $id_grado);
        $consulta->bindParam(":id_grupo", $id_grupo);
        $consulta->bindParam(":id_tema", $id_tema);
        $consulta->bindParam(":codigo_institucion", $codigo_institucion);
        $consulta->bindParam(":id_sede", $id_sede);
        $consulta->bindParam(":id_contenido", $id_contenido);
        $consulta->execute();
        $discusiones = $consulta->fetchAll(PDO::FETCH_ASSOC);

        // 4. Por cada discusión, obtener los comentarios anidados
        foreach ($discusiones as &$discusion) {
            $id_discusion = $discusion['id_discusion'];

            $comentariosQuery = MainModel::conectar()->prepare("
            SELECT 
                c.id_comentario,
                c.id_discusion,
                c.creado_por,
                c.comentario,
                c.id_padre,
                c.fecha_creacion,
                u.nombres AS nombre_usuario,
                u.imagen AS imagen_usuario
            FROM comentarios_foro c
            INNER JOIN usuarios u ON u.documento = c.creado_por
            WHERE c.id_discusion = :id_discusion
            ORDER BY c.fecha_creacion ASC
        ");
            $comentariosQuery->bindParam(":id_discusion", $id_discusion);
            $comentariosQuery->execute();
            $comentarios = $comentariosQuery->fetchAll(PDO::FETCH_ASSOC);


            // Indexar comentarios por ID
            $indexComentarios = [];
            foreach ($comentarios as $comentario) {
                $comentario['respuestas'] = [];
                $indexComentarios[$comentario['id_comentario']] = $comentario;
            }

            // Construir árbol de comentarios
            $estructuraFinal = [];
            foreach ($indexComentarios as $id => &$comentario) {
                if ($comentario['id_padre'] !== null && isset($indexComentarios[$comentario['id_padre']])) {
                    $indexComentarios[$comentario['id_padre']]['respuestas'][] = &$comentario;
                } else {
                    $estructuraFinal[] = &$comentario;
                }
            }


            // Añadir comentarios a la discusión
            $discusion['comentarios'] = $estructuraFinal;
        }

        // 5. Devolver respuesta completa
        echo json_encode($discusiones, JSON_UNESCAPED_UNICODE);
    }

    public static function registrar_comentario_foro_controlador($datos)
    {
        // 1. Limpiar y desencriptar datos
        $id_discusion = MainModel::limpiar_cadenas($datos['id_discusion']);
        $creado_por = MainModel::limpiar_cadenas($datos['creado_por']);
        $comentario = trim($datos['comentario']);
        $id_padre = isset($datos['id_padre']) && $datos['id_padre'] !== 'null' && $datos['id_padre'] !== ''
            ? MainModel::limpiar_cadenas($datos['id_padre'])
            : null;

        // 2. Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'Discusión' => $id_discusion,
            'Usuario' => $creado_por,
            'Comentario' => $comentario,
        ]);

        // 3. Verificar que exista la discusión
        $checkDiscusion = MainModel::ejecutar_consultas_simples("SELECT id_discusion FROM discusiones WHERE id_discusion = '$id_discusion'");
        if ($checkDiscusion->rowCount() <= 0) {
            MainModel::jsonResponse("Toast", "Discusión no encontrada", "La discusión a la que intenta comentar no existe.", "warning");
        }

        // 4. Si hay id_padre, verificar que el comentario padre exista
        if ($id_padre !== null) {
            $checkPadre = MainModel::ejecutar_consultas_simples("SELECT id_comentario FROM comentarios_foro WHERE id_comentario = '$id_padre'");
            if ($checkPadre->rowCount() <= 0) {
                MainModel::jsonResponse("Toast", "Comentario padre no encontrado", "El comentario al que intenta responder no existe.", "warning");
            }
        }

        // 5. Insertar comentario
        $sql = MainModel::conectar()->prepare("
        INSERT INTO comentarios_foro (id_discusion, creado_por, comentario, id_padre)
        VALUES (:id_discusion, :creado_por, :comentario, :id_padre)
        ");
        $sql->bindParam(":id_discusion", $id_discusion);
        $sql->bindParam(":creado_por", $creado_por);
        $sql->bindParam(":comentario", $comentario);
        $sql->bindParam(":id_padre", $id_padre, PDO::PARAM_INT);

        if ($sql->execute()) {
            MainModel::jsonResponse("Toast", "Comentario registrado", "Tu comentario fue publicado correctamente.", "success");
        } else {
            MainModel::jsonResponse("Toast", "Error", "No se pudo registrar el comentario.", "error");
        }
    }

    public static function eliminar_discusion_controlador($datos)
    {
        $id_discusion = MainModel::limpiar_cadenas($datos['id_discusion']);

        // Validar existencia
        $verificar = MainModel::ejecutar_consultas_simples("SELECT id_discusion FROM discusiones WHERE id_discusion = '$id_discusion'");
        if ($verificar->rowCount() <= 0) {
            MainModel::jsonResponse("Toast", "No encontrada", "La discusión no existe", "warning");
        }

        // Eliminar (se eliminarán en cascada los comentarios por la FK)
        $eliminar = MainModel::conectar()->prepare("DELETE FROM discusiones WHERE id_discusion = :id_discusion");
        $eliminar->bindParam(":id_discusion", $id_discusion);

        if ($eliminar->execute()) {
            MainModel::jsonResponse("Toast", "Eliminada", "La discusión fue eliminada correctamente", "success");
        } else {
            MainModel::jsonResponse("Toast", "Error", "No se pudo eliminar la discusión", "error");
        }
    }

    public static function eliminar_comentario_foro_controlador($datos)
    {
        $id_comentario = MainModel::limpiar_cadenas($datos['id_comentario']);

        // Verificar que exista el comentario (corregido: se cerró bien la comilla)
        $checkComentario = MainModel::ejecutar_consultas_simples("SELECT id_comentario FROM comentarios_foro WHERE id_comentario = '$id_comentario'");
        if ($checkComentario->rowCount() <= 0) {
            MainModel::jsonResponse("Toast", "No encontrado", "El comentario no existe o ya fue eliminado.", "warning");
        }

        // Eliminar el comentario (si tiene hijos, se eliminarán por ON DELETE CASCADE)
        $eliminar = MainModel::conectar()->prepare("DELETE FROM comentarios_foro WHERE id_comentario = :id_comentario");
        $eliminar->bindParam(":id_comentario", $id_comentario);

        if ($eliminar->execute()) {
            MainModel::jsonResponse("Toast", "Comentario eliminado", "El comentario fue eliminado correctamente.", "success");
        } else {
            MainModel::jsonResponse("Toast", "Error", "No se pudo eliminar el comentario.", "error");
        }
    }

    public static function editar_discusion_controlador($datos)
    {
        // 1. Limpiar y desencriptar datos
        $id_discusion = MainModel::limpiar_cadenas($datos['id_discusion']);
        $titulo_discusion = MainModel::limpiar_cadenas($datos['titulo_discusion']);
        $descripcion = $datos['descripcion'];
        $creado_por = MainModel::limpiar_cadenas($datos['creado_por']);

        // 2. Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'ID discusión' => $id_discusion,
            'Título' => $titulo_discusion,
            'Descripción' => $descripcion,
            'Creado por' => $creado_por
        ]);

        // 3. Verificar si la discusión existe y fue creada por el mismo usuario
        $consulta = MainModel::conectar()->prepare("
        SELECT id_discusion FROM discusiones 
        WHERE id_discusion = :id_discusion AND creado_por = :creado_por
        LIMIT 1
        ");
        $consulta->bindParam(':id_discusion', $id_discusion);
        $consulta->bindParam(':creado_por', $creado_por);
        $consulta->execute();

        if ($consulta->rowCount() <= 0) {
            MainModel::jsonResponse("Toast", "Discusión no válida", "No tienes permiso para editar esta discusión o no existe.", "error");
            return;
        }

        // 4. Actualizar la discusión
        $actualizar = MainModel::conectar()->prepare("
        UPDATE discusiones 
        SET titulo_discusion = :titulo, descripcion = :descripcion, fecha_creacion = NOW()
        WHERE id_discusion = :id_discusion
        ");
        $actualizar->bindParam(':titulo', $titulo_discusion);
        $actualizar->bindParam(':descripcion', $descripcion);
        $actualizar->bindParam(':id_discusion', $id_discusion);

        if ($actualizar->execute()) {
            MainModel::jsonResponse("Toast", "¡Actualizado!", "La discusión fue actualizada correctamente.", "success");
        } else {
            MainModel::jsonResponse("Toast", "Error", "No se pudo actualizar la discusión. Intenta más tarde.", "error");
        }
    }


    public static function actualizar_comentario_foro_controlador($datos)
    {
        $id_comentario = MainModel::limpiar_cadenas($datos['id_comentario']);
        $texto_nuevo = trim($datos['texto']);
        $creado_por = MainModel::limpiar_cadenas($datos['creado_por']);

        // Validar campos obligatorios
        if (empty($id_comentario) || empty($texto_nuevo) || empty($creado_por)) {
            MainModel::jsonResponse("Toast", "Campos requeridos", "Todos los campos son obligatorios.", "warning");
        }

        // Verificar existencia del comentario y pertenencia
        $consulta = MainModel::conectar()->prepare("SELECT comentario, creado_por FROM comentarios_foro WHERE id_comentario = :id");
        $consulta->bindParam(":id", $id_comentario);
        $consulta->execute();

        if ($consulta->rowCount() === 0) {
            MainModel::jsonResponse("Toast", "No encontrado", "El comentario no existe.", "error");
        }

        $comentarioDB = $consulta->fetch();

        if ($comentarioDB['creado_por'] != $creado_por) {
            MainModel::jsonResponse("Toast", "No autorizado", "No puedes editar este comentario.", "warning");
        }

        // Verificar si el contenido es idéntico
        if (trim($comentarioDB['comentario']) === $texto_nuevo) {
            MainModel::jsonResponse("Toast", "Sin cambios", "No se detectaron cambios en el comentario.", "info");
        }

        // Actualizar el comentario
        $actualizar = MainModel::conectar()->prepare("UPDATE comentarios_foro SET comentario = :texto WHERE id_comentario = :id");
        $actualizar->bindParam(":texto", $texto_nuevo);
        $actualizar->bindParam(":id", $id_comentario);

        if ($actualizar->execute()) {
            MainModel::jsonResponse("Toast", "Actualizado", "El comentario se actualizó correctamente.", "success");
        } else {
            MainModel::jsonResponse("Toast", "Error", "No se pudo actualizar el comentario.", "error");
        }
    }

    public static function consultar_entrega_tarea_estudiante_controlador($datos)
    {
        // Desencriptar y limpiar
        $id_tarea = MainModel::limpiar_cadenas($datos['id_tarea'] ?? '');
        $documento_estudiante = MainModel::limpiar_cadenas($datos['documento_estudiante'] ?? '');

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'ID de tarea' => $id_tarea,
            'Documento del estudiante' => $documento_estudiante,
        ]);

        $conexion = MainModel::conectar();

        $sql = "SELECT 
                id_entrega,
                contenido_texto,
                archivo_adjunto,
                fecha_entrega,
                ultima_modificacion,
                calificado,
                calificacion,
                retroalimentacion,
                estado
            FROM entregas_tareas
            WHERE id_tarea = :id_tarea AND documento_estudiante = :documento_estudiante
            LIMIT 1";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":id_tarea", $id_tarea);
        $stmt->bindParam(":documento_estudiante", $documento_estudiante);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            echo json_encode([
                "entrega" => null,
                "mensaje" => "El estudiante aún no ha realizado la entrega de la tarea"
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode([
            "entrega" => $fila,
            "mensaje" => "El estudiante ya ha realizado la entrega de la tarea"
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function registrar_entrega_tarea_estudiante_controlador($datos)
    {
        $tipo_entrega = MainModel::limpiar_cadenas($datos['tipo_entrega']);
        $documento_estudiante = MainModel::limpiar_cadenas($datos['documento_estudiante']);
        $id_tarea = MainModel::limpiar_cadenas($datos['id_tarea']);

        // Validar obligatorios
        MainModel::validar_campos_obligatorios([
            'Tipo de entrega' => $tipo_entrega,
            'Documento del estudiante' => $documento_estudiante,
            'ID de tarea' => $id_tarea,
        ]);

        // Conexión
        $conexion = MainModel::conectar();


        // Verificar si ya existe una entrega registrada para ese estudiante y tarea
        $existeEntrega = $conexion->prepare("
            SELECT COUNT(*) FROM entregas_tareas
            WHERE id_tarea = :id_tarea AND documento_estudiante = :documento_estudiante
        ");
        $existeEntrega->execute([
            ':id_tarea' => $id_tarea,
            ':documento_estudiante' => $documento_estudiante
        ]);
        $yaExiste = $existeEntrega->fetchColumn();

        if ($yaExiste > 0) {
            MainModel::jsonResponse("simple", "Entrega ya registrada", "Ya tienes una entrega registrada para esta tarea.", "warning");
        }


        // Extraer tipo_archivo_entrega de la tarea
        $stmt = $conexion->prepare("SELECT tipo_archivo_entrega FROM tareas WHERE id_tarea = :id_tarea LIMIT 1");
        $stmt->bindParam(":id_tarea", $id_tarea);
        $stmt->execute();
        $tipo_archivo_db = $stmt->fetchColumn();

        if (!$tipo_archivo_db && $tipo_entrega === 'archivo') {
            MainModel::jsonResponse("simple", "Error", "Esta tarea no permite entrega por archivo.", "error");
        }
        

        // Entrega por texto
        if ($tipo_entrega === 'texto') {
            $contenido_texto = $datos['contenido_entrega'] ?? '';

            $insert = $conexion->prepare("
            INSERT INTO entregas_tareas (id_tarea, documento_estudiante, contenido_texto)
            VALUES (:id_tarea, :documento_estudiante, :contenido_texto)
        ");
            $insert->execute([
                ":id_tarea" => $id_tarea,
                ":documento_estudiante" => $documento_estudiante,
                ":contenido_texto" => $contenido_texto
            ]);

            MainModel::jsonResponse("simple", "Entrega registrada", "Tu entrega ha sido enviada exitosamente.", "success");
        }

        // Entrega por archivo
        if ($tipo_entrega === 'archivo') {
            $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
            $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
            $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);
            $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);

            MainModel::validar_campos_obligatorios([
                'ID sede' => $id_sede,
                'Código institución' => $codigo_institucion,
                'Docente' => $documento_profesor,
                'Tema' => $id_tema
            ]);

            $archivos = $_FILES['archivos'] ?? null;

            if (
                !isset($archivos['name']) ||
                !is_array($archivos['name']) ||
                count($archivos['name']) === 0
            ) {
                MainModel::jsonResponse("simple", "Archivo faltante", "No se encontró archivo para entregar.", "warning");
            }

            $extensiones_validas = explode(',', $tipo_archivo_db); // Ej: 'pdf,docx'
            $archivo_guardado = null;

            // Preparar carpeta
            $ruta_base = __DIR__ . '/../views/resources';
            $ruta_destino = "$ruta_base/$codigo_institucion/sedes/$id_sede/imagenes/temas_educativos/$documento_profesor/$id_tema/archivos_entregas_tareas";
            if (!file_exists($ruta_destino)) mkdir($ruta_destino, 0775, true);

            foreach ($archivos['tmp_name'] as $index => $temporal) {
                $nombre_original = $archivos['name'][$index];
                $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);

                if (!in_array(strtolower($extension), $extensiones_validas)) {
                    MainModel::jsonResponse("simple", "Archivo inválido", "El archivo '$nombre_original' no es del tipo permitido ($tipo_archivo_db).", "error");
                }

                $nombre_unico = uniqid('entrega_') . '.' . $extension;
                $ruta_final = "$ruta_destino/$nombre_unico";

                if (move_uploaded_file($temporal, $ruta_final)) {
                    $archivo_guardado = $nombre_unico ;
                }
            }

            // Guardar en DB
            $insert = $conexion->prepare("
            INSERT INTO entregas_tareas (id_tarea, documento_estudiante, archivo_adjunto)
            VALUES (:id_tarea, :documento_estudiante, :archivo_adjunto)
        ");
            $insert->execute([
                ":id_tarea" => $id_tarea,
                ":documento_estudiante" => $documento_estudiante,
                ":archivo_adjunto" => $archivo_guardado
            ]);

            MainModel::jsonResponse("simple", "Entrega subida", "Tu archivo fue entregado correctamente.", "success");
        }

        // Default por si algo sale mal
        MainModel::jsonResponse("simple", "Error inesperado", "No se pudo procesar la entrega.", "error");
    }

    public static function eliminar_entrega_tarea_estudiante_controlador($datos)
    {
        // Sanitizar y desencriptar datos
        $id_entrega = MainModel::limpiar_cadenas($datos['id_entrega']);
        $nombre_archivo = MainModel::limpiar_cadenas($datos['nombre_archivo']);
        $tipo_entrega = MainModel::limpiar_cadenas($datos['tipo_entrega']);
        $documento_estudiante = MainModel::limpiar_cadenas($datos['documento_estudiante']);
        $id_tarea = MainModel::limpiar_cadenas($datos['id_tarea']);
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);

        // Validación básica
        MainModel::validar_campos_obligatorios([
            'ID de entrega' => $id_entrega,
            'Nombre del archivo' => $nombre_archivo,
            'Tipo de entrega' => $tipo_entrega,
            'Documento del estudiante' => $documento_estudiante,
            'ID de tarea' => $id_tarea,
            'Código de institución' => $codigo_institucion,
            'ID sede' => $id_sede,
            'Documento del profesor' => $documento_profesor,
            'ID tema' => $id_tema
        ]);

        $conexion = MainModel::conectar();

        // 1. Verificar que la entrega existe y no ha sido calificada
        $sql = "SELECT calificado FROM entregas_tareas WHERE id_entrega = :id_entrega AND documento_estudiante = :documento_estudiante AND id_tarea = :id_tarea LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":id_entrega", $id_entrega);
        $stmt->bindParam(":documento_estudiante", $documento_estudiante);
        $stmt->bindParam(":id_tarea", $id_tarea);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            MainModel::jsonResponse("simple", "Entrega no encontrada", "No se encontró la entrega especificada.", "error");
        }

        if ($fila['calificado'] == 1) {
            MainModel::jsonResponse("simple", "Entrega calificada", "No puedes eliminar una entrega que ya ha sido calificada.", "warning");
        }

        // 2. Eliminar archivo físico si aplica
        if ($tipo_entrega === 'archivo' && !empty($nombre_archivo)) {
            $ruta_base = __DIR__ . '/../views/resources';
            $ruta_archivo = "$ruta_base/$codigo_institucion/sedes/$id_sede/imagenes/temas_educativos/$documento_profesor/$id_tema/archivos_entregas_tareas/$nombre_archivo";

            if (file_exists($ruta_archivo)) {
                unlink($ruta_archivo);
            }
        }

        // 3. Eliminar registro de la base de datos
        $sqlDelete = "DELETE FROM entregas_tareas WHERE id_entrega = :id_entrega AND documento_estudiante = :documento_estudiante AND id_tarea = :id_tarea";
        $stmtDelete = $conexion->prepare($sqlDelete);
        $stmtDelete->bindParam(":id_entrega", $id_entrega);
        $stmtDelete->bindParam(":documento_estudiante", $documento_estudiante);
        $stmtDelete->bindParam(":id_tarea", $id_tarea);
        $stmtDelete->execute();

        // 4. Confirmar
        MainModel::jsonResponse("simple", "Entrega eliminada", "La entrega fue eliminada correctamente.", "success");
    }

    public static function eliminar_texto_entrega_tarea_estudiante_controlador($datos)
    {
        $id_entrega = MainModel::limpiar_cadenas($datos['id_entrega']);
        $tipo_entrega = MainModel::limpiar_cadenas($datos['tipo_entrega']);
        $documento_estudiante = MainModel::limpiar_cadenas($datos['documento_estudiante']);
        $id_tarea = MainModel::limpiar_cadenas($datos['id_tarea']);

          // Conexión
        $conexion = MainModel::conectar();

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'ID entrega' => $id_entrega,
            'Tipo entrega' => $tipo_entrega,
            'Documento estudiante' => $documento_estudiante,
            'ID tarea' => $id_tarea
        ]);

           // 1. Verificar que la entrega existe y no ha sido calificada
        $sql = "SELECT calificado FROM entregas_tareas WHERE id_entrega = :id_entrega AND documento_estudiante = :documento_estudiante AND id_tarea = :id_tarea LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":id_entrega", $id_entrega);
        $stmt->bindParam(":documento_estudiante", $documento_estudiante);
        $stmt->bindParam(":id_tarea", $id_tarea);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            MainModel::jsonResponse("simple", "Entrega no encontrada", "No se encontró la entrega especificada.", "error");
        }

        if ($fila['calificado'] == 1) {
            MainModel::jsonResponse("simple", "Entrega calificada", "No puedes eliminar una entrega que ya ha sido calificada.", "warning");
        }

        // Solo permitimos eliminar texto en este controlador
        if ($tipo_entrega !== 'texto') {
            MainModel::jsonResponse("simple", "Operación inválida", "Solo se permite eliminar entregas de tipo texto.", "warning");
        }

      

        // Verificar si existe esa entrega y pertenece al estudiante
        $consulta = $conexion->prepare("
        SELECT id_entrega FROM entregas_tareas 
        WHERE id_entrega = :id_entrega 
        AND id_tarea = :id_tarea 
        AND documento_estudiante = :documento_estudiante 
        AND contenido_texto IS NOT NULL 
        LIMIT 1
    ");
        $consulta->execute([
            ":id_entrega" => $id_entrega,
            ":id_tarea" => $id_tarea,
            ":documento_estudiante" => $documento_estudiante
        ]);

        if ($consulta->rowCount() === 0) {
            MainModel::jsonResponse("simple", "No encontrado", "La entrega no existe o no es válida para eliminar.", "warning");
        }

        // Eliminar
        $eliminar = $conexion->prepare("DELETE FROM entregas_tareas WHERE id_entrega = :id_entrega LIMIT 1");
        $eliminar->bindParam(":id_entrega", $id_entrega);

        if ($eliminar->execute()) {
            MainModel::jsonResponse("recargar", "Eliminada", "La entrega ha sido eliminada correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo eliminar la entrega. Intenta nuevamente.", "error");
        }
    }



}