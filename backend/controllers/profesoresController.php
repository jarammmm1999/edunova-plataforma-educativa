<?php

require_once __DIR__ . '/../models/profesoresModelo.php';

class ProfesoresController extends profesoresModelo
{

    public static function extraer_profesores_controlador($id_sede, $codigo_institucion)
    {
        $codigo_institucion = MainModel::limpiar_cadenas($codigo_institucion);
        $id_sede = MainModel::limpiar_cadenas($id_sede);
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);


        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, null);
        // ✅ Consulta corregida: solo una cláusula WHERE con todas las condiciones
        $consulta = "SELECT documento, nombres FROM usuarios 
                 WHERE id_rol = '3'
                 AND estado = '1' 
                 AND codigo_institucion = '$codigo_institucion' 
                 AND id_sede = '$id_sede'";

        $resultado = MainModel::ejecutar_consultas_simples($consulta);

        $profesores = [];

        while ($fila = $resultado->fetch(PDO::FETCH_ASSOC)) {
            // Encriptar el documento por seguridad
            $fila['documento'] = MainModel::encryption($fila['documento']);
            $profesores[] = $fila;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($profesores, JSON_UNESCAPED_UNICODE);
    }

    public static function guardar_asignaciones_docente_controlador($datos)
    {
        if (!isset($datos['asignaciones'])) {
            MainModel::jsonResponse("simple", "Datos inválidos", "No se encontraron datos de asignaciones.", "error");
            return;
        }

        $asignaciones = json_decode($datos['asignaciones'], true);

        if (!is_array($asignaciones) || empty($asignaciones)) {
            MainModel::jsonResponse("simple", "Formato incorrecto", "Las asignaciones no tienen un formato válido.", "error");
            return;
        }

        $conexion = MainModel::conectar();
        $errores = [];
        $registrosInsertados = 0;

        foreach ($asignaciones as $asignacion) {
            $documento_docente     = MainModel::limpiar_cadenas($asignacion['profesor']['documento'] ?? '');
            $documento_docente     = MainModel::decryption($documento_docente);
            $codigo_institucion    = MainModel::limpiar_cadenas($asignacion['codigo_institucion'] ?? '');
            $id_sede               = MainModel::limpiar_cadenas($asignacion['id_sede'] ?? '');

            $ruta_base = __DIR__ . '/../views/resources';
            $ruta_institucion = "$ruta_base/$codigo_institucion";
            $ruta_sede = "$ruta_institucion/sedes/$id_sede";
            $ruta_temas = "$ruta_sede/imagenes/imagenes_materias/$documento_docente";

            if (!file_exists($ruta_temas)) {
                mkdir($ruta_temas, 0775, true);
            }

            // Ruta origen y destino del archivo
            $imagen_origen = __DIR__ . '/../views/assets/image/materiadefault.png'; // ruta real donde está el archivo original
            $imagen_destino = $ruta_temas . '/materiadefault.png';


            // Copiar la imagen solo si no existe ya en destino
            if (!file_exists($imagen_destino)) {
                if (copy($imagen_origen, $imagen_destino)) {
                }
            }


            if (empty($documento_docente) || empty($asignacion['materias'])) {
                continue;
            }

            foreach ($asignacion['materias'] as $materia) {
                $id_materia = MainModel::limpiar_cadenas($materia['id_materia'] ?? '');
                $id_materia = MainModel::decryption($id_materia);

                if (empty($id_materia) || empty($materia['grados'])) {
                    continue;
                }

                foreach ($materia['grados'] as $grado) {
                    $id_grado = MainModel::limpiar_cadenas($grado['id_grado'] ?? '');
                    $id_grado = MainModel::decryption($id_grado);

                    if (empty($id_grado) || empty($grado['grupos'])) {
                        continue;
                    }

                    foreach ($grado['grupos'] as $grupo) {
                        $id_grupo = MainModel::limpiar_cadenas($grupo['id_grupo'] ?? null);
                        if (empty($id_grupo)) continue;

                        // 🔎 Verificar si este docente ya tiene la misma asignación
                        $verificarDocente = $conexion->prepare("SELECT COUNT(*) FROM materias_asigandas_profesores 
                        WHERE documento_docente = :docente AND id_materia = :materia 
                        AND id_grado = :grado AND id_grupo = :grupo 
                        AND codigo_institucion = :institucion AND id_sede = :sede");
                        $verificarDocente->bindParam(":docente", $documento_docente);
                        $verificarDocente->bindParam(":materia", $id_materia);
                        $verificarDocente->bindParam(":grado", $id_grado);
                        $verificarDocente->bindParam(":grupo", $id_grupo);
                        $verificarDocente->bindParam(":institucion", $codigo_institucion);
                        $verificarDocente->bindParam(":sede", $id_sede);
                        $verificarDocente->execute();

                        if ($verificarDocente->fetchColumn() > 0) {
                            $errores[] = "⚠️ El docente ya tiene asignada esta materia en ese grado y grupo.";
                            continue;
                        }

                        // 🔎 Verificar si otro docente ya tiene esta asignación
                        $verificarOtro = $conexion->prepare("
                        SELECT 
                            u.nombres AS nombre_docente,
                            m.nombre_materia,
                            g.nombre_grado,
                            gr.nombre_grupo
                        FROM materias_asigandas_profesores ad
                        INNER JOIN usuarios u ON u.documento = ad.documento_docente
                        INNER JOIN materias m ON m.id_materia = ad.id_materia
                        INNER JOIN grados g ON g.id_grado = ad.id_grado
                        INNER JOIN grupos gr ON gr.id_grupo = ad.id_grupo
                        WHERE ad.id_materia = :materia 
                          AND ad.id_grado = :grado 
                          AND ad.id_grupo = :grupo 
                          AND ad.codigo_institucion = :institucion 
                          AND ad.id_sede = :sede 
                          AND ad.documento_docente != :docente
                    ");
                        $verificarOtro->bindParam(":materia", $id_materia);
                        $verificarOtro->bindParam(":grado", $id_grado);
                        $verificarOtro->bindParam(":grupo", $id_grupo);
                        $verificarOtro->bindParam(":institucion", $codigo_institucion);
                        $verificarOtro->bindParam(":sede", $id_sede);
                        $verificarOtro->bindParam(":docente", $documento_docente);
                        $verificarOtro->execute();

                        if ($resultado = $verificarOtro->fetch(PDO::FETCH_ASSOC)) {
                            $errores[] = "⚠️ El grupo <b>{$resultado['nombre_grupo']}</b> del grado <b>{$resultado['nombre_grado']}</b> ya fue asignado con la materia <b>{$resultado['nombre_materia']}</b> al docente <b>{$resultado['nombre_docente']}</b>.";
                            continue;
                        }

                        // ✅ Insertar asignación
                        $insertar = $conexion->prepare("INSERT INTO materias_asigandas_profesores 
                        (documento_docente, id_materia, id_grado, id_grupo, codigo_institucion, id_sede)
                        VALUES (:docente, :materia, :grado, :grupo, :institucion, :sede)");
                        $insertar->bindParam(":docente", $documento_docente);
                        $insertar->bindParam(":materia", $id_materia);
                        $insertar->bindParam(":grado", $id_grado);
                        $insertar->bindParam(":grupo", $id_grupo);
                        $insertar->bindParam(":institucion", $codigo_institucion);
                        $insertar->bindParam(":sede", $id_sede);
                        $insertar->execute();

                        $registrosInsertados++;
                    }
                }
            }
        }

        // 🎯 Respuesta final
        if (!empty($errores)) {
            $mensajeError = implode("<br>", $errores);
            MainModel::jsonResponse(
                "simple",
                "Asignaciones parcialmente guardadas",
                "Se registraron $registrosInsertados asignaciones. Algunos conflictos:<br>$mensajeError",
                "warning"
            );
        } else {
            MainModel::jsonResponse("simple", "¡Asignaciones exitosas!", "Se registraron $registrosInsertados asignaciones correctamente.", "success");
        }
    }


    public static function consultar_asignaciones_docentes_controlador($id_sede, $codigo_institucion)
    {
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($codigo_institucion));
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($id_sede));

        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, null);

        $conexion = MainModel::conectar();

        $sql = "SELECT 
                ad.documento_docente,
                u.nombres,
                ad.id_materia,
                m.nombre_materia,
                ad.id_grado,
                g.nombre_grado,
                ad.id_grupo,
                gr.nombre_grupo
            FROM materias_asigandas_profesores ad
            INNER JOIN usuarios u ON u.documento = ad.documento_docente
            INNER JOIN materias m ON m.id_materia = ad.id_materia
            INNER JOIN grados g ON g.id_grado = ad.id_grado
            INNER JOIN grupos gr ON gr.id_grupo = ad.id_grupo
            WHERE ad.estado = 1
              AND ad.codigo_institucion = :codigo_institucion
              AND ad.id_sede = :id_sede
            ORDER BY ad.documento_docente, ad.id_materia, ad.id_grado, ad.id_grupo";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":codigo_institucion", $codigo_institucion);
        $stmt->bindParam(":id_sede", $id_sede);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar por docente
        $respuesta = [];

        foreach ($datos as $row) {
            $doc = $row['documento_docente'];
            if (!isset($respuesta[$doc])) {
                $respuesta[$doc] = [
                    'documento_docente' => $doc,
                    'nombres' => $row['nombres'],
                    'materias' => []
                ];
            }

            // Buscar o agregar materia
            $materiaIndex = array_search($row['id_materia'], array_column($respuesta[$doc]['materias'], 'id_materia'));
            if ($materiaIndex === false) {
                $respuesta[$doc]['materias'][] = [
                    'id_materia' => $row['id_materia'],
                    'nombre_materia' => $row['nombre_materia'],
                    'grados' => []
                ];
                $materiaIndex = count($respuesta[$doc]['materias']) - 1;
            }

            // Buscar o agregar grado
            $gradoIndex = array_search($row['id_grado'], array_column($respuesta[$doc]['materias'][$materiaIndex]['grados'], 'id_grado'));
            if ($gradoIndex === false) {
                $respuesta[$doc]['materias'][$materiaIndex]['grados'][] = [
                    'id_grado' => $row['id_grado'],
                    'nombre_grado' => $row['nombre_grado'],
                    'grupos' => []
                ];
                $gradoIndex = count($respuesta[$doc]['materias'][$materiaIndex]['grados']) - 1;
            }

            // Agregar grupo
            $respuesta[$doc]['materias'][$materiaIndex]['grados'][$gradoIndex]['grupos'][] = [
                'id_grupo' => $row['id_grupo'],
                'nombre_grupo' => $row['nombre_grupo']
            ];
        }

        echo json_encode(array_values($respuesta), JSON_UNESCAPED_UNICODE);
    }

    public static function eliminargradosAsignadosProfesores_controlador($datos)
    {

        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $documento_docente = MainModel::limpiar_cadenas($datos['documento_docente']);
        $id_grado = MainModel::limpiar_cadenas($datos['id_grado']);

        MainModel::validar_campos_obligatorios([
            'documento docente' => $documento_docente,
            'id grado' => $id_grado,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);


        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_docente);

        $datos_eliminar = [
            'documento_docente' => $documento_docente,
            'id_grado' => $id_grado,
            'codigo_institucion' => $codigo_institucion,
            'id_sede' => $id_sede
        ];

        $eliminar_grado = profesoresModelo::eliminar_grados_asignados_profesores($datos_eliminar);

        if ($eliminar_grado) {
            MainModel::jsonResponse("simple", "Grado eliminado", "Las asignaciones del grado fueron eliminada correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo eliminar las asignaciones del grado", "error");
        }
    }


    public static function eliminargruposAsignadosProfesores_controlador($datos)
    {

        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $documento_docente = MainModel::limpiar_cadenas($datos['documento_docente']);
        $id_grupo = MainModel::limpiar_cadenas($datos['id_grupo']);

        MainModel::validar_campos_obligatorios([
            'documento docente' => $documento_docente,
            'id grupo' => $id_grupo,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);


        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_docente);

        $datos_eliminar = [
            'documento_docente' => $documento_docente,
            'id_grupo' => $id_grupo,
            'codigo_institucion' => $codigo_institucion,
            'id_sede' => $id_sede
        ];

        $eliminar_grado = profesoresModelo::eliminar_grupos_asignados_profesores($datos_eliminar);

        if ($eliminar_grado) {
            MainModel::jsonResponse("simple", "Grupo eliminado", "Las asignaciones del grupo fueron eliminada correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo eliminar las asignaciones del grupo", "error");
        }
    }

    public static function eliminarMateriasAsignadosProfesores_controlador($datos)
    {

        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $documento_docente = MainModel::limpiar_cadenas($datos['documento_docente']);
        $id_materia = MainModel::limpiar_cadenas($datos['id_materia']);

        MainModel::validar_campos_obligatorios([
            'documento docente' => $documento_docente,
            'id materia' => $id_materia,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);


        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_docente);

        $datos_eliminar = [
            'documento_docente' => $documento_docente,
            'id_materia' => $id_materia,
            'codigo_institucion' => $codigo_institucion,
            'id_sede' => $id_sede
        ];

        $eliminar_grado = profesoresModelo::eliminar_materia_asignados_profesores($datos_eliminar);

        if ($eliminar_grado) {
            MainModel::jsonResponse("simple", "Materia eliminada", "Las asignaciones de la materia fue eliminada correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo eliminar las asignaciones de la materia", "error");
        }
    }


    public static function consultar_asignaciones_docentes_logueado_controlador($id_sede, $codigo_institucion, $documento_docente)
    {
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($codigo_institucion));
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($id_sede));
        $documento_docente = MainModel::limpiar_cadenas($documento_docente);

        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_docente);

        $conexion = MainModel::conectar();

        $sql = "SELECT 
                ad.documento_docente,
                u.nombres,
                ad.id_materia,
                ad.imagen_materia,
                m.nombre_materia,
                ad.id_grado,
                g.nombre_grado,
                ad.id_grupo,
                gr.nombre_grupo
            FROM materias_asigandas_profesores ad
            INNER JOIN usuarios u ON u.documento = ad.documento_docente
            INNER JOIN materias m ON m.id_materia = ad.id_materia
            INNER JOIN grados g ON g.id_grado = ad.id_grado
            INNER JOIN grupos gr ON gr.id_grupo = ad.id_grupo
            WHERE ad.estado = 1
              AND ad.codigo_institucion = :codigo_institucion
              AND ad.id_sede = :id_sede
              AND ad.documento_docente = :documento_docente
            ORDER BY ad.id_materia, ad.id_grado, ad.id_grupo";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":codigo_institucion", $codigo_institucion);
        $stmt->bindParam(":id_sede", $id_sede);
        $stmt->bindParam(":documento_docente", $documento_docente);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Estructura única del docente
        $respuesta = [
            'documento_docente' => $documento_docente,
            'nombres' => '',
            'materias' => []
        ];

        foreach ($datos as $row) {
            if (!$respuesta['nombres']) {
                $respuesta['nombres'] = $row['nombres'];
            }

            // Buscar o agregar materia
            $materiaIndex = array_search($row['id_materia'], array_column($respuesta['materias'], 'id_materia'));
            if ($materiaIndex === false) {
                $respuesta['materias'][] = [
                    'id_materia' => MainModel::encryption($row['id_materia']),
                    'nombre_materia' => $row['nombre_materia'],
                    'imagen_materia' => $row['imagen_materia'],
                    'grados' => []
                ];
                $materiaIndex = count($respuesta['materias']) - 1;
            }

            // Buscar o agregar grado
            $gradoIndex = array_search($row['id_grado'], array_column($respuesta['materias'][$materiaIndex]['grados'], 'id_grado'));
            if ($gradoIndex === false) {
                $respuesta['materias'][$materiaIndex]['grados'][] = [
                    'id_grado' => MainModel::encryption($row['id_grado']),
                    'nombre_grado' => $row['nombre_grado'],
                    'grupos' => []
                ];
                $gradoIndex = count($respuesta['materias'][$materiaIndex]['grados']) - 1;
            }

            // Agregar grupo
            $respuesta['materias'][$materiaIndex]['grados'][$gradoIndex]['grupos'][] = [
                'id_grupo' => MainModel::encryption($row['id_grupo']),
                'nombre_grupo' => $row['nombre_grupo']
            ];
        }

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }

    public static function extraer_informacion_materia_seleccionada_controlador($datos)
    {
        $id_materia = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_materia']));
        $id_grado = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grado']));
        $id_grupo = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grupo']));
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $documento_docente = MainModel::limpiar_cadenas($datos['documento_profesor']);

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'documento docente' => $documento_docente,
            'id materia' => $id_materia,
            'id grado' => $id_grado,
            'id grupo' => $id_grupo,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        // Validar existencia del docente en esa institución y sede
        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_docente);

        $conexion = MainModel::conectar();

        $sql = "SELECT 
                m.nombre_materia,
                g.nombre_grado,
                ad.imagen_materia,
                gr.nombre_grupo
            FROM materias_asigandas_profesores ad
            INNER JOIN materias m ON m.id_materia = ad.id_materia
            INNER JOIN grados g ON g.id_grado = ad.id_grado
            INNER JOIN grupos gr ON gr.id_grupo = ad.id_grupo
            WHERE ad.estado = 1
              AND ad.codigo_institucion = :codigo_institucion
              AND ad.id_sede = :id_sede
              AND ad.documento_docente = :documento_docente
              AND ad.id_materia = :id_materia
              AND ad.id_grado = :id_grado
              AND ad.id_grupo = :id_grupo
            LIMIT 1";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":codigo_institucion", $codigo_institucion);
        $stmt->bindParam(":id_sede", $id_sede);
        $stmt->bindParam(":documento_docente", $documento_docente);
        $stmt->bindParam(":id_materia", $id_materia);
        $stmt->bindParam(":id_grado", $id_grado);
        $stmt->bindParam(":id_grupo", $id_grupo);

        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    public static function crear_temas_educativos_controlador($datos)
    {
        $id_materia = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_materia']));
        $id_grado = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grado']));
        $id_grupo = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grupo']));
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);
        $titulo_tema = MainModel::limpiar_cadenas($datos['titulo_tema']);
        $descripcion_tema = $datos['descripcion_tema'];

        MainModel::validar_campos_obligatorios([
            'documento docente' => $documento_profesor,
            'titulo tema' => $titulo_tema,
            'descripcion tema' => $descripcion_tema,
            'id materia' => $id_materia,
            'id grado' => $id_grado,
            'id grupo' => $id_grupo,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        // Validar existencia del docente en esa institución y sede
        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_profesor);

        $check = MainModel::ejecutar_consultas_simples("SELECT id_materia  FROM materias WHERE id_materia  = '$id_materia '");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Matérnia no encontrada", "La materia seleccionada no existe en el sistema.", "warning");
        }

        $check = MainModel::ejecutar_consultas_simples("SELECT id_grado  FROM grados WHERE id_grado  = '$id_grado '");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "grado no encontrado", "El grado seleccionado no existe en el sistema.", "warning");
        }

        $check = MainModel::ejecutar_consultas_simples("SELECT id_grado FROM materias_asigandas_profesores WHERE id_grado  = '$id_grado '");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "grado no encontrado", "El grado seleccionado no existe en el sistema.", "warning");
        }

        $check = MainModel::ejecutar_consultas_simples("SELECT id  FROM materias_asigandas_profesores 
        WHERE id_materia = '$id_materia' 
        AND id_grado = '$id_grado' 
        AND id_grupo = '$id_grupo' 
        AND documento_docente = '$documento_profesor'
        AND codigo_institucion = '$codigo_institucion'
        AND id_sede = '$id_sede'
        ");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No autorizado", "No tienes asignada esta materia con ese grado y grupo.", "warning");
        }

        // Validar si ya existe un tema con ese mismo título
        $checkTitulo = MainModel::ejecutar_consultas_simples("SELECT id_tema FROM temas_materia 
        WHERE id_materia = '$id_materia' 
        AND id_grado = '$id_grado' 
        AND id_grupo = '$id_grupo' 
        AND documento_docente = '$documento_profesor'
        AND codigo_institucion = '$codigo_institucion'
        AND id_sede = '$id_sede'
        AND titulo_tema = '$titulo_tema'
        ");
        if ($checkTitulo->rowCount() > 0) {
            MainModel::jsonResponse("simple", "Duplicado", "Ya existe un tema con ese mismo título registrado por ti.", "warning");
        }

        // Obtener el orden más alto registrado para este profesor en esa materia/grado/grupo
        $orden = 1; // por defecto
        $consultaOrden = MainModel::ejecutar_consultas_simples("SELECT MAX(orden) as max_orden FROM temas_materia 
        WHERE id_materia = '$id_materia' 
        AND id_grado = '$id_grado' 
        AND id_grupo = '$id_grupo' 
        AND documento_docente = '$documento_profesor'
        AND codigo_institucion = '$codigo_institucion'
        AND id_sede = '$id_sede'
        ");
        if ($consultaOrden->rowCount() > 0) {
            $max = $consultaOrden->fetch();
            $orden = ((int)$max['max_orden']) + 1;
        }

        $data = [
            'id_materia' => $id_materia,
            'documento_docente' => $documento_profesor,
            'id_grado' => $id_grado,
            'id_grupo' => $id_grupo,
            'titulo_tema' => $titulo_tema,
            'descripcion' => $descripcion_tema,
            'orden' => $orden,
            'codigo_institucion' => $codigo_institucion,
            'id_sede' => $id_sede
        ];


        $agregar_tema = profesoresModelo::registrar_temas_academicos_modelos($data);

        if ($agregar_tema) {
            $ruta_base = __DIR__ . '/../views/resources';
            $ruta_institucion = "$ruta_base/$codigo_institucion";
            $ruta_sede = "$ruta_institucion/sedes/$id_sede";
            $ruta_temas = "$ruta_sede/imagenes/temas_educativos/$documento_profesor";
            // Crear la carpeta si no existe
            if (!file_exists($ruta_temas)) {
                mkdir($ruta_temas, 0775, true);
            }
               $id_tema_insertado = MainModel::conectar()->lastInsertId(); 

                MainModel::jsonResponse("simple", "Tema registrados con éxito.", "Tema registrado con éxito.", "success", [
                    "id_tema_insertado" => $id_tema_insertado
                ]);

        } else {
            MainModel::jsonResponse("simple", "Error", "Error al registrar el tema.", "error");
        }
    }

    public static function Editar_imagenenes_portadas_materias_controlador($datos)
    {
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $id_grado = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grado']));
        $id_grupo = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grupo']));
        $id_materia = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_materia']));
        $documento_docente   = MainModel::limpiar_cadenas($datos['documento'] ?? '');

        MainModel::validar_campos_obligatorios([
            'documento' => $documento_docente,
            'institución' => $codigo_institucion,
            'sede' => $id_sede,
            "id grado" => $id_grado,
            "id grupo" => $id_grupo,
            "id materia" => $id_materia,

        ]);


        $conexion = MainModel::conectar();
        $sql_select = "SELECT imagen_materia  FROM materias_asigandas_profesores WHERE documento_docente = :documento  
        AND id_grado = :id_grado 
        AND id_grupo = :id_grupo 
        AND id_materia = :id_materia LIMIT 1";
        $stmt_select = $conexion->prepare($sql_select);
        $stmt_select->bindParam(':documento', $documento_docente, PDO::PARAM_STR);
        $stmt_select->bindParam(':id_grado', $id_grado, PDO::PARAM_INT);
        $stmt_select->bindParam(':id_grupo', $id_grupo, PDO::PARAM_INT);
        $stmt_select->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
        $stmt_select->execute();
        $resultado = $stmt_select->fetch(PDO::FETCH_ASSOC);

        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_docente);

        $check = MainModel::ejecutar_consultas_simples("SELECT id_grado FROM grados WHERE id_grado = '$id_grado'");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Grado inválido", "El grado seleccionado no es válido o no existe en el sistema.", "warning");
        }

        $check = MainModel::ejecutar_consultas_simples("SELECT id_grupo FROM grupos WHERE id_grupo = '$id_grupo'");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Grupo inválido", "El grupo seleccionado no es válido o no existe en el sistema.", "warning");
        }

        $check = MainModel::ejecutar_consultas_simples("SELECT id_materia FROM materias WHERE id_materia = '$id_materia'");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Grupo inválido", "El grupo seleccionado no es válido o no existe en el sistema.", "warning");
        }

         $imagen_actual = $resultado['imagen_materia'] ?? 'materiadefault.png';

         // 2. Validar si se está subiendo una nueva imagen
        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            return MainModel::jsonResponse("simple", "Error", "No se recibió una imagen válida.", "error");
        }

        $nombre_archivo = $_FILES['imagen']['name'];
        $tipo_archivo   = $_FILES['imagen']['type'];
        $temporal       = $_FILES['imagen']['tmp_name'];

        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($tipo_archivo, $tipos_permitidos)) {
            return MainModel::jsonResponse("simple", "Tipo de imagen inválido", "Solo se permiten imágenes en formato JPEG, PNG o WebP.", "warning");
        }


        $ruta_base = __DIR__ . '/../views/resources';
        $ruta_institucion = "$ruta_base/$codigo_institucion";
        $ruta_sede = "$ruta_institucion/sedes/$id_sede";
        $ruta_imagen_materia = "$ruta_sede/imagenes/imagenes_materias/$documento_docente";

        if (!file_exists($ruta_imagen_materia)) {
            mkdir($ruta_imagen_materia, 0775, true);
        }

          // 4. Generar nuevo nombre único para la imagen
        $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
        $nuevo_nombre = 'imagen_materia_' . uniqid() . '.' . $extension;
        $ruta_destino = $ruta_imagen_materia . '/' . $nuevo_nombre;

        if ($imagen_actual !== 'materiadefault.png') {
            $ruta_actual = $ruta_imagen_materia . '/' . $imagen_actual;
            if (file_exists($ruta_actual)) {
                unlink($ruta_actual); // Elimina la imagen anterior
            }
        }

        // 6. Subir la nueva imagen
        if (!move_uploaded_file($temporal, $ruta_destino)) {
            return MainModel::jsonResponse("simple", "Error", "No se pudo guardar la nueva imagen.", "error");
        }

        // 7. Actualizar la base de datos en la tabla correcta
     
        $sql_update = "UPDATE materias_asigandas_profesores 
               SET imagen_materia = :imagen 
               WHERE documento_docente = :documento 
               AND id_grado = :id_grado 
               AND id_grupo = :id_grupo 
               AND id_materia = :id_materia";

        $stmt_update = $conexion->prepare($sql_update);
        $stmt_update->bindParam(':imagen', $nuevo_nombre, PDO::PARAM_STR);
        $stmt_update->bindParam(':documento', $documento_docente, PDO::PARAM_STR);
        $stmt_update->bindParam(':id_grado', $id_grado, PDO::PARAM_INT);
        $stmt_update->bindParam(':id_grupo', $id_grupo, PDO::PARAM_INT);
        $stmt_update->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);

        if ($stmt_update->execute()) {
            return MainModel::jsonResponse("simple", "Éxito", "La imagen de la materia se actualizó correctamente.", "success");
        } else {
            return MainModel::jsonResponse("simple", "Error", "No se pudo actualizar la imagen en la base de datos.", "error");
        }
    }

    public static function obtener_temas_educativos_controlador($datos)
    {
        // 1. Limpiar y desencriptar datos
        $id_materia = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_materia']));
        $id_grado = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grado']));
        $id_grupo = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grupo']));
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);

        // 2. Validar campos requeridos
        MainModel::validar_campos_obligatorios([
            'documento docente' => $documento_profesor,
            'id materia' => $id_materia,
            'id grado' => $id_grado,
            'id grupo' => $id_grupo,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        // 3. Validar que el docente tenga asignada esa materia con ese grupo/grado
        $check = MainModel::ejecutar_consultas_simples("SELECT id FROM materias_asigandas_profesores 
        WHERE id_materia = '$id_materia' 
        AND id_grado = '$id_grado' 
        AND id_grupo = '$id_grupo' 
        AND documento_docente = '$documento_profesor' 
        AND codigo_institucion = '$codigo_institucion' 
        AND id_sede = '$id_sede' 
        AND estado = 1");

        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No autorizado", "No tienes asignada esta materia con ese grado y grupo.", "warning");
        }

        // 4. Obtener temas registrados por ese docente
        $temas = MainModel::ejecutar_consultas_simples("SELECT id_tema, titulo_tema, descripcion, orden, estado, fecha_creacion 
        FROM temas_materia 
        WHERE id_materia = '$id_materia' 
        AND id_grado = '$id_grado' 
        AND id_grupo = '$id_grupo' 
        AND documento_docente = '$documento_profesor' 
        AND codigo_institucion = '$codigo_institucion' 
        AND id_sede = '$id_sede' 
        ORDER BY orden ASC");

        $temasArray = $temas->fetchAll(PDO::FETCH_ASSOC);

        // 5. Responder
        echo json_encode($temasArray, JSON_UNESCAPED_UNICODE);
    }


    public static function extraer_contenido_temas_controlador($datos)
    {
        // Desencriptar y limpiar
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);
    
        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'id tema' => $id_tema,
        ]);

        // Consulta segura
        $conexion = MainModel::conectar();
        $sql = $conexion->prepare("SELECT * FROM contenidos_tema WHERE id_tema = :id_tema AND estado = 'activo ' ORDER BY orden ASC");
        $sql->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
      
        $sql->execute();

        $resultados = $sql->fetchAll(PDO::FETCH_ASSOC);

        if (count($resultados) > 0) {
            echo json_encode($resultados, JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            MainModel::jsonResponse("simple", "Sin resultados", "Este tema aún no tiene contenidos registrados.", "info");
        }
    }


    public static function crear_contenido_tema_controlador($datos)
    {
        $tipo_contenido = MainModel::limpiar_cadenas($datos['tipo_contenido']);
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);

         // Validar campos
            MainModel::validar_campos_obligatorios([
                "id tema" => $id_tema,
                "tipo de contenido" => $tipo_contenido,
                "documento docente" => $documento_profesor
            ]);

           $conexion = MainModel::conectar();

        if ($tipo_contenido == "texto") {
            $titulo_texto = MainModel::limpiar_cadenas($datos['titulo_texto']);
            $contenido =$datos['contenido'];
        
            // ✅ Obtener el mayor orden actual para ese tema
            $consulta_orden = $conexion->prepare("SELECT MAX(orden) AS ultimo_orden FROM contenidos_tema WHERE id_tema = :id_tema");
            $consulta_orden->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
            $consulta_orden->execute();
            $resultado_orden = $consulta_orden->fetch(PDO::FETCH_ASSOC);
            $nuevo_orden = ($resultado_orden['ultimo_orden'] ?? 0) + 1;

            // ✅ Insertar con orden
                $sql = $conexion->prepare("
                INSERT INTO contenidos_tema (
                    id_tema,
                    tipo_contenido,
                    titulo,
                    contenido_texto,
                    creado_por,
                    estado,
                    orden
                ) VALUES (
                    :id_tema,
                    :tipo_contenido,
                    :titulo,
                    :contenido_texto,
                    :creado_por,
                    'activo',
                    :orden
                )
            ");

            $sql->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
            $sql->bindParam(':tipo_contenido', $tipo_contenido, PDO::PARAM_STR);
            $sql->bindParam(':titulo', $titulo_texto, PDO::PARAM_STR);
            $sql->bindParam(':contenido_texto', $contenido, PDO::PARAM_STR);
            $sql->bindParam(':creado_por', $documento_profesor, PDO::PARAM_STR);
            $sql->bindParam(':orden', $nuevo_orden, PDO::PARAM_INT);

            if ($sql->execute()) {
                MainModel::jsonResponse("simple", "¡Éxito!", "Texto educativo guardado correctamente.", "success");
            } else {
                MainModel::jsonResponse("simple", "Error", "No se pudo guardar el texto.", "error");
            }


        } else if ($tipo_contenido == "imagen") {

            $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
            $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
            $tipo_archivo = MainModel::limpiar_cadenas($datos['tipo_archivo']);
            $imagen = isset($datos['imagen']) ? $datos['imagen'] : 'AvatarNone.png';

            // Obtener orden actual
            $consulta_orden = $conexion->prepare("SELECT MAX(orden) AS ultimo_orden FROM contenidos_tema WHERE id_tema = :id_tema");
            $consulta_orden->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
            $consulta_orden->execute();
            $resultado_orden = $consulta_orden->fetch(PDO::FETCH_ASSOC);
            $nuevo_orden = ($resultado_orden['ultimo_orden'] ?? 0) + 1;

            if ($tipo_archivo == 'archivo') {

                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $nombre_archivo = $_FILES['imagen']['name'];
                    $tipo_mime = $_FILES['imagen']['type'];
                    $temporal = $_FILES['imagen']['tmp_name'];

                    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!in_array($tipo_mime, $tipos_permitidos)) {
                        return MainModel::jsonResponse(
                            "simple",
                            "Tipo de imagen inválido",
                            "Solo se permiten imágenes en formato JPEG, PNG o WebP.",
                            "warning"
                        );
                    }

                    $ruta_base = __DIR__ . '/../views/resources';
                    $ruta_institucion = "$ruta_base/$codigo_institucion";
                    $ruta_sede = "$ruta_institucion/sedes/$id_sede";
                    $ruta_temas = "$ruta_sede/imagenes/temas_educativos/$documento_profesor/$id_tema";

                    if (!file_exists($ruta_temas)) {
                        mkdir($ruta_temas, 0775, true);
                    }

                    $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
                    $nuevo_nombre = 'usuario_' . uniqid() . '.' . $extension;
                    $ruta_destino = $ruta_temas . '/' . $nuevo_nombre;

                    if (move_uploaded_file($temporal, $ruta_destino)) {
                        $imagen = $nuevo_nombre;
                    }
                }

                        $sql = $conexion->prepare("
                    INSERT INTO contenidos_tema (
                        id_tema,
                        tipo_contenido,
                        descripcion,
                        creado_por,
                        estado,
                        orden
                    ) VALUES (
                        :id_tema,
                        :tipo_contenido,
                        :descripcion,
                        :creado_por,
                        'activo',
                        :orden
                    )
                ");

                $sql->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
                $sql->bindParam(':tipo_contenido', $tipo_contenido, PDO::PARAM_STR);
                $sql->bindParam(':descripcion', $imagen, PDO::PARAM_STR);
                $sql->bindParam(':creado_por', $documento_profesor, PDO::PARAM_STR);
                $sql->bindParam(':orden', $nuevo_orden, PDO::PARAM_INT);
            } else if ($tipo_archivo == 'url') {

                        $sql = $conexion->prepare("
                    INSERT INTO contenidos_tema (
                        id_tema,
                        tipo_contenido,
                        url_archivo,
                        creado_por,
                        estado,
                        orden
                    ) VALUES (
                        :id_tema,
                        :tipo_contenido,
                        :url_archivo,
                        :creado_por,
                        'activo',
                        :orden
                    )
                ");

                $sql->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
                $sql->bindParam(':tipo_contenido', $tipo_contenido, PDO::PARAM_STR);
                $sql->bindParam(':url_archivo', $imagen, PDO::PARAM_STR);
                $sql->bindParam(':creado_por', $documento_profesor, PDO::PARAM_STR);
                $sql->bindParam(':orden', $nuevo_orden, PDO::PARAM_INT);
            } else {
                return MainModel::jsonResponse("simple", "Error", "Tipo de archivo no válido. Debe ser 'archivo' o 'url'.", "error");
            }

            if ($sql->execute()) {
                return MainModel::jsonResponse("simple", "¡Éxito!", "Imagen educativa guardada correctamente.", "success");
            } else {
                return MainModel::jsonResponse("simple", "Error", "No se pudo guardar la imagen.", "error");
            }

        } else if ($tipo_contenido == "archivo") {
            $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
            $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));

            $archivos = $_FILES['archivos'] ?? null;

            if (!isset($archivos['name']) || !is_array($archivos['name']) || count($archivos['name']) === 0) {
                MainModel::jsonResponse("simple", "Sin archivos", "No se recibieron archivos válidos para procesar.", "warning");
            }

            // Crear ruta de destino
            $ruta_base = __DIR__ . '/../views/resources';
            $ruta_destino = "$ruta_base/$codigo_institucion/sedes/$id_sede/imagenes/temas_educativos/$documento_profesor/$id_tema/archivos";

            if (!file_exists($ruta_destino)) {
                mkdir($ruta_destino, 0775, true);
            }

            $conexion = MainModel::conectar();
            $guardados = 0;

            // Obtener orden actual
            $consulta_orden = $conexion->prepare("SELECT MAX(orden) AS ultimo_orden FROM contenidos_tema WHERE id_tema = :id_tema");
            $consulta_orden->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
            $consulta_orden->execute();
            $resultado_orden = $consulta_orden->fetch(PDO::FETCH_ASSOC);
            $orden_actual  = ($resultado_orden['ultimo_orden'] ?? 0) + 1;

            // Procesar todos los archivos
            foreach ($archivos['tmp_name'] as $index => $temporal) {
                $nombre_archivo = $archivos['name'][$index];
                $ruta_archivo_final = $ruta_destino . '/' . $nombre_archivo;

                // 1. Verificar si ya existe un archivo con ese nombre para ese tema y profesor
                $verificar = $conexion->prepare("SELECT id_contenido FROM contenidos_tema 
                 WHERE id_tema = :id_tema AND creado_por = :creado_por AND url_archivo = :nombre_archivo");

                $verificar->bindParam(":id_tema", $id_tema, PDO::PARAM_INT);
                $verificar->bindParam(":creado_por", $documento_profesor, PDO::PARAM_STR);
                $verificar->bindParam(":nombre_archivo", $nombre_archivo, PDO::PARAM_STR);
                $verificar->execute();

                if ($verificar->rowCount() > 0) {
                    // Saltamos este archivo duplicado
                    continue;
                }

                // 2. Aumentar el orden
                $orden_actual++;

                // 3. Mover archivo al servidor
                if (move_uploaded_file($temporal, $ruta_archivo_final)) {
                    // 4. Insertar en la base de datos
                    $sql = $conexion->prepare("INSERT INTO contenidos_tema 
            (id_tema, tipo_contenido, url_archivo, creado_por, estado, orden, fecha_creacion)
            VALUES (:id_tema, 'archivo', :url_archivo, :creado_por, 'activo', :orden, NOW())");

                    $sql->bindParam(":id_tema", $id_tema, PDO::PARAM_INT);
                    $sql->bindParam(":url_archivo", $nombre_archivo, PDO::PARAM_STR);
                    $sql->bindParam(":creado_por", $documento_profesor, PDO::PARAM_STR);
                    $sql->bindParam(":orden", $orden_actual, PDO::PARAM_INT);

                    if ($sql->execute()) {
                        $guardados++;
                    }
                }
            }


            if ($guardados > 0) {
                MainModel::jsonResponse("recargar", "Archivos guardados", "$guardados archivo(s) fueron subidos correctamente.", "success");
            } else {
                MainModel::jsonResponse("simple", "Error", "No se pudo guardar ninguno de los archivos.", "error");
            }
        } else if ($tipo_contenido == "video"){
            $video = MainModel::limpiar_cadenas($datos['video']);
             MainModel::validar_campos_obligatorios([
                "video" => $video,
            ]);

            // ✅ Obtener el mayor orden actual para ese tema
            $consulta_orden = $conexion->prepare("SELECT MAX(orden) AS ultimo_orden FROM contenidos_tema WHERE id_tema = :id_tema");
            $consulta_orden->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
            $consulta_orden->execute();
            $resultado_orden = $consulta_orden->fetch(PDO::FETCH_ASSOC);
            $nuevo_orden = ($resultado_orden['ultimo_orden'] ?? 0) + 1;

            // ✅ Insertar con orden
                $sql = $conexion->prepare("
                INSERT INTO contenidos_tema (
                    id_tema,
                    tipo_contenido,
                    url_archivo,
                    creado_por,
                    estado,
                    orden
                ) VALUES (
                    :id_tema,
                    :tipo_contenido,
                    :video,
                    :creado_por,
                    'activo',
                    :orden
                )
            ");

            $sql->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
            $sql->bindParam(':tipo_contenido', $tipo_contenido, PDO::PARAM_STR);
            $sql->bindParam(':video', $video, PDO::PARAM_STR);
            $sql->bindParam(':creado_por', $documento_profesor, PDO::PARAM_STR);
            $sql->bindParam(':orden', $nuevo_orden, PDO::PARAM_INT);

            if ($sql->execute()) {
                MainModel::jsonResponse("simple", "¡Éxito!", "Video educativo guardado correctamente.", "success");
            } else {
                MainModel::jsonResponse("simple", "Error", "No se pudo guardar el Video.", "error");
            }


        } else if ($tipo_contenido == "tarea"){
            $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
            $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
            $titulo_tarea = MainModel::limpiar_cadenas($datos['titulo_tarea']);
            $descripcion_tarea =$datos['contenido_tarea'];
            $fecha_entrega = MainModel::limpiar_cadenas($datos['fecha_entrega']);
            $fecha_inicio = MainModel::limpiar_cadenas($datos['fecha_inicio']);
            $fecha_limite_entrega = MainModel::limpiar_cadenas($datos['fecha_limite_entrega']);
            $recordarme_calificar = MainModel::limpiar_cadenas($datos['recordarme_calificar']);
            $tipo_entrega = MainModel::limpiar_cadenas($datos['tipo_entrega']);
            $tipo_archivo_seleccionado = MainModel::limpiar_cadenas($datos['tipo_archivo_seleccionado']);
            $es_grupal = MainModel::limpiar_cadenas($datos['es_grupal']);
            $id_grado = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grado']));
            $id_grupo = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grupo']));
            $id_materia = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_materia']));

             MainModel::validar_campos_obligatorios([
                "id tema" => $id_tema,
                "documento docente" => $documento_profesor,
                "titulo tarea" => $titulo_tarea,
                "descripcion tarea" => $descripcion_tarea,
                "fecha entrega" => $fecha_entrega,
                "fecha inicio" => $fecha_inicio,
                "fecha limite entrega" => $fecha_limite_entrega,
                "tipo entrega" => $tipo_entrega,
                "es grupal" => $es_grupal,
                "recordarme para calificar" => $recordarme_calificar,
                "id grado" => $id_grado,
                "id grupo" => $id_grupo,
                "id materia" => $id_materia,
            ]);

            

            MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_profesor);

            $check = MainModel::ejecutar_consultas_simples("SELECT id_grado FROM grados WHERE id_grado = '$id_grado'");
            if ($check->rowCount() <= 0) {
                MainModel::jsonResponse("simple", "Grado inválido", "El grado seleccionado no es válido o no existe en el sistema.", "warning");
            }

            $check = MainModel::ejecutar_consultas_simples("SELECT id_grupo FROM grupos WHERE id_grupo = '$id_grupo'");
            if ($check->rowCount() <= 0) {
                MainModel::jsonResponse("simple", "Grupo inválido", "El grupo seleccionado no es válido o no existe en el sistema.", "warning");
            }

            $check = MainModel::ejecutar_consultas_simples("SELECT id_materia FROM materias WHERE id_materia = '$id_materia'");
            if ($check->rowCount() <= 0) {
                MainModel::jsonResponse("simple", "Grupo inválido", "El grupo seleccionado no es válido o no existe en el sistema.", "warning");
            }

          

            if (strtotime($fecha_entrega) < strtotime($fecha_inicio)) {
                MainModel::jsonResponse("simple", "Fecha inválida", "La fecha de entrega no puede ser anterior a la fecha de inicio.", "warning");
            }

            if (strtotime($fecha_limite_entrega) < strtotime($fecha_entrega)) {
                MainModel::jsonResponse("simple", "Fecha inválida", "La fecha límite de entrega no puede ser anterior a la fecha de entrega.", "warning");
            }

             if (strtotime($fecha_limite_entrega) < strtotime($fecha_inicio)) {
                MainModel::jsonResponse("simple", "Fecha inválida", "La fecha límite de entrega no puede ser anterior a la fecha de inicio.", "warning");
            }

       

            // Obtener orden actual
            $consulta_orden = $conexion->prepare("SELECT MAX(orden) AS ultimo_orden FROM contenidos_tema WHERE id_tema = :id_tema");
            $consulta_orden->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
            $consulta_orden->execute();
            $resultado_orden = $consulta_orden->fetch(PDO::FETCH_ASSOC);
            $nuevo_orden  = ($resultado_orden['ultimo_orden'] ?? 0) + 1;

            // ✅ Insertar con orden
                $sql = $conexion->prepare("
                INSERT INTO contenidos_tema (
                    id_tema,
                    tipo_contenido,
                    titulo,
                    creado_por,
                    estado,
                    orden
                ) VALUES (
                    :id_tema,
                    :tipo_contenido,
                    :titulo,
                    :creado_por,
                    'activo',
                    :orden
                )
            ");

            $sql->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
            $sql->bindParam(':tipo_contenido', $tipo_contenido, PDO::PARAM_STR);
            $sql->bindParam(':titulo', $titulo_tarea, PDO::PARAM_STR);
            $sql->bindParam(':creado_por', $documento_profesor, PDO::PARAM_STR);
            $sql->bindParam(':orden', $nuevo_orden, PDO::PARAM_INT);

            if ($sql->execute()) {
                // Guardar detalles de la tarea

                $id_contenido = $conexion->lastInsertId();     // Obtener el ID recién insertado

                $archivos = $_FILES['archivos'] ?? null;

                // Inicializar arreglo de nombres
                $nombres_archivos_guardados = [];

                // Validar si vienen archivos
                if (!isset($archivos['name']) || !is_array($archivos['name']) || count($archivos['name']) === 0) {
                   $archivos_json = null;
                } else {
                    // Crear ruta de destino
                    $ruta_base = __DIR__ . '/../views/resources';
                    $ruta_destino = "$ruta_base/$codigo_institucion/sedes/$id_sede/imagenes/temas_educativos/$documento_profesor/$id_tema/archivos";

                    if (!file_exists($ruta_destino)) {
                        mkdir($ruta_destino, 0775, true);
                    }

                    // Procesar cada archivo
                    foreach ($archivos['tmp_name'] as $index => $temporal) {
                        $nombre_original = $archivos['name'][$index];

                        // Obtener extensión
                        $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
                        $nombre_sin_ext = pathinfo($nombre_original, PATHINFO_FILENAME);
                        // Crear nombre final con sufijo "__tarea"
                        $nuevo_nombre = $nombre_sin_ext . '__tarea.' . $extension;
                        $ruta_archivo_final = $ruta_destino . '/' . $nuevo_nombre;

                        // Mover archivo
                        if (move_uploaded_file($temporal, $ruta_archivo_final)) {
                            $nombres_archivos_guardados[] = $nuevo_nombre;
                        }
                    }

                    // Convertir a JSON solo si hay archivos válidos
                    $archivos_json = count($nombres_archivos_guardados) > 0
                        ? json_encode($nombres_archivos_guardados, JSON_UNESCAPED_UNICODE)
                        : null;
                }

                
                // ✅ Guardar este JSON en tu base de datos
               
                $sql = $conexion->prepare("INSERT INTO tareas (
                    id_contenido,id_tema, id_materia, documento_docente,id_grado,id_grupo,codigo_institucion,id_sede,titulo_tarea,
                    descripcion,archivos_adjuntos,fecha_inicio,fecha_entrega,fecha_limite_entrega,
                    recordarme_calificar,tipo_entrega,tipo_archivo_entrega,estado,es_grupal
                ) VALUES (
                   :id_contenido,:id_tema,:id_materia,:documento_docente,:id_grado,:id_grupo,:codigo_institucion,:id_sede,:titulo_tarea,
                    :descripcion,:archivos_adjuntos,:fecha_inicio,:fecha_entrega,:fecha_limite_entrega,:recordarme_calificar,
                    :tipo_entrega,:tipo_archivo_entrega,:estado,:es_grupal
                )
                ");

                $sql->bindParam(':id_contenido', $id_contenido, PDO::PARAM_INT);
                $sql->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
                $sql->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
                $sql->bindParam(':documento_docente', $documento_profesor, PDO::PARAM_STR);
                $sql->bindParam(':id_grado', $id_grado, PDO::PARAM_INT);
                $sql->bindParam(':id_grupo', $id_grupo, PDO::PARAM_INT);
                $sql->bindParam(':codigo_institucion', $codigo_institucion, PDO::PARAM_INT);
                $sql->bindParam(':id_sede', $id_sede, PDO::PARAM_INT);
                $sql->bindParam(':titulo_tarea', $titulo_tarea, PDO::PARAM_STR);
                $sql->bindParam(':descripcion', $descripcion_tarea, PDO::PARAM_STR);
                // Convertir el arreglo de archivos a JSON
                $sql->bindValue(':archivos_adjuntos', $archivos_json, is_null($archivos_json) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $sql->bindParam(':fecha_inicio', $fecha_inicio);
                $sql->bindParam(':fecha_entrega', $fecha_entrega);
                $sql->bindParam(':fecha_limite_entrega', $fecha_limite_entrega);
                $sql->bindParam(':recordarme_calificar', $recordarme_calificar);
                $sql->bindParam(':tipo_entrega', $tipo_entrega, PDO::PARAM_STR);
                $sql->bindParam(':tipo_archivo_entrega', $tipo_archivo_seleccionado, PDO::PARAM_STR);
                $sql->bindValue(':estado', 1, PDO::PARAM_INT); // activo
                $sql->bindParam(':es_grupal', $es_grupal, PDO::PARAM_BOOL);

                if ($sql->execute()) {
                    MainModel::jsonResponse("simple", "¡Éxito!", "Tarea guardada correctamente.", "success");
                } else {
                    MainModel::jsonResponse("simple", "Error", "No se pudo guardar la tarea.", "error");
                }


            
            } else {
                MainModel::jsonResponse("simple", "Error", "No se pudo guardar la tarea.", "error");
            }

            
            


        } else if ($tipo_contenido == "taller"){
            $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
            $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
            $titulo_tarea = MainModel::limpiar_cadenas($datos['titulo_tarea']);
            $descripcion_tarea =$datos['contenido_tarea'];
            $fecha_entrega = MainModel::limpiar_cadenas($datos['fecha_entrega']);
            $fecha_inicio = MainModel::limpiar_cadenas($datos['fecha_inicio']);
            $fecha_limite_entrega = MainModel::limpiar_cadenas($datos['fecha_limite_entrega']);
            $recordarme_calificar = MainModel::limpiar_cadenas($datos['recordarme_calificar']);
            $tipo_entrega = MainModel::limpiar_cadenas($datos['tipo_entrega']);
            $tipo_archivo_seleccionado = MainModel::limpiar_cadenas($datos['tipo_archivo_seleccionado']);
            $es_grupal = MainModel::limpiar_cadenas($datos['es_grupal']);
            $id_grado = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grado']));
            $id_grupo = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_grupo']));
            $id_materia = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_materia']));

             MainModel::validar_campos_obligatorios([
                "id tema" => $id_tema,
                "documento docente" => $documento_profesor,
                "titulo tarea" => $titulo_tarea,
                "descripcion tarea" => $descripcion_tarea,
                "fecha entrega" => $fecha_entrega,
                "fecha inicio" => $fecha_inicio,
                "fecha limite entrega" => $fecha_limite_entrega,
                "tipo entrega" => $tipo_entrega,
                "es grupal" => $es_grupal,
                "recordarme para calificar" => $recordarme_calificar,
                "id grado" => $id_grado,
                "id grupo" => $id_grupo,
                "id materia" => $id_materia,
            ]);

            

            MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_profesor);

            $check = MainModel::ejecutar_consultas_simples("SELECT id_grado FROM grados WHERE id_grado = '$id_grado'");
            if ($check->rowCount() <= 0) {
                MainModel::jsonResponse("simple", "Grado inválido", "El grado seleccionado no es válido o no existe en el sistema.", "warning");
            }

            $check = MainModel::ejecutar_consultas_simples("SELECT id_grupo FROM grupos WHERE id_grupo = '$id_grupo'");
            if ($check->rowCount() <= 0) {
                MainModel::jsonResponse("simple", "Grupo inválido", "El grupo seleccionado no es válido o no existe en el sistema.", "warning");
            }

            $check = MainModel::ejecutar_consultas_simples("SELECT id_materia FROM materias WHERE id_materia = '$id_materia'");
            if ($check->rowCount() <= 0) {
                MainModel::jsonResponse("simple", "Grupo inválido", "El grupo seleccionado no es válido o no existe en el sistema.", "warning");
            }

          

            if (strtotime($fecha_entrega) < strtotime($fecha_inicio)) {
                MainModel::jsonResponse("simple", "Fecha inválida", "La fecha de entrega no puede ser anterior a la fecha de inicio.", "warning");
            }

            if (strtotime($fecha_limite_entrega) < strtotime($fecha_entrega)) {
                MainModel::jsonResponse("simple", "Fecha inválida", "La fecha límite de entrega no puede ser anterior a la fecha de entrega.", "warning");
            }

             if (strtotime($fecha_limite_entrega) < strtotime($fecha_inicio)) {
                MainModel::jsonResponse("simple", "Fecha inválida", "La fecha límite de entrega no puede ser anterior a la fecha de inicio.", "warning");
            }

       

            // Obtener orden actual
            $consulta_orden = $conexion->prepare("SELECT MAX(orden) AS ultimo_orden FROM contenidos_tema WHERE id_tema = :id_tema");
            $consulta_orden->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
            $consulta_orden->execute();
            $resultado_orden = $consulta_orden->fetch(PDO::FETCH_ASSOC);
            $nuevo_orden  = ($resultado_orden['ultimo_orden'] ?? 0) + 1;

            // ✅ Insertar con orden
                $sql = $conexion->prepare("
                INSERT INTO contenidos_tema (
                    id_tema,
                    tipo_contenido,
                    titulo,
                    creado_por,
                    estado,
                    orden
                ) VALUES (
                    :id_tema,
                    :tipo_contenido,
                    :titulo,
                    :creado_por,
                    'activo',
                    :orden
                )
            ");

            $sql->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
            $sql->bindParam(':tipo_contenido', $tipo_contenido, PDO::PARAM_STR);
            $sql->bindParam(':titulo', $titulo_tarea, PDO::PARAM_STR);
            $sql->bindParam(':creado_por', $documento_profesor, PDO::PARAM_STR);
            $sql->bindParam(':orden', $nuevo_orden, PDO::PARAM_INT);

            if ($sql->execute()) {
                // Guardar detalles de la tarea

                $id_contenido = $conexion->lastInsertId();     // Obtener el ID recién insertado

                $archivos = $_FILES['archivos'] ?? null;

                // Inicializar arreglo de nombres
                $nombres_archivos_guardados = [];

                // Validar si vienen archivos
                if (!isset($archivos['name']) || !is_array($archivos['name']) || count($archivos['name']) === 0) {
                   $archivos_json = null;
                } else {
                    // Crear ruta de destino
                    $ruta_base = __DIR__ . '/../views/resources';
                    $ruta_destino = "$ruta_base/$codigo_institucion/sedes/$id_sede/imagenes/temas_educativos/$documento_profesor/$id_tema/archivos";

                    if (!file_exists($ruta_destino)) {
                        mkdir($ruta_destino, 0775, true);
                    }

                    // Procesar cada archivo
                    foreach ($archivos['tmp_name'] as $index => $temporal) {
                        $nombre_original = $archivos['name'][$index];

                        // Obtener extensión
                        $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
                        $nombre_sin_ext = pathinfo($nombre_original, PATHINFO_FILENAME);
                        // Crear nombre final con sufijo "__tarea"
                        $nuevo_nombre = $nombre_sin_ext . '__taller.' . $extension;
                        $ruta_archivo_final = $ruta_destino . '/' . $nuevo_nombre;

                        // Mover archivo
                        if (move_uploaded_file($temporal, $ruta_archivo_final)) {
                            $nombres_archivos_guardados[] = $nuevo_nombre;
                        }
                    }

                    // Convertir a JSON solo si hay archivos válidos
                    $archivos_json = count($nombres_archivos_guardados) > 0
                        ? json_encode($nombres_archivos_guardados, JSON_UNESCAPED_UNICODE)
                        : null;
                }

                
                // ✅ Guardar este JSON en tu base de datos
               
                $sql = $conexion->prepare("INSERT INTO taller (
                    id_contenido,id_tema, id_materia, documento_docente,id_grado,id_grupo,codigo_institucion,id_sede,titulo_tarea,
                    descripcion,archivos_adjuntos,fecha_inicio,fecha_entrega,fecha_limite_entrega,
                    recordarme_calificar,tipo_entrega,tipo_archivo_entrega,estado,es_grupal
                ) VALUES (
                   :id_contenido,:id_tema,:id_materia,:documento_docente,:id_grado,:id_grupo,:codigo_institucion,:id_sede,:titulo_tarea,
                    :descripcion,:archivos_adjuntos,:fecha_inicio,:fecha_entrega,:fecha_limite_entrega,:recordarme_calificar,
                    :tipo_entrega,:tipo_archivo_entrega,:estado,:es_grupal
                )
                ");

                $sql->bindParam(':id_contenido', $id_contenido, PDO::PARAM_INT);
                $sql->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
                $sql->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
                $sql->bindParam(':documento_docente', $documento_profesor, PDO::PARAM_STR);
                $sql->bindParam(':id_grado', $id_grado, PDO::PARAM_INT);
                $sql->bindParam(':id_grupo', $id_grupo, PDO::PARAM_INT);
                $sql->bindParam(':codigo_institucion', $codigo_institucion, PDO::PARAM_INT);
                $sql->bindParam(':id_sede', $id_sede, PDO::PARAM_INT);
                $sql->bindParam(':titulo_tarea', $titulo_tarea, PDO::PARAM_STR);
                $sql->bindParam(':descripcion', $descripcion_tarea, PDO::PARAM_STR);
                // Convertir el arreglo de archivos a JSON
                $sql->bindValue(':archivos_adjuntos', $archivos_json, is_null($archivos_json) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $sql->bindParam(':fecha_inicio', $fecha_inicio);
                $sql->bindParam(':fecha_entrega', $fecha_entrega);
                $sql->bindParam(':fecha_limite_entrega', $fecha_limite_entrega);
                $sql->bindParam(':recordarme_calificar', $recordarme_calificar);
                $sql->bindParam(':tipo_entrega', $tipo_entrega, PDO::PARAM_STR);
                $sql->bindParam(':tipo_archivo_entrega', $tipo_archivo_seleccionado, PDO::PARAM_STR);
                $sql->bindValue(':estado', 1, PDO::PARAM_INT); // activo
                $sql->bindParam(':es_grupal', $es_grupal, PDO::PARAM_BOOL);

                if ($sql->execute()) {
                    MainModel::jsonResponse("simple", "¡Éxito!", "Taller guardado correctamente.", "success");
                } else {
                    MainModel::jsonResponse("simple", "Error", "No se pudo guardar el taller.", "error");
                }


            
            } else {
                MainModel::jsonResponse("simple", "Error", "No se pudo guardar el taller.", "error");
            }

            

        } else if ($tipo_contenido == "foro") {
            $titulo_texto = MainModel::limpiar_cadenas($datos['titulo_texto']);
            $contenido = $datos['contenido'];
        
            // ✅ Obtener el mayor orden actual para ese tema
            $consulta_orden = $conexion->prepare("SELECT MAX(orden) AS ultimo_orden FROM contenidos_tema WHERE id_tema = :id_tema");
            $consulta_orden->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
            $consulta_orden->execute();
            $resultado_orden = $consulta_orden->fetch(PDO::FETCH_ASSOC);
            $nuevo_orden = ($resultado_orden['ultimo_orden'] ?? 0) + 1;

            // ✅ Insertar con orden
                $sql = $conexion->prepare("
                INSERT INTO contenidos_tema (
                    id_tema,
                    tipo_contenido,
                    titulo,
                    contenido_texto,
                    creado_por,
                    estado,
                    orden
                ) VALUES (
                    :id_tema,
                    :tipo_contenido,
                    :titulo,
                    :contenido_texto,
                    :creado_por,
                    'activo',
                    :orden
                )
            ");

            $sql->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
            $sql->bindParam(':tipo_contenido', $tipo_contenido, PDO::PARAM_STR);
            $sql->bindParam(':titulo', $titulo_texto, PDO::PARAM_STR);
            $sql->bindParam(':contenido_texto', $contenido, PDO::PARAM_STR);
            $sql->bindParam(':creado_por', $documento_profesor, PDO::PARAM_STR);
            $sql->bindParam(':orden', $nuevo_orden, PDO::PARAM_INT);

            if ($sql->execute()) {
                MainModel::jsonResponse("simple", "¡Éxito!", "Foro educativo guardado correctamente.", "success");
            } else {
                MainModel::jsonResponse("simple", "Error", "No se pudo guardar el foro.", "error");
            }
        }


    }

    public static function actualizar_contenido_tema_controlador($datos)
    {
        $tipo_contenido = MainModel::limpiar_cadenas($datos['tipo_contenido']);
        $id_contenido = MainModel::limpiar_cadenas($datos['id_contenido']);

        MainModel::validar_campos_obligatorios([
            'tipo de contenido' => $tipo_contenido,
            'id contenido' => $id_contenido,
        ]);

        $consulta_existencia = MainModel::ejecutar_consultas_simples("SELECT * FROM contenidos_tema WHERE id_contenido = '$id_contenido'");
        if ($consulta_existencia->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "ID inválido", "El ID del contenido que intenta actualizar no existe.", "warning");
        }

        if ($tipo_contenido == "texto") {
            $titulo_texto = MainModel::limpiar_cadenas($datos['titulo_texto']);
            $contenido = $datos['contenido_texto'];

            MainModel::validar_campos_obligatorios([
                'titulo' => $titulo_texto,
                'contenido' => $contenido,
            ]);

            // Datos actuales desde la BD
            $datos_actuales = $consulta_existencia->fetch(PDO::FETCH_ASSOC);
            $titulo_actual = $datos_actuales['titulo'] ?? '';
            $contenido_actual = $datos_actuales['contenido_texto'] ?? '';

            // Comparamos los datos actuales con los nuevos
            if ($titulo_actual === $titulo_texto && $contenido_actual === $contenido) {
                MainModel::jsonResponse("simple", "Sin cambios", "No se detectaron cambios en el contenido.", "info");
            }

            $check = MainModel::ejecutar_consultas_simples("UPDATE contenidos_tema SET titulo = '$titulo_texto', contenido_texto = '$contenido' 
            WHERE id_contenido = '$id_contenido'");
            if ($check->rowCount() <= 0) {
                MainModel::jsonResponse("simple", "Error al actualizar", "No se pudo actualizar la informacion del texto", "warning");
            }else {
                MainModel::jsonResponse("simple", "Actualizado", "El texto fue actualizado correctamente.", "success");
            }

        }else  if ($tipo_contenido == "foro"){
            
            $titulo_texto = MainModel::limpiar_cadenas($datos['titulo_texto']);
            $contenido = $datos['contenido_texto'];

            MainModel::validar_campos_obligatorios([
                'titulo' => $titulo_texto,
                'contenido' => $contenido,
            ]);

            // Datos actuales desde la BD
            $datos_actuales = $consulta_existencia->fetch(PDO::FETCH_ASSOC);
            $titulo_actual = $datos_actuales['titulo'] ?? '';
            $contenido_actual = $datos_actuales['contenido_texto'] ?? '';

            // Comparamos los datos actuales con los nuevos
            if ($titulo_actual === $titulo_texto && $contenido_actual === $contenido) {
                MainModel::jsonResponse("simple", "Sin cambios", "No se detectaron cambios en el contenido.", "info");
            }

            $check = MainModel::ejecutar_consultas_simples("UPDATE contenidos_tema SET titulo = '$titulo_texto', contenido_texto = '$contenido' 
            WHERE id_contenido = '$id_contenido'");
            if ($check->rowCount() <= 0) {
                MainModel::jsonResponse("simple", "Error al actualizar", "No se pudo actualizar la informacion del foro", "warning");
            }else {
                MainModel::jsonResponse("simple", "Actualizado", "El foro fue actualizado correctamente.", "success");
            }
        }
    }
    public static function eliminar_texto_educativos_controlador($datos)
    {
        $id_contenido = MainModel::limpiar_cadenas($datos['id_contenido']);
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'id contenido' => $id_contenido,
            'documento docente' => $documento_profesor,
        ]);

        // Verificar existencia del contenido
        $consulta = MainModel::ejecutar_consultas_simples("SELECT id_contenido FROM contenidos_tema 
        WHERE id_contenido = '$id_contenido' 
        AND creado_por = '$documento_profesor'");

        if ($consulta->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El contenido que deseas eliminar no existe o no pertenece a este docente.", "warning");
        }

        // Eliminar el contenido
        $conexion = MainModel::conectar();
        $sql = $conexion->prepare("DELETE FROM contenidos_tema 
        WHERE id_contenido = :id_contenido 
        AND creado_por = :creado_por");

        $sql->bindParam(":id_contenido", $id_contenido);
        $sql->bindParam(":creado_por", $documento_profesor);

        if ($sql->execute()) {
            MainModel::jsonResponse("simple", "Contenido eliminado", "El contenido fue eliminado correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo eliminar el contenido. Intenta nuevamente.", "error");
        }
    }

    public static function eliminar_imagenes_educativos_controlador($datos)
    {
        $id_contenido = MainModel::limpiar_cadenas($datos['id_contenido']);
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'id contenido' => $id_contenido,
            'documento docente' => $documento_profesor,
            'id sede' => $id_sede,
            'institución' => $codigo_institucion,
            'id tema' => $id_tema
        ]);

        // Verificar existencia del contenido
        $consulta = MainModel::ejecutar_consultas_simples("SELECT * FROM contenidos_tema 
        WHERE id_contenido = '$id_contenido' 
        AND creado_por = '$documento_profesor'");

        if ($consulta->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El contenido que deseas eliminar no existe o no pertenece a este docente.", "warning");
        }

        // Obtener campos para eliminar archivos físicos
        $contenido = $consulta->fetch(PDO::FETCH_ASSOC);
        $descripcion = $contenido['descripcion'] ?? null;
        $url_archivo = $contenido['url_archivo'] ?? null;

        // Construir rutas
        $ruta_base = __DIR__ . '/../views/resources';
        $ruta_institucion = "$ruta_base/$codigo_institucion";
        $ruta_sede = "$ruta_institucion/sedes/$id_sede";
        $ruta_tema = "$ruta_sede/imagenes/temas_educativos/$documento_profesor/$id_tema";

        // Eliminar archivo local si existe
        if (!empty($descripcion)) {
            $archivo_descripcion = "$ruta_tema/$descripcion";
            if (file_exists($archivo_descripcion)) {
                unlink($archivo_descripcion);
            }
        }

        if (!empty($url_archivo) && filter_var($url_archivo, FILTER_VALIDATE_URL) === false) {
            $archivo_url = "$ruta_tema/$url_archivo";
            if (file_exists($archivo_url)) {
                unlink($archivo_url);
            }
        }

        // Eliminar registro en la base de datos
        $conexion = MainModel::conectar();
        $sql = $conexion->prepare("DELETE FROM contenidos_tema 
        WHERE id_contenido = :id_contenido 
        AND creado_por = :creado_por");

        $sql->bindParam(":id_contenido", $id_contenido);
        $sql->bindParam(":creado_por", $documento_profesor);

        if ($sql->execute()) {
            MainModel::jsonResponse("simple", "Contenido eliminado", "La imagen fué eliminada correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo eliminar la imagen. Intenta nuevamente.", "error");
        }
    }

    public static function eliminar_archivos_educativos_controlador($datos)
    {
        $id_contenido = MainModel::limpiar_cadenas($datos['id_contenido']);
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'id contenido' => $id_contenido,
            'documento docente' => $documento_profesor,
            'id sede' => $id_sede,
            'institución' => $codigo_institucion,
            'id tema' => $id_tema
        ]);

        // Verificar existencia del contenido
        $consulta = MainModel::ejecutar_consultas_simples("SELECT * FROM contenidos_tema 
        WHERE id_contenido = '$id_contenido' 
        AND creado_por = '$documento_profesor'");

        if ($consulta->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El contenido que deseas eliminar no existe o no pertenece a este docente.", "warning");
        }

        // Obtener campos para eliminar archivos físicos
        $contenido = $consulta->fetch(PDO::FETCH_ASSOC);
        $url_archivo = $contenido['url_archivo'] ?? null;

        // Construir rutas
        $ruta_base = __DIR__ . '/../views/resources';
        $ruta_institucion = "$ruta_base/$codigo_institucion";
        $ruta_sede = "$ruta_institucion/sedes/$id_sede";
        $ruta_archivos = "$ruta_sede/imagenes/temas_educativos/$documento_profesor/$id_tema/archivos";

        // Eliminar archivo local si existe
        if (!empty($url_archivo)) {
            $archivo_descripcion = "$ruta_archivos/$url_archivo";
            if (file_exists($archivo_descripcion)) {
                unlink($archivo_descripcion);
            }
        }

        // Eliminar registro en la base de datos
        $conexion = MainModel::conectar();
        $sql = $conexion->prepare("DELETE FROM contenidos_tema 
        WHERE id_contenido = :id_contenido 
        AND creado_por = :creado_por");

        $sql->bindParam(":id_contenido", $id_contenido);
        $sql->bindParam(":creado_por", $documento_profesor);

        if ($sql->execute()) {
            MainModel::jsonResponse("simple", "Contenido eliminado", "El archivo fué eliminado correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo eliminar el archivo. Intenta nuevamente.", "error");
        }
    }

    public static function editar_nombre_temas_educativos($datos)
    {
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);
        $nombre_tema = MainModel::limpiar_cadenas($datos['nombre_tema']);
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'id tema' => $id_tema,
            'nuevo nombre del tema' => $nombre_tema,
            'id sede' => $id_sede,
            'institución' => $codigo_institucion
        ]);

        // Verificar que el tema exista
        $consulta = MainModel::ejecutar_consultas_simples("SELECT id_tema FROM temas_materia 
        WHERE id_tema = '$id_tema' 
        AND id_sede = '$id_sede' 
        AND codigo_institucion = '$codigo_institucion'");

        if ($consulta->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El tema que deseas editar no existe o no pertenece a esta sede o institución.", "warning");
        }

        // Actualizar el título del tema
        $conexion = MainModel::conectar();
        $sql = $conexion->prepare("UPDATE temas_materia 
        SET titulo_tema = :nombre_tema 
        WHERE id_tema = :id_tema 
        AND id_sede = :id_sede 
        AND codigo_institucion = :codigo_institucion");

        $sql->bindParam(":nombre_tema", $nombre_tema);
        $sql->bindParam(":id_tema", $id_tema);
        $sql->bindParam(":id_sede", $id_sede);
        $sql->bindParam(":codigo_institucion", $codigo_institucion);

        if ($sql->execute()) {

            MainModel::jsonResponse("simple", "Tema actualizado", "El nombre del tema fue actualizado correctamente.", "success", [
                "id_tema" => $id_tema
            ]);
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar el nombre del tema. Intenta nuevamente.", "error");
        }
    }

    public static function editar_estado_temas_educativos($datos)
    {
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);
        $estado = MainModel::limpiar_cadenas($datos['estado']);
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'id tema' => $id_tema,
            'estado' => $estado,
            'id sede' => $id_sede,
            'institución' => $codigo_institucion
        ]);

        // Verificar que el tema exista
        $consulta = MainModel::ejecutar_consultas_simples("SELECT id_tema FROM temas_materia 
        WHERE id_tema = '$id_tema' 
        AND id_sede = '$id_sede' 
        AND codigo_institucion = '$codigo_institucion'");

        if ($consulta->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El tema que deseas editar no existe o no pertenece a esta sede o institución.", "warning");
        }

        // Actualizar el título del tema
        $conexion = MainModel::conectar();
        $sql = $conexion->prepare("UPDATE temas_materia 
        SET estado = :estado 
        WHERE id_tema = :id_tema 
        AND id_sede = :id_sede 
        AND codigo_institucion = :codigo_institucion");

        $sql->bindParam(":estado", $estado);
        $sql->bindParam(":id_tema", $id_tema);
        $sql->bindParam(":id_sede", $id_sede);
        $sql->bindParam(":codigo_institucion", $codigo_institucion);

        if ($sql->execute()) {
            MainModel::jsonResponse("simple", "Tema ".$estado, "El nombre del tema fue actualizado correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar el estado del tema. Intenta nuevamente.", "error");
        }
    }

    public static function actualizar_orden_temas_controlador($datos)
    {
        // ✅ Decodifica el JSON enviado desde Angular
        $temas = json_decode($datos['datos'], true);

        if (!is_array($temas)) {
            MainModel::jsonResponse("simple", "Datos inválidos", "El formato de los datos es incorrecto.", "error");
            return;
        }

        $conexion = MainModel::conectar();
        $conexion->beginTransaction();

        try {
            foreach ($temas as $tema) {
                $id_tema = isset($tema['id_tema']) ? (int)$tema['id_tema'] : null;
                $nuevo_orden = isset($tema['nuevo_orden']) ? (int)$tema['nuevo_orden'] : null;

                if ($id_tema === null || $nuevo_orden === null) {
                    throw new Exception("Datos incompletos para el tema.");
                }

                $sql = $conexion->prepare("UPDATE temas_materia SET orden = :orden WHERE id_tema = :id_tema");
                $sql->bindParam(':orden', $nuevo_orden, PDO::PARAM_INT);
                $sql->bindParam(':id_tema', $id_tema, PDO::PARAM_INT);
                $sql->execute();
            }

            $conexion->commit();
            MainModel::jsonResponse("Toast", "¡Éxito!", "El orden de los temas fue actualizado correctamente.", "success");
        } catch (Exception $e) {
            $conexion->rollBack();
            MainModel::jsonResponse("Toast", "Error", "No se pudo actualizar el orden. " . $e->getMessage(), "error");
        }
    }


     public static function actualizar_orden_contenido_temas_controlador($datos)
    {
        // Decodifica directamente el JSON recibido
        $temas = json_decode($datos['datos'], true);

        // Validación básica
        if (!is_array($temas)) {
            MainModel::jsonResponse("simple", "Datos inválidos", "Los datos para actualizar el orden no son válidos.", "error");
            return;
        }

        // Ejecutar actualización por cada tema
        foreach ($temas as $tema) {
            $orden = (int) $tema['nuevo_orden'];
            $id_tema = (int) $tema['id_contenido'];

            $sql = MainModel::conectar()->prepare("UPDATE contenidos_tema SET orden = :orden WHERE id_contenido = :id_contenido");
            $sql->bindParam(':orden', $orden, PDO::PARAM_INT);
            $sql->bindParam(':id_contenido', $id_tema, PDO::PARAM_INT);
            $sql->execute();
        }

        // Respuesta de éxito
        MainModel::jsonResponse("Toast", "¡Éxito!", "El orden del contenido fue actualizado correctamente.", "success");
    }

    /*********************************tareas************************************ */

    public static function extraer_contenido_tareas_controlador($datos)
    {
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);
        $id_contenido = MainModel::limpiar_cadenas($datos['id_contenido']);

          MainModel::validar_campos_obligatorios([
                "id tema" => $id_tema,
                "id contenido" => $id_contenido,
                "id sede" => $id_sede,
                "institución" => $codigo_institucion
            ]);

        // Validar acceso
        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, null);
        // Consulta completa de tareas
        $consulta = "
        SELECT * FROM tareas 
        WHERE id_sede = :id_sede 
        AND codigo_institucion = :codigo_institucion 
        AND id_tema = :id_tema 
        AND id_contenido = :id_contenido
        ORDER BY fecha_entrega DESC
        ";

        $conexion = MainModel::conectar();
        $sql = $conexion->prepare($consulta);
        $sql->bindParam(':id_sede', $id_sede);
        $sql->bindParam(':codigo_institucion', $codigo_institucion);
        $sql->bindParam(':id_tema', $id_tema);
        $sql->bindParam(':id_contenido', $id_contenido);

        $sql->execute();

        // Obtener solo una fila (la primera que cumpla las condiciones)
        $fila = $sql->fetch(PDO::FETCH_ASSOC);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($fila, JSON_UNESCAPED_UNICODE);
    }

    public static function eliminar_archivos_tareas_registrados_controlador($datos)
    {
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);
        $id_tarea = MainModel::limpiar_cadenas($datos['id_tarea']);
        $nombre_archivo = MainModel::limpiar_cadenas($datos['nombre_archivo']);

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            "id sede" => $id_sede,
            "institución" => $codigo_institucion,
            "documento docente" => $documento_profesor,
            "id tema" => $id_tema,
            "id tarea" => $id_tarea,
            "nombre archivo" => $nombre_archivo
        ]);

        // Validar acceso
        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_profesor);

        // Comprobar existencia de la tarea
        $consulta = MainModel::ejecutar_consultas_simples("SELECT archivos_adjuntos FROM tareas WHERE id_tarea = '$id_tarea' LIMIT 1");

        if ($consulta->rowCount() == 0) {
            MainModel::jsonResponse("simple", "Tarea no encontrada", "No se encontró la tarea con el ID proporcionado.", "warning");
        }

        $fila = $consulta->fetch(PDO::FETCH_ASSOC);

        // Convertir a arreglo PHP
        $archivos = json_decode($fila['archivos_adjuntos'], true);

        if (!is_array($archivos)) {
            $archivos = [];
        }

        // Ruta completa del archivo
        $ruta_base = __DIR__ . '/../views/resources';
        $ruta_destino = "$ruta_base/$codigo_institucion/sedes/$id_sede/imagenes/temas_educativos/$documento_profesor/$id_tema/archivos";
        $ruta_archivo = "$ruta_destino/$nombre_archivo";

        // Verificar si el archivo está en el arreglo
        if (!in_array($nombre_archivo, $archivos)) {
            MainModel::jsonResponse("simple", "Archivo no encontrado", "El archivo no está registrado en esta tarea.", "warning");
        }

        // Eliminar archivo físico si existe
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }

        // Eliminar del arreglo
        $archivos_filtrados = array_filter($archivos, fn($archivo) => $archivo !== $nombre_archivo);

        // Reindexar el arreglo
        $archivos_actualizados = array_values($archivos_filtrados);

        // Codificar de nuevo a JSON
        $json_actualizado = json_encode($archivos_actualizados, JSON_UNESCAPED_UNICODE);

        // Actualizar en base de datos
        $conexion = MainModel::conectar();
        $sql = $conexion->prepare("UPDATE tareas SET archivos_adjuntos = :adjuntos WHERE id_tarea = :id_tarea");
        $sql->bindParam(':adjuntos', $json_actualizado);
        $sql->bindParam(':id_tarea', $id_tarea);
        $actualizado = $sql->execute();

        if ($actualizado) {
            MainModel::jsonResponse("simple", "Archivo eliminado", "El archivo fue eliminado correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar la tarea en la base de datos.", "error");
        }
    }

    public static function actualizar_informacion_tareas_controlador($datos)
    {
        // Paso 1: Limpiar datos de entrada
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $titulo_tarea = MainModel::limpiar_cadenas($datos['titulo_tarea']);
        $descripcion_tarea =$datos['contenido_tarea'];
        $fecha_entrega = MainModel::limpiar_cadenas($datos['fecha_entrega']);
        $fecha_inicio = MainModel::limpiar_cadenas($datos['fecha_inicio']);
        $fecha_limite_entrega = MainModel::limpiar_cadenas($datos['fecha_limite_entrega']);
        $recordarme_calificar = MainModel::limpiar_cadenas($datos['recordarme_calificar']);
        $tipo_entrega = MainModel::limpiar_cadenas($datos['tipo_entrega']);
        $tipo_archivo_seleccionado = MainModel::limpiar_cadenas($datos['tipo_archivo_seleccionado']);
        $es_grupal = MainModel::limpiar_cadenas($datos['es_grupal']);
        $id_contenido = MainModel::limpiar_cadenas($datos['id_contenido']);
        $id_tarea = MainModel::limpiar_cadenas($datos['id_tarea']);

        MainModel::validar_campos_obligatorios([
            "id tema" => $id_tema,
            "documento docente" => $documento_profesor,
            "id sede" => $id_sede,
            "institución" => $codigo_institucion,
            "titulo tarea" => $titulo_tarea,
            "contenido tarea" => $descripcion_tarea,
            "fecha entrega" => $fecha_entrega,
            "fecha inicio" => $fecha_inicio,
            "fecha limite entrega" => $fecha_limite_entrega,
            "tipo entrega" => $tipo_entrega,
            "es grupal" => $es_grupal,
            "id contenido" => $id_contenido,
            "id tarea" => $id_tarea
        ]);

        // Paso 2: Consultar datos actuales de la tarea
        $conexion = MainModel::conectar();
        $consulta = $conexion->prepare("SELECT * FROM tareas WHERE id_tarea = :id_tarea");
        $consulta->bindParam(":id_tarea", $id_tarea);
        $consulta->execute();

        if ($consulta->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Tarea no encontrada", "No se encontró una tarea con el ID proporcionado.", "warning");
        }

        $datosActuales = $consulta->fetch(PDO::FETCH_ASSOC);

        // Paso 3: Archivos existentes
        $archivos_guardados = json_decode($datosActuales['archivos_adjuntos'], true);
        if (!is_array($archivos_guardados)) $archivos_guardados = [];

        // Paso 4: Procesar nuevos archivos (si los hay)
        $archivos = $_FILES['archivos'] ?? null;
        $archivos_nuevos = [];

        if (isset($archivos['name']) && is_array($archivos['name']) && count($archivos['name']) > 0) {
            $ruta_base = __DIR__ . '/../views/resources';
            $ruta_destino = "$ruta_base/$codigo_institucion/sedes/$id_sede/imagenes/temas_educativos/$documento_profesor/$id_tema/archivos";

            if (!file_exists($ruta_destino)) mkdir($ruta_destino, 0775, true);

            foreach ($archivos['tmp_name'] as $index => $temporal) {
                $nombre_original = $archivos['name'][$index];
                $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
                $nombre_sin_ext = pathinfo($nombre_original, PATHINFO_FILENAME);
                $nuevo_nombre = $nombre_sin_ext . '__tarea.' . $extension;
                $ruta_archivo_final = $ruta_destino . '/' . $nuevo_nombre;

                if (move_uploaded_file($temporal, $ruta_archivo_final)) {
                    $archivos_nuevos[] = $nuevo_nombre;
                }
            }
        }

        // Paso 5: Fusionar archivos
        $archivos_actualizados = array_values(array_unique(array_merge($archivos_guardados, $archivos_nuevos)));
        $archivos_json = count($archivos_actualizados) > 0
            ? json_encode($archivos_actualizados, JSON_UNESCAPED_UNICODE)
            : null;

        // Paso 6: Comparar si hay diferencias
        $cambios = (
            $titulo_tarea !== $datosActuales['titulo_tarea'] ||
            $descripcion_tarea !== $datosActuales['descripcion'] ||
            $fecha_entrega !== $datosActuales['fecha_entrega'] ||
            $fecha_inicio !== $datosActuales['fecha_inicio'] ||
            $fecha_limite_entrega !== $datosActuales['fecha_limite_entrega'] ||
            $recordarme_calificar !== $datosActuales['recordarme_calificar'] ||
            $tipo_entrega !== $datosActuales['tipo_entrega'] ||
            $tipo_archivo_seleccionado !== $datosActuales['tipo_archivo_entrega'] ||
            $es_grupal != $datosActuales['es_grupal'] ||
            json_encode($archivos_guardados) !== json_encode($archivos_actualizados)
        );

        if (!$cambios) {
            MainModel::jsonResponse("simple", "Sin cambios", "No se realizaron cambios en la tarea.", "info");
        }

        // Paso 7: Actualizar solo si hubo cambios
        $sql = $conexion->prepare("
        UPDATE tareas SET 
            titulo_tarea = :titulo_tarea,
            descripcion = :descripcion,
            fecha_inicio = :fecha_inicio,
            fecha_entrega = :fecha_entrega,
            fecha_limite_entrega = :fecha_limite_entrega,
            recordarme_calificar = :recordarme_calificar,
            tipo_entrega = :tipo_entrega,
            tipo_archivo_entrega = :tipo_archivo_entrega,
            archivos_adjuntos = :archivos_adjuntos,
            es_grupal = :es_grupal
        WHERE id_tarea = :id_tarea
        ");

        $sql->bindParam(':titulo_tarea', $titulo_tarea);
        $sql->bindParam(':descripcion', $descripcion_tarea);
        $sql->bindParam(':fecha_inicio', $fecha_inicio);
        $sql->bindParam(':fecha_entrega', $fecha_entrega);
        $sql->bindParam(':fecha_limite_entrega', $fecha_limite_entrega);
        $sql->bindParam(':recordarme_calificar', $recordarme_calificar);
        $sql->bindParam(':tipo_entrega', $tipo_entrega);
        $sql->bindParam(':tipo_archivo_entrega', $tipo_archivo_seleccionado);
        $sql->bindValue(':archivos_adjuntos', $archivos_json, is_null($archivos_json) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $sql->bindParam(':es_grupal', $es_grupal, PDO::PARAM_BOOL);
        $sql->bindParam(':id_tarea', $id_tarea);

        if ($sql->execute()) {

            // actualizar el contenido del tema si es necesario
          
                $sql_contenido = $conexion->prepare("UPDATE contenidos_tema SET titulo = :titulo  WHERE id_contenido = :id_contenido");
                $sql_contenido->bindParam(':titulo', $titulo_tarea);
                $sql_contenido->bindParam(':id_contenido', $id_contenido);
                $sql_contenido->execute();
                // Verificar si la actualización del contenido fue exitosa
                if ($sql_contenido->rowCount() <= 0) {
                      MainModel::jsonResponse("simple", "¡Éxito!", "Tarea actualizada correctamente.", "success");
                }  

            MainModel::jsonResponse("simple", "¡Éxito!", "Tarea actualizada correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar la tarea.", "error");
        }
    }


    public static function eliminar_tareas_educativas_controlador($datos)
    {
        $id_contenido = MainModel::limpiar_cadenas($datos['id_contenido']);
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'id contenido' => $id_contenido,
            'documento docente' => $documento_profesor,
            'id sede' => $id_sede,
            'institución' => $codigo_institucion,
            'id tema' => $id_tema
        ]);

        $ruta_base = __DIR__ . '/../views/resources';
        $ruta_destino = "$ruta_base/$codigo_institucion/sedes/$id_sede/imagenes/temas_educativos/$documento_profesor/$id_tema/archivos";

        // Verificar que el contenido exista y le pertenezca al docente
        $consulta = MainModel::ejecutar_consultas_simples("
        SELECT id_contenido FROM contenidos_tema 
        WHERE id_contenido = '$id_contenido' 
        AND creado_por = '$documento_profesor'
        ");

        if ($consulta->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El contenido que deseas eliminar no existe o no pertenece a este docente.", "warning");
        }

        try {
            $conexion = MainModel::conectar();
            $conexion->beginTransaction();

            // 🔹 1. Obtener tareas con archivos para eliminar físicamente
            $consulta_tareas = $conexion->prepare("SELECT archivos_adjuntos FROM tareas WHERE id_contenido = :id_contenido");
            $consulta_tareas->bindParam(":id_contenido", $id_contenido);
            $consulta_tareas->execute();

            while ($fila = $consulta_tareas->fetch(PDO::FETCH_ASSOC)) {
                $archivos = json_decode($fila['archivos_adjuntos'], true);
                if (is_array($archivos)) {
                    foreach ($archivos as $archivo) {
                        $ruta_archivo = $ruta_destino . '/' . $archivo;
                        if (file_exists($ruta_archivo)) {
                            unlink($ruta_archivo);
                        }
                    }
                }
            }

            // 🔹 2. Eliminar las tareas de la base de datos
            $sql1 = $conexion->prepare("DELETE FROM tareas WHERE id_contenido = :id_contenido");
            $sql1->bindParam(":id_contenido", $id_contenido);
            $sql1->execute();

           
            // 🔹 4. Eliminar el contenido
            $sql3 = $conexion->prepare("DELETE FROM contenidos_tema 
            WHERE id_contenido = :id_contenido AND creado_por = :creado_por");
            $sql3->bindParam(":id_contenido", $id_contenido);
            $sql3->bindParam(":creado_por", $documento_profesor);
            $sql3->execute();

            $conexion->commit();

            MainModel::jsonResponse("simple", "Contenido eliminado", "El contenido, las tareas asociadas y sus archivos fueron eliminados correctamente.", "success");
        } catch (Exception $e) {
            $conexion->rollBack();
            MainModel::jsonResponse("simple", "Error crítico", "Ocurrió un error al intentar eliminar el contenido: " . $e->getMessage(), "error");
        }
    }

    /*********************************talleres************************************ */

    public static function extraer_contenido_talleres_controlador($datos)
    {
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);
        $id_contenido = MainModel::limpiar_cadenas($datos['id_contenido']);

          MainModel::validar_campos_obligatorios([
                "id tema" => $id_tema,
                "documento docente" => $documento_profesor,
                "id contenido" => $id_contenido,
                "id sede" => $id_sede,
                "institución" => $codigo_institucion
            ]);

        // Validar acceso
        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, null);
        // Consulta completa de tareas
        $consulta = "
        SELECT * FROM taller 
        WHERE id_sede = :id_sede 
        AND codigo_institucion = :codigo_institucion 
        AND documento_docente = :documento_docente 
        AND id_tema = :id_tema 
        AND id_contenido = :id_contenido
        ORDER BY fecha_entrega DESC
        ";

        $conexion = MainModel::conectar();
        $sql = $conexion->prepare($consulta);
        $sql->bindParam(':id_sede', $id_sede);
        $sql->bindParam(':codigo_institucion', $codigo_institucion);
        $sql->bindParam(':documento_docente', $documento_profesor);
        $sql->bindParam(':id_tema', $id_tema);
        $sql->bindParam(':id_contenido', $id_contenido);

        $sql->execute();

        // Obtener solo una fila (la primera que cumpla las condiciones)
        $fila = $sql->fetch(PDO::FETCH_ASSOC);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($fila, JSON_UNESCAPED_UNICODE);
    }

    public static function eliminar_archivos_taller_registrados_controlador($datos)
    {
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);
        $id_taller = MainModel::limpiar_cadenas($datos['id_taller']);
        $nombre_archivo = MainModel::limpiar_cadenas($datos['nombre_archivo']);

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            "id sede" => $id_sede,
            "institución" => $codigo_institucion,
            "documento docente" => $documento_profesor,
            "id tema" => $id_tema,
            "id taller" => $id_taller,
            "nombre archivo" => $nombre_archivo
        ]);

        // Validar acceso
        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_profesor);

        // Comprobar existencia del taller
        $consulta = MainModel::ejecutar_consultas_simples("SELECT archivos_adjuntos FROM taller WHERE id_taller = '$id_taller' LIMIT 1");

        if ($consulta->rowCount() == 0) {
            MainModel::jsonResponse("simple", "Taller no encontrado", "No se encontró el taller con el ID proporcionado.", "warning");
        }

        $fila = $consulta->fetch(PDO::FETCH_ASSOC);

        // Convertir a arreglo PHP
        $archivos = json_decode($fila['archivos_adjuntos'], true);

        if (!is_array($archivos)) {
            $archivos = [];
        }

        // Ruta completa del archivo
        $ruta_base = __DIR__ . '/../views/resources';
        $ruta_destino = "$ruta_base/$codigo_institucion/sedes/$id_sede/imagenes/temas_educativos/$documento_profesor/$id_tema/archivos";
        $ruta_archivo = "$ruta_destino/$nombre_archivo";

        // Verificar si el archivo está en el arreglo
        if (!in_array($nombre_archivo, $archivos)) {
            MainModel::jsonResponse("simple", "Archivo no encontrado", "El archivo no está registrado en este taller.", "warning");
        }

        // Eliminar archivo físico si existe
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }

        // Eliminar del arreglo
        $archivos_filtrados = array_filter($archivos, fn($archivo) => $archivo !== $nombre_archivo);

        // Reindexar el arreglo
        $archivos_actualizados = array_values($archivos_filtrados);

        // Codificar de nuevo a JSON
        $json_actualizado = json_encode($archivos_actualizados, JSON_UNESCAPED_UNICODE);

        // Actualizar en base de datos
        $conexion = MainModel::conectar();
        $sql = $conexion->prepare("UPDATE taller SET archivos_adjuntos = :adjuntos WHERE id_taller = :id_taller");
        $sql->bindParam(':adjuntos', $json_actualizado);
        $sql->bindParam(':id_taller', $id_taller);
        $actualizado = $sql->execute();

        if ($actualizado) {
            MainModel::jsonResponse("simple", "Archivo eliminado", "El archivo fue eliminado correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar el taller en la base de datos.", "error");
        }
    }

    public static function actualizar_informacion_taller_controlador($datos)
    {
        // Paso 1: Limpiar datos de entrada
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));
        $titulo_tarea = MainModel::limpiar_cadenas($datos['titulo_tarea']);
        $descripcion_tarea =$datos['contenido_tarea'];
        $fecha_entrega = MainModel::limpiar_cadenas($datos['fecha_entrega']);
        $fecha_inicio = MainModel::limpiar_cadenas($datos['fecha_inicio']);
        $fecha_limite_entrega = MainModel::limpiar_cadenas($datos['fecha_limite_entrega']);
        $recordarme_calificar = MainModel::limpiar_cadenas($datos['recordarme_calificar']);
        $tipo_entrega = MainModel::limpiar_cadenas($datos['tipo_entrega']);
        $tipo_archivo_seleccionado = MainModel::limpiar_cadenas($datos['tipo_archivo_seleccionado']);
        $es_grupal = MainModel::limpiar_cadenas($datos['es_grupal']);
        $id_contenido = MainModel::limpiar_cadenas($datos['id_contenido']);
        $id_taller = MainModel::limpiar_cadenas($datos['id_taller']);

        MainModel::validar_campos_obligatorios([
            "id tema" => $id_tema,
            "documento docente" => $documento_profesor,
            "id sede" => $id_sede,
            "institución" => $codigo_institucion,
            "titulo tarea" => $titulo_tarea,
            "contenido tarea" => $descripcion_tarea,
            "fecha entrega" => $fecha_entrega,
            "fecha inicio" => $fecha_inicio,
            "fecha limite entrega" => $fecha_limite_entrega,
            "tipo entrega" => $tipo_entrega,
            "es grupal" => $es_grupal,
            "id contenido" => $id_contenido,
            "id taller" => $id_taller
        ]);

        // Paso 2: Consultar datos actuales del taller
        $conexion = MainModel::conectar();
        $consulta = $conexion->prepare("SELECT * FROM taller WHERE id_taller = :id_taller");
        $consulta->bindParam(":id_taller", $id_taller);
        $consulta->execute();

        if ($consulta->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "taller no encontrado", "No se encontró un taller con el ID proporcionado.", "warning");
        }

        $datosActuales = $consulta->fetch(PDO::FETCH_ASSOC);

        // Paso 3: Archivos existentes
        $archivos_guardados = json_decode($datosActuales['archivos_adjuntos'], true);
        if (!is_array($archivos_guardados)) $archivos_guardados = [];

        // Paso 4: Procesar nuevos archivos (si los hay)
        $archivos = $_FILES['archivos'] ?? null;
        $archivos_nuevos = [];

        if (isset($archivos['name']) && is_array($archivos['name']) && count($archivos['name']) > 0) {
            $ruta_base = __DIR__ . '/../views/resources';
            $ruta_destino = "$ruta_base/$codigo_institucion/sedes/$id_sede/imagenes/temas_educativos/$documento_profesor/$id_tema/archivos";

            if (!file_exists($ruta_destino)) mkdir($ruta_destino, 0775, true);

            foreach ($archivos['tmp_name'] as $index => $temporal) {
                $nombre_original = $archivos['name'][$index];
                $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
                $nombre_sin_ext = pathinfo($nombre_original, PATHINFO_FILENAME);
                $nuevo_nombre = $nombre_sin_ext . '__taller.' . $extension;
                $ruta_archivo_final = $ruta_destino . '/' . $nuevo_nombre;

                if (move_uploaded_file($temporal, $ruta_archivo_final)) {
                    $archivos_nuevos[] = $nuevo_nombre;
                }
            }
        }

        // Paso 5: Fusionar archivos
        $archivos_actualizados = array_values(array_unique(array_merge($archivos_guardados, $archivos_nuevos)));
        $archivos_json = count($archivos_actualizados) > 0
            ? json_encode($archivos_actualizados, JSON_UNESCAPED_UNICODE)
            : null;

        // Paso 6: Comparar si hay diferencias
        $cambios = (
            $titulo_tarea !== $datosActuales['titulo_tarea'] ||
            $descripcion_tarea !== $datosActuales['descripcion'] ||
            $fecha_entrega !== $datosActuales['fecha_entrega'] ||
            $fecha_inicio !== $datosActuales['fecha_inicio'] ||
            $fecha_limite_entrega !== $datosActuales['fecha_limite_entrega'] ||
            $recordarme_calificar !== $datosActuales['recordarme_calificar'] ||
            $tipo_entrega !== $datosActuales['tipo_entrega'] ||
            $tipo_archivo_seleccionado !== $datosActuales['tipo_archivo_entrega'] ||
            $es_grupal != $datosActuales['es_grupal'] ||
            json_encode($archivos_guardados) !== json_encode($archivos_actualizados)
        );

        if (!$cambios) {
            MainModel::jsonResponse("simple", "Sin cambios", "No se realizaron cambios en el taller.", "info");
        }

        // Paso 7: Actualizar solo si hubo cambios
        $sql = $conexion->prepare("
        UPDATE taller SET 
            titulo_tarea = :titulo_tarea,
            descripcion = :descripcion,
            fecha_inicio = :fecha_inicio,
            fecha_entrega = :fecha_entrega,
            fecha_limite_entrega = :fecha_limite_entrega,
            recordarme_calificar = :recordarme_calificar,
            tipo_entrega = :tipo_entrega,
            tipo_archivo_entrega = :tipo_archivo_entrega,
            archivos_adjuntos = :archivos_adjuntos,
            es_grupal = :es_grupal
        WHERE id_taller = :id_taller
        ");

        $sql->bindParam(':titulo_tarea', $titulo_tarea);
        $sql->bindParam(':descripcion', $descripcion_tarea);
        $sql->bindParam(':fecha_inicio', $fecha_inicio);
        $sql->bindParam(':fecha_entrega', $fecha_entrega);
        $sql->bindParam(':fecha_limite_entrega', $fecha_limite_entrega);
        $sql->bindParam(':recordarme_calificar', $recordarme_calificar);
        $sql->bindParam(':tipo_entrega', $tipo_entrega);
        $sql->bindParam(':tipo_archivo_entrega', $tipo_archivo_seleccionado);
        $sql->bindValue(':archivos_adjuntos', $archivos_json, is_null($archivos_json) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $sql->bindParam(':es_grupal', $es_grupal, PDO::PARAM_BOOL);
        $sql->bindParam(':id_taller', $id_taller);

        if ($sql->execute()) {

            // actualizar el contenido del tema si es necesario
          
                $sql_contenido = $conexion->prepare("UPDATE contenidos_tema SET titulo = :titulo  WHERE id_contenido = :id_contenido");
                $sql_contenido->bindParam(':titulo', $titulo_tarea);
                $sql_contenido->bindParam(':id_contenido', $id_contenido);
                $sql_contenido->execute();
                // Verificar si la actualización del contenido fue exitosa
                if ($sql_contenido->rowCount() <= 0) {
                      MainModel::jsonResponse("simple", "¡Éxito!", "Taller actualizado correctamente.", "success");
                }  

            MainModel::jsonResponse("simple", "¡Éxito!", "Taller actualizado correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar el taller.", "error");
        }
    }

     public static function eliminar_taller_educativas_controlador($datos)
    {
        $id_contenido = MainModel::limpiar_cadenas($datos['id_contenido']);
        $id_tema = MainModel::limpiar_cadenas($datos['id_tema']);
        $documento_profesor = MainModel::limpiar_cadenas($datos['documento_profesor']);
        $id_sede = MainModel::decryption(MainModel::limpiar_cadenas($datos['id_sede']));
        $codigo_institucion = MainModel::decryption(MainModel::limpiar_cadenas($datos['codigo_institucion']));

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'id contenido' => $id_contenido,
            'documento docente' => $documento_profesor,
            'id sede' => $id_sede,
            'institución' => $codigo_institucion,
            'id tema' => $id_tema
        ]);

        $ruta_base = __DIR__ . '/../views/resources';
        $ruta_destino = "$ruta_base/$codigo_institucion/sedes/$id_sede/imagenes/temas_educativos/$documento_profesor/$id_tema/archivos";

        // Verificar que el contenido exista y le pertenezca al docente
        $consulta = MainModel::ejecutar_consultas_simples("
        SELECT id_contenido FROM contenidos_tema 
        WHERE id_contenido = '$id_contenido' 
        AND creado_por = '$documento_profesor'
        ");

        if ($consulta->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El contenido que deseas eliminar no existe o no pertenece a este docente.", "warning");
        }

        try {
            $conexion = MainModel::conectar();
            $conexion->beginTransaction();

            // 🔹 1. Obtener tareas con archivos para eliminar físicamente
            $consulta_tareas = $conexion->prepare("SELECT archivos_adjuntos FROM taller WHERE id_contenido = :id_contenido");
            $consulta_tareas->bindParam(":id_contenido", $id_contenido);
            $consulta_tareas->execute();

            while ($fila = $consulta_tareas->fetch(PDO::FETCH_ASSOC)) {
                $archivos = json_decode($fila['archivos_adjuntos'], true);
                if (is_array($archivos)) {
                    foreach ($archivos as $archivo) {
                        $ruta_archivo = $ruta_destino . '/' . $archivo;
                        if (file_exists($ruta_archivo)) {
                            unlink($ruta_archivo);
                        }
                    }
                }
            }

            // 🔹 2. Eliminar los talleres de la base de datos
            $sql1 = $conexion->prepare("DELETE FROM taller WHERE id_contenido = :id_contenido");
            $sql1->bindParam(":id_contenido", $id_contenido);
            $sql1->execute();

           
            // 🔹 4. Eliminar el contenido
            $sql3 = $conexion->prepare("DELETE FROM contenidos_tema 
            WHERE id_contenido = :id_contenido AND creado_por = :creado_por");
            $sql3->bindParam(":id_contenido", $id_contenido);
            $sql3->bindParam(":creado_por", $documento_profesor);
            $sql3->execute();

            $conexion->commit();

            MainModel::jsonResponse("simple", "Contenido eliminado", "El contenido, el taller asociados y sus archivos fueron eliminados correctamente.", "success");
        } catch (Exception $e) {
            $conexion->rollBack();
            MainModel::jsonResponse("simple", "Error crítico", "Ocurrió un error al intentar eliminar el contenido: " . $e->getMessage(), "error");
        }
    }




}
