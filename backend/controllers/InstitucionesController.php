<?php

require_once __DIR__ . '/../models/InstitucionesModelo.php';



class InstitucionesController extends InstitucionesModelo
{

    public static function extraer_instituciones_controlador()
    {

        $extraer_instituciones = MainModel::ejecutar_consultas_simples("SELECT * FROM instituciones WHERE estado_institucion = 1");

        $instituciones = [];

        while ($fila = $extraer_instituciones->fetch(PDO::FETCH_ASSOC)) {
            $fila['codigo_institucion'] = MainModel::encryption($fila['codigo_institucion']);
            $instituciones[] = $fila;
        }

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($instituciones);
    }

    public static function extraer_sedes_por_institucion_controlador($codigo_encriptado)
    {
        $codigo = MainModel::limpiar_cadenas($codigo_encriptado);
        $codigo = MainModel::decryption($codigo_encriptado); // Si viene encriptado
        $query = "SELECT * FROM sedes WHERE codigo_institucion = '$codigo'";
        $stmt = MainModel::ejecutar_consultas_simples($query);

        $sedes = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fila['id_sede'] = MainModel::encryption($fila['id_sede']);
            $sedes[] = $fila;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($sedes);
    }

    public static function extraer_sexos_aplicacion_controlador()
    {
        // Ejecutamos la consulta de todos los sexos
        $extraer_sexos = MainModel::ejecutar_consultas_simples("SELECT * FROM sexos");

        $sexos = [];

        while ($fila = $extraer_sexos->fetch(PDO::FETCH_ASSOC)) {
            // Si necesitas encriptar el ID, hazlo así. Si no, elimina esta línea.
            $fila['id_sexo'] = MainModel::encryption($fila['id_sexo']);

            $sexos[] = $fila;
        }
        // Establecemos cabecera para devolver JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($sexos);
    }

    public static function extraer_estados_aplicacion_controlador()
    {
        // Ejecutamos la consulta de todos los sexos
        $extraer_estados = MainModel::ejecutar_consultas_simples("SELECT * FROM estados");

        $estados = [];

        while ($fila = $extraer_estados->fetch(PDO::FETCH_ASSOC)) {
            // Si necesitas encriptar el ID, hazlo así. Si no, elimina esta línea.
            $fila['id_estado'] = MainModel::encryption($fila['id_estado']);
            $estados[] = $fila;
        }
        // Establecemos cabecera para devolver JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($estados);
    }

    public static function extraer_roles_aplicacion_controlador()
    {
        // Ejecutamos la consulta de todos los sexos
        $extraer_roles = MainModel::ejecutar_consultas_simples("SELECT * FROM roles WHERE id_rol != 1");

        $roles = [];

        while ($fila = $extraer_roles->fetch(PDO::FETCH_ASSOC)) {
            // Si necesitas encriptar el ID, hazlo así. Si no, elimina esta línea.
            $fila['id_rol'] = MainModel::encryption($fila['id_rol']);
            $roles[] = $fila;
        }
        // Establecemos cabecera para devolver JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($roles);
    }


    public static function extraer_grado_sede_aplicacion_controlador($id_sede, $codigo_institucion)
    {
        // Sanitizar parámetros
        $codigo_institucion = MainModel::limpiar_cadenas($codigo_institucion);
        $id_sede = MainModel::limpiar_cadenas($id_sede);

        // Desencriptar parámetros
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);

        // Consulta SQL corregida (uso de un solo igual en condiciones)
        $consulta = "SELECT id_grado, nombre_grado FROM grados WHERE id_sede = '$id_sede' AND codigo_institucion = '$codigo_institucion'";
        $extraer_grados = MainModel::ejecutar_consultas_simples($consulta);

        $grados = [];

        while ($fila = $extraer_grados->fetch(PDO::FETCH_ASSOC)) {
            // Si deseas encriptar los ID puedes hacerlo aquí (opcional)
            $fila['id_grado'] = MainModel::encryption($fila['id_grado']);
            $grados[] = $fila;
        }

        // Devolver como JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($grados);
    }

    public static function consultar_grupos_grados_controlador($id_grado)
    {
        $id_grado = MainModel::limpiar_cadenas($id_grado);
        $id_grado = MainModel::decryption($id_grado);
        $consulta = "SELECT id_grupo,nombre_grupo FROM grupos WHERE id_grado = '$id_grado'";
        $extraer  = MainModel::ejecutar_consultas_simples($consulta);
        $grupos = [];

        while ($fila = $extraer->fetch(PDO::FETCH_ASSOC)) {
            $fila['id_grupo'] = MainModel::encryption($fila['id_grupo']);
            $grupos[] = $fila;
        }
        // Devolver como JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($grupos);
    }

    /****************materias academicas controlador*********** */

    public static function registrar_materias_academicas_controlador($datos)
    {
        $NombreMaterias = MainModel::limpiar_cadenas($datos['materia']);
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion']);
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede']);

        MainModel::validar_campos_obligatorios([
            'Nombre de la materia' => $NombreMaterias,
            'Institución' => $codigo_institucion,
            'Sede' => $id_sede
        ]);

        // Validar existencia de sede/institución
        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, null);

        // Verificar si ya existe la materia en la misma sede e institución
        $check = MainModel::ejecutar_consultas_simples("SELECT nombre_materia FROM materias WHERE nombre_materia = '$NombreMaterias' AND codigo_institucion = '$codigo_institucion' AND id_sede = '$id_sede'");
        if ($check->rowCount() > 0) {
            MainModel::jsonResponse("simple", "Materia duplicada", "Ya existe una materia con ese nombre en esta sede.", "warning");
        }

        // Insertar nueva materia
        $materia_guardada = InstitucionesModelo::guardar_materia([
            'nombre_materia' => $NombreMaterias,
            'codigo_institucion' => $codigo_institucion,
            'id_sede' => $id_sede,
            'fecha_creacion' => date("Y-m-d H:i:s"),
            'estado' => 1
        ]);

        if ($materia_guardada->rowCount() > 0) {
            MainModel::jsonResponse("simple", "Materia registrada", "La materia se registró correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "Ocurrió un error al registrar la materia.", "error");
        }
    }

    public static function extraer_materias_por_sede_controlador($id_sede, $codigo_institucion)
    {

        $id_sede = MainModel::limpiar_cadenas($id_sede);
        $codigo_institucion = MainModel::limpiar_cadenas($codigo_institucion);


        $query = "SELECT * FROM materias WHERE id_sede = :id_sede AND codigo_institucion = :codigo_institucion order by nombre_materia ASC";
        $stmt = MainModel::conectar()->prepare($query);
        $stmt->bindParam(':id_sede', $id_sede);
        $stmt->bindParam(':codigo_institucion', $codigo_institucion);
        $stmt->execute();

        $materias = [];

        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fila['id_materia'] = MainModel::encryption($fila['id_materia']);
            $fila['codigo_institucion'] = MainModel::encryption($fila['codigo_institucion']);
            $fila['id_sede'] = MainModel::encryption($fila['id_sede']);
            $materias[] = $fila;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($materias);
    }

    public static function elimina_materias_controlador($datos)
    {
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion']);
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede']);
        $id_materia = MainModel::limpiar_cadenas($datos['id_materia']);
        MainModel::validar_campos_obligatorios([
            'id_materia' => $id_materia,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);
        $id_materia = MainModel::decryption($id_materia);
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);

        // Verificar que exista la materia
        $check = MainModel::conectar()->prepare("SELECT * FROM materias WHERE id_materia = :id");
        $check->bindParam(":id", $id_materia, PDO::PARAM_INT);
        $check->execute();

        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrada", "La materia que intentas eliminar no existe o ya fue eliminada.", "warning");
        }

        // Eliminar la materia
        $delete = MainModel::conectar()->prepare("DELETE FROM materias WHERE id_materia = :id");
        $delete->bindParam(":id", $id_materia, PDO::PARAM_INT);

        if ($delete->execute()) {
            MainModel::jsonResponse("simple", "Materia eliminada", "La materia fue eliminada exitosamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "Ocurrió un error al intentar eliminar la materia.", "error");
        }
    }

    public static function editar_materias_controlador($datos)
    {
        // Limpiar entradas
        $nombre_materia = MainModel::limpiar_cadenas($datos['nombre_materia']);
        $id_materia = MainModel::limpiar_cadenas($datos['id_materia']);
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion']);
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede']);

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'id_materia' => $id_materia,
            'nombre de la materia' => $nombre_materia,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        // Desencriptar
        $id_materia = MainModel::decryption($id_materia);
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);

        // Verificar existencia
        $check = MainModel::conectar()->prepare("SELECT * FROM materias WHERE id_materia = :id");
        $check->bindParam(":id", $id_materia, PDO::PARAM_INT);
        $check->execute();

        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "La materia que intentas editar no existe.", "warning");
        }

        $materia_actual = $check->fetch(PDO::FETCH_ASSOC)['nombre_materia'];

        // Comparar si el nombre es igual
        if (strcasecmp(trim($materia_actual), trim($nombre_materia)) === 0) {
            MainModel::jsonResponse("simple", "Sin cambios", "No realizaste ningún cambio en el nombre de la materia.", "info");
        }

        // Actualizar materia
        $actualizar = MainModel::conectar()->prepare("
        UPDATE materias 
        SET nombre_materia = :nombre 
        WHERE id_materia = :id 
        AND codigo_institucion = :codigo 
        AND id_sede = :sede
     ");

        $actualizar->bindParam(":nombre", $nombre_materia, PDO::PARAM_STR);
        $actualizar->bindParam(":id", $id_materia, PDO::PARAM_INT);
        $actualizar->bindParam(":codigo", $codigo_institucion, PDO::PARAM_STR);
        $actualizar->bindParam(":sede", $id_sede, PDO::PARAM_INT);

        if ($actualizar->execute()) {
            MainModel::jsonResponse("simple", "Materia actualizada", "El nombre de la materia se actualizó correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar el nombre de la materia.", "error");
        }
    }

    /****************grados academico controlador*********** */

    public static function registrar_grados_academicas_controlador($datos)
    {
        $NombreGrado = MainModel::limpiar_cadenas($datos['grado']);
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion']);
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede']);

        MainModel::validar_campos_obligatorios([
            'Nombre del grado' => $NombreGrado,
            'Institución' => $codigo_institucion,
            'Sede' => $id_sede
        ]);

        // Validar existencia de sede/institución
        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, null);

        // Verificar si ya existe la materia en la misma sede e institución
        $check = MainModel::ejecutar_consultas_simples("SELECT nombre_grado FROM grados WHERE nombre_grado = '$NombreGrado' AND codigo_institucion = '$codigo_institucion' AND id_sede = '$id_sede'");
        if ($check->rowCount() > 0) {
            MainModel::jsonResponse("simple", "Grado académico duplicado", "Ya existe una grado académico con ese nombre en esta sede.", "warning");
        }

        // Insertar nueva materia
        $materia_guardada = InstitucionesModelo::guardar_grados([
            'nombre_grado' => $NombreGrado,
            'codigo_institucion' => $codigo_institucion,
            'id_sede' => $id_sede,
        ]);

        if ($materia_guardada->rowCount() > 0) {
            MainModel::jsonResponse("simple", "Grado académico registrado", "El grado académico se registró correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "Ocurrió un error al registrar el grado académico.", "error");
        }
    }

    public static function extraer_grado_por_sede_controlador($id_sede, $codigo_institucion)
    {

        $id_sede = MainModel::limpiar_cadenas($id_sede);
        $codigo_institucion = MainModel::limpiar_cadenas($codigo_institucion);


        $query = "SELECT * FROM grados WHERE id_sede = :id_sede AND codigo_institucion = :codigo_institucion ";
        $stmt = MainModel::conectar()->prepare($query);
        $stmt->bindParam(':id_sede', $id_sede);
        $stmt->bindParam(':codigo_institucion', $codigo_institucion);
        $stmt->execute();

        $grados = [];

        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fila['id_grado'] = MainModel::encryption($fila['id_grado']);
            $fila['codigo_institucion'] = MainModel::encryption($fila['codigo_institucion']);
            $fila['id_sede'] = MainModel::encryption($fila['id_sede']);
            $grados[] = $fila;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($grados);
    }

    public static function elimina_grado_controlador($datos)
    {
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion']);
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede']);
        $id_grado = MainModel::limpiar_cadenas($datos['id_grado']);

        MainModel::validar_campos_obligatorios([
            'id_grado' => $id_grado,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        $id_grado = MainModel::decryption($id_grado);
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);

        // Verificar que exista el grado
        $check = MainModel::conectar()->prepare("SELECT * FROM grados WHERE id_grado = :id");
        $check->bindParam(":id", $id_grado, PDO::PARAM_INT);
        $check->execute();

        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El grado académico que intentas eliminar no existe o ya fue eliminado.", "warning");
        }

        // Verificar si existen grupos asociados al grado
        $consultaGrupos = MainModel::conectar()->prepare("SELECT COUNT(*) FROM grupos WHERE id_grado = :id");
        $consultaGrupos->bindParam(":id", $id_grado, PDO::PARAM_INT);
        $consultaGrupos->execute();
        $totalGrupos = $consultaGrupos->fetchColumn();

        if ($totalGrupos > 0) {
            MainModel::jsonResponse("simple", "Acción no permitida", "No puedes eliminar este grado porque tiene grupos asociados.", "warning");
        }

        // Eliminar el grado
        $delete = MainModel::conectar()->prepare("DELETE FROM grados WHERE id_grado = :id");
        $delete->bindParam(":id", $id_grado, PDO::PARAM_INT);

        if ($delete->execute()) {
            MainModel::jsonResponse("simple", "Grado académico eliminado", "El grado fue eliminado exitosamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "Ocurrió un error al intentar eliminar el grado académico.", "error");
        }
    }

    public static function editar_grado_controlador($datos)
    {
        // Limpiar entradas
        $nombre_grado = MainModel::limpiar_cadenas($datos['nombre_grado']);
        $id_grado = MainModel::limpiar_cadenas($datos['id_grado']);
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion']);
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede']);

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'id_grado' => $id_grado,
            'nombre del grado' => $nombre_grado,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        // Desencriptar
        $id_grado = MainModel::decryption($id_grado);
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);

        // Verificar existencia
        $check = MainModel::conectar()->prepare("SELECT * FROM grados WHERE id_grado = :id");
        $check->bindParam(":id", $id_grado, PDO::PARAM_INT);
        $check->execute();

        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El grado academico que intentas editar no existe.", "warning");
        }

        $grado_actual = $check->fetch(PDO::FETCH_ASSOC)['nombre_grado'];

        // Comparar si el nombre es igual
        if (strcasecmp(trim($grado_actual), trim($nombre_grado)) === 0) {
            MainModel::jsonResponse("simple", "Sin cambios", "No realizaste ningún cambio en el nombre del grado academico.", "info");
        }

        // Actualizar materia
        $actualizar = MainModel::conectar()->prepare("
        UPDATE grados 
        SET nombre_grado = :nombre 
        WHERE id_grado = :id 
        AND codigo_institucion = :codigo 
        AND id_sede = :sede
     ");

        $actualizar->bindParam(":nombre", $nombre_grado, PDO::PARAM_STR);
        $actualizar->bindParam(":id", $id_grado, PDO::PARAM_INT);
        $actualizar->bindParam(":codigo", $codigo_institucion, PDO::PARAM_STR);
        $actualizar->bindParam(":sede", $id_sede, PDO::PARAM_INT);

        if ($actualizar->execute()) {
            MainModel::jsonResponse("simple", "grado actualizado", "El nombre del grado academico se actualizó correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar el nombre del grado academico.", "error");
        }
    }

    public static function obtener_grupos_por_grado_controlador($id_grado)
    {
        $id_grado = MainModel::decryption(MainModel::limpiar_cadenas($id_grado));

        $consulta = MainModel::conectar()->prepare("SELECT * FROM grupos WHERE id_grado = :id");
        $consulta->bindParam(":id", $id_grado, PDO::PARAM_INT);
        $consulta->execute();

        $grupos = $consulta->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($grupos);
    }

    public static function registrar_grupo_controlador($datos)
    {
        $grupos = json_decode($datos['grupos'], true);

        $mensajes = [];

        if (!is_array($grupos) || empty($grupos)) {
            MainModel::jsonResponse("simple", "Error", "No se recibieron grupos válidos para registrar.", "error");
        }

        // Validar todos antes de registrar
        foreach ($grupos as $index => $grupo) {
            $errores = [];

            // Validar existencia de campos
            if (!isset($grupo['nombre_grupo'], $grupo['cantidad'], $grupo['id_grado'])) {
                $mensajes[$index][] = "Faltan campos requeridos.";
                continue;
            }

            // Limpiar y validar campos
            $nombre_grupo = MainModel::limpiar_cadenas($grupo['nombre_grupo']);
            $cantidad = (int) MainModel::limpiar_cadenas($grupo['cantidad']);
            $id_grado = MainModel::decryption(MainModel::limpiar_cadenas($grupo['id_grado']));

            if (empty($nombre_grupo)) {
                $errores[] = "El nombre del grupo está vacío.";
            }

            if ($cantidad <= 0) {
                $errores[] = "La cantidad debe ser mayor a 0.";
            }

            if (!$id_grado || !is_numeric($id_grado)) {
                $errores[] = "ID del grado inválido.";
            }

            // Verificar que el grado exista
            $checkGrado = MainModel::conectar()->prepare("SELECT id_grado FROM grados WHERE id_grado = :id");
            $checkGrado->bindParam(":id", $id_grado, PDO::PARAM_INT);
            $checkGrado->execute();

            if ($checkGrado->rowCount() == 0) {
                $errores[] = "El grado académico no existe.";
            }

            // Verificar si el grupo ya existe para ese grado
            $checkGrupo = MainModel::conectar()->prepare("SELECT id_grupo FROM grupos WHERE id_grado = :id AND nombre_grupo = :nombre");
            $checkGrupo->bindParam(":id", $id_grado, PDO::PARAM_INT);
            $checkGrupo->bindParam(":nombre", $nombre_grupo, PDO::PARAM_STR);
            $checkGrupo->execute();

            if ($checkGrupo->rowCount() > 0) {
                $errores[] = "Ya existe un grupo con ese nombre en este grado.";
            }

            if (!empty($errores)) {
                $mensajes[$index] = $errores;
            }
        }

        // Si hay errores, devolverlos sin insertar
        if (!empty($mensajes)) {
            MainModel::jsonResponse("detallado", "Errores detectados", $mensajes, "error");
            return;
        }

        // Si todo está bien, ahora insertar
        foreach ($grupos as $grupo) {
            $nombre_grupo = MainModel::limpiar_cadenas($grupo['nombre_grupo']);
            $cantidad = (int) MainModel::limpiar_cadenas($grupo['cantidad']);
            $id_grado = MainModel::decryption(MainModel::limpiar_cadenas($grupo['id_grado']));

            $insertar = MainModel::conectar()->prepare("INSERT INTO grupos(nombre_grupo, cantidad, id_grado) VALUES (:nombre, :cantidad, :id_grado)");
            $insertar->bindParam(":nombre", $nombre_grupo, PDO::PARAM_STR);
            $insertar->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
            $insertar->bindParam(":id_grado", $id_grado, PDO::PARAM_INT);
            $insertar->execute();
        }

        MainModel::jsonResponse("simple", "Grupos registrados", "Todos los grupos fueron registrados correctamente.", "success");
    }

    public static function eliminar_grupo_controlador($datos)
    {

        $grupo = json_decode($datos['grupos'], true);

        if (!is_array($grupo)) {
            MainModel::jsonResponse("simple", "Error", "No se recibió información válida para eliminar.", "error");
        }

        $id_encriptado = $grupo['id_grupo'] ?? null;

        if (empty($id_encriptado)) {
            MainModel::jsonResponse("simple", "Error", "ID del grupo no válido.", "warning");
        }


        $id_grupo = MainModel::limpiar_cadenas($id_encriptado);

        $check = MainModel::conectar()->prepare("SELECT * FROM grupos WHERE id_grupo = :id");
        $check->bindParam(":id", $id_grupo, PDO::PARAM_INT);
        $check->execute();

        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Grupo no encontrado", "El grupo no existe o ya fue eliminado.", "warning");
        }

        $delete = MainModel::conectar()->prepare("DELETE FROM grupos WHERE id_grupo = :id");
        $delete->bindParam(":id", $id_grupo, PDO::PARAM_INT);

        if ($delete->execute()) {
            MainModel::jsonResponse("simple", "Grupo eliminado", "El grupo fue eliminado correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo eliminar el grupo.", "error");
        }
    }

    public static function editar_grupo_controlador($datos)
    {
        // Decodificar los datos JSON recibidos
        $grupo = json_decode($datos['grupos'], true);

        // Verificar estructura del arreglo
        if (!is_array($grupo)) {
            MainModel::jsonResponse("simple", "Error", "No se recibió información válida para editar.", "error");
        }

        // Validaciones básicas
        $id_encriptado     = $grupo['id_grupo']         ?? null;
        $nombre_grupo      = $grupo['nombre_grupo']     ?? '';
        $cantidad          = $grupo['cantidad']         ?? '';
        $id_grado_encriptado = $grupo['id_grado']       ?? null;

        $errores = [];

        if (empty($id_encriptado))       $errores[] = "ID del grupo no es válido.";
        if (empty($nombre_grupo))        $errores[] = "Nombre del grupo no puede estar vacío.";
        if (!is_numeric($cantidad) || $cantidad <= 0) $errores[] = "Cantidad no válida.";


        if (!empty($errores)) {
            MainModel::jsonResponse("simple", "Errores de validación", implode(" | ", $errores), "warning");
        }

        // Desencriptar valores
        $id_grupo  = MainModel::limpiar_cadenas($id_encriptado);


        $nombre_grupo = MainModel::limpiar_cadenas($nombre_grupo);
        $cantidad = MainModel::limpiar_cadenas($cantidad);

        // Verificar que exista el grupo
        $check = MainModel::conectar()->prepare("SELECT * FROM grupos WHERE id_grupo = :id");
        $check->bindParam(":id", $id_grupo, PDO::PARAM_INT);
        $check->execute();

        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El grupo que intentas editar no existe.", "warning");
        }

        // Validar que no exista otro grupo con el mismo nombre en ese grado (excluyendo el actual)
        $validarNombre = MainModel::conectar()->prepare("
            SELECT * FROM grupos 
            WHERE nombre_grupo = :nombre AND id_grado = :grado AND id_grupo != :id
        ");
        $validarNombre->bindParam(":nombre", $nombre_grupo, PDO::PARAM_STR);
        $validarNombre->bindParam(":grado", $id_grado, PDO::PARAM_INT);
        $validarNombre->bindParam(":id", $id_grupo, PDO::PARAM_INT);
        $validarNombre->execute();

        if ($validarNombre->rowCount() > 0) {
            MainModel::jsonResponse("simple", "Ya existe", "Ya hay un grupo con ese nombre en este grado.", "warning");
        }

        // Ejecutar la actualización
        $update = MainModel::conectar()->prepare("
            UPDATE grupos SET nombre_grupo = :nombre, cantidad = :cantidad WHERE id_grupo = :id
        ");
        $update->bindParam(":nombre", $nombre_grupo, PDO::PARAM_STR);
        $update->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
        $update->bindParam(":id", $id_grupo, PDO::PARAM_INT);

        if ($update->execute()) {
            MainModel::jsonResponse("simple", "Grupo actualizado", "La información fue modificada correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar el grupo.", "error");
        }
    }

    public static function registrar_materias_por_grado_controlador($datos)
    {
        $json_string = $datos['datos'] ?? null;

        if (!$json_string || !is_string($json_string)) {
            MainModel::jsonResponse("simple", "Error", "No se recibió el contenido esperado en 'datos'.", "error");
        }

        $datos_decodificados = json_decode($json_string, true);

        if (!is_array($datos_decodificados)) {
            MainModel::jsonResponse("simple", "Error", "No se pudo decodificar el JSON de 'datos'.", "error");
        }

        foreach ($datos_decodificados as $index => $item) {

            if (
                !isset($item['id_grado']) || !isset($item['materias_json']) ||
                !isset($item['codigo_institucion']) || !isset($item['id_sede'])
            ) {
                MainModel::jsonResponse("simple", "Datos incompletos", "Faltan campos en el grupo $index.", "warning");
            }

            $id_grado = MainModel::limpiar_cadenas($item['id_grado']);
            $id_grado = MainModel::decryption($id_grado);
            $codigo_institucion = MainModel::limpiar_cadenas($item['codigo_institucion']);
            $id_sede = MainModel::limpiar_cadenas($item['id_sede']);
            $materias_array = $item['materias_json'];

            if (!is_array($materias_array) || empty($materias_array)) {
                MainModel::jsonResponse("simple", "Error de materias", "Las materias del grupo $index están vacías o mal formateadas.", "warning");
            }

            // 🔓 Desencriptar y limpiar materias
            $materias_limpias = [];
            foreach ($materias_array as $materia_codificada) {
                $materia_descifrada = MainModel::decryption($materia_codificada);
                $materia_limpia = MainModel::limpiar_cadenas($materia_descifrada);
                if (!empty($materia_limpia)) {
                    $materias_limpias[] = $materia_limpia;
                }
            }

            if (empty($materias_limpias)) {
                MainModel::jsonResponse("simple", "Materias vacías", "Después de desencriptar, no hay materias válidas en el grupo $index.", "warning");
            }

            MainModel::validar_campos_obligatorios([
                "Grado" => $id_grado,
                "Institución" => $codigo_institucion,
                "Sede" => $id_sede
            ]);

            MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, null);

            $check = MainModel::ejecutar_consultas_simples("
            SELECT id FROM materias_por_grado 
            WHERE id_grado = '$id_grado' 
              AND codigo_institucion = '$codigo_institucion' 
              AND id_sede = '$id_sede'
        ");

            if ($check->rowCount() > 0) {
                MainModel::jsonResponse("simple", "Registro duplicado", "Ya existen materias registradas para este grado en la sede seleccionada.", "warning");
            }

            $materias_json_str = json_encode($materias_limpias, JSON_UNESCAPED_UNICODE);

            // 🔒 Escapar datos manualmente
            $materias_json_str = addslashes($materias_json_str);
            $id_grado = addslashes($id_grado);
            $codigo_institucion = addslashes($codigo_institucion);
            $id_sede = addslashes($id_sede);

            $insert = MainModel::ejecutar_consultas_simples("
            INSERT INTO materias_por_grado (id_grado, materias_json, codigo_institucion, id_sede)
            VALUES ('$id_grado', '$materias_json_str', '$codigo_institucion', '$id_sede')
        ");

            if (!$insert || $insert->rowCount() <= 0) {
                MainModel::jsonResponse("simple", "Error", "No se pudieron registrar las materias del grupo $index.", "error");
            }
        }

        MainModel::jsonResponse("recargar", "Materias registradas", "Todas las materias fueron registradas correctamente.", "success");
    }

    public static function consultar_todas_materias_por_grado_controlador()
{
    $consulta = "SELECT id, id_grado, materias_json, codigo_institucion, id_sede FROM materias_por_grado";
    $resultados = MainModel::ejecutar_consultas_simples($consulta);

    $respuesta = [];

    while ($fila = $resultados->fetch(PDO::FETCH_ASSOC)) {
        $materias_ids = json_decode($fila['materias_json'], true);
        $codigo_institucion = $fila['codigo_institucion'];
        $id_sede = $fila['id_sede'];
        $id_grado = $fila['id_grado'];

        // 🔍 Obtener el nombre del grado
        $consulta_nombre_grado = "
            SELECT nombre_grado 
            FROM grados 
            WHERE id_grado = '$id_grado' 
              AND codigo_institucion = '$codigo_institucion'
              AND id_sede = '$id_sede'
            LIMIT 1
        ";
        $nombre_grado_resultado = MainModel::ejecutar_consultas_simples($consulta_nombre_grado);
        $nombre_grado = '';

        if ($nombre_grado_resultado->rowCount() > 0) {
            $nombre_grado = $nombre_grado_resultado->fetchColumn();
        }

        // Validar que el arreglo sea válido
        if (!is_array($materias_ids) || empty($materias_ids)) {
            continue;
        }

        // Convertir a enteros y evitar inyecciones
        $ids = array_map('intval', $materias_ids);
        $ids_in = implode(',', $ids);

        // Consultar los nombres de las materias
        $consulta_materias = "
            SELECT id_materia, nombre_materia 
            FROM materias 
            WHERE id_materia IN ($ids_in) 
              AND codigo_institucion = '$codigo_institucion'
              AND id_sede = '$id_sede'
              AND estado = 1
        ";
        $materias_query = MainModel::ejecutar_consultas_simples($consulta_materias);

        $materias_nombre = [];
        while ($m = $materias_query->fetch(PDO::FETCH_ASSOC)) {
            $materias_nombre[] = [
                "id_materia" => MainModel::encryption($m['id_materia']),
                "nombre_materia" => $m['nombre_materia']
            ];
        }

        // Agregar al resultado final
        $respuesta[] = [
            "id" => $fila['id'],
            "id_grado" => $id_grado,
            "nombre_grado" => $nombre_grado,
            "codigo_institucion" => $codigo_institucion,
            "id_sede" => $id_sede,
            "materias" => $materias_nombre
        ];
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
}


    public static function eliminar_materia_de_grado_controlador($datos)
    {

        $id_registro = MainModel::limpiar_cadenas($datos['id_registro'] ?? null);
        $id_materia  = MainModel::limpiar_cadenas($datos['id_materia'] ?? null);
        $id_materia  = MainModel::decryption($id_materia);

        if (!$id_registro || !$id_materia || !is_numeric($id_materia)) {
            MainModel::jsonResponse("simple", "Datos inválidos", "Faltan datos o el ID de materia no es válido.", "warning");
        }

        // 2. Consultar el registro actual
        $consulta = "SELECT materias_json FROM materias_por_grado WHERE id = '$id_registro' LIMIT 1";
        $resultado = MainModel::ejecutar_consultas_simples($consulta);

        if ($resultado->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "No se encontró el registro de grado.", "error");
        }

        $fila = $resultado->fetch(PDO::FETCH_ASSOC);
        $materias = json_decode($fila['materias_json'], true);

        if (!is_array($materias)) {
            MainModel::jsonResponse("simple", "Error", "El formato del campo materias_json es inválido.", "error");
        }

        // 3. Eliminar la materia específica
        $materias_filtradas = array_filter($materias, function ($m) use ($id_materia) {
            return intval($m) !== intval($id_materia);
        });

        if (count($materias) === count($materias_filtradas)) {
            MainModel::jsonResponse("simple", "Materia no encontrada", "La materia indicada no está asociada al grado.", "info");
        }

        // 4. Guardar el nuevo JSON
        $materias_json_actualizado = json_encode(array_values($materias_filtradas), JSON_UNESCAPED_UNICODE);
        $materias_json_actualizado = addslashes($materias_json_actualizado);

        $update = MainModel::ejecutar_consultas_simples("
        UPDATE materias_por_grado 
        SET materias_json = '$materias_json_actualizado' 
        WHERE id = '$id_registro'
        ");

        if ($update && $update->rowCount() > 0) {
            MainModel::jsonResponse("simple", "Materia eliminada", "La materia fue eliminada correctamente del grado.", "success");
        } else {
            MainModel::jsonResponse("simple", "Sin cambios", "No se pudo eliminar la materia o ya fue eliminada.", "warning");
        }
    }

    public static function agregar_nueva_materia_por_grado_controlador($datos)
    {
        // 1. Recibir y limpiar
        $id_grado = MainModel::limpiar_cadenas($datos['id_grado'] ?? null);
        $id_materia = MainModel::limpiar_cadenas($datos['id_materia'] ?? null);
        $id_materia = MainModel::decryption($id_materia); 


        if (!$id_grado || !$id_materia || !is_numeric($id_materia)) {
            MainModel::jsonResponse("simple", "Datos inválidos", "Faltan datos o el ID no es válido.", "warning");
        }

        // 2. Verificar si existe el registro del grupo
        $consulta = "SELECT materias_json FROM materias_por_grado WHERE id_grado = '$id_grado' LIMIT 1";
        $resultado = MainModel::ejecutar_consultas_simples($consulta);

        if ($resultado->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Grado no encontrado", "No se encontró el Grado especificado.", "error");
        }

        $fila = $resultado->fetch(PDO::FETCH_ASSOC);
        $materias = json_decode($fila['materias_json'], true);

        if (!is_array($materias)) {
            MainModel::jsonResponse("simple", "Error", "El formato de materias_json es inválido.", "error");
        }

        // 3. Verificar si la materia ya existe
        if (in_array($id_materia, $materias)) {
            MainModel::jsonResponse("simple", "Materia existente", "Esta materia ya está asignada al Grado.", "info");
        }

        // 4. Agregar nueva materia
        $materias[] = (int)$id_materia;
        $materias_actualizadas = json_encode($materias, JSON_UNESCAPED_UNICODE);
        $materias_actualizadas = addslashes($materias_actualizadas);

        // 5. Actualizar base de datos
        $update = MainModel::ejecutar_consultas_simples("
            UPDATE materias_por_grado 
            SET materias_json = '$materias_actualizadas' 
            WHERE id_grado = '$id_grado'
        ");

        if ($update && $update->rowCount() > 0) {
            MainModel::jsonResponse("recargar", "Materia agregada", "La materia fue añadida correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Sin cambios", "No se pudo agregar la materia o ya estaba asignada.", "warning");
        }
    }

    public static function eliminar_materias_asignadas_grados_controlador($datos)
    {
        // 1. Limpiar y desencriptar el ID
        $id_grado = MainModel::limpiar_cadenas($datos['id_grado'] ?? null);

        if (!$id_grado || !is_numeric($id_grado)) {
            MainModel::jsonResponse("simple", "ID inválido", "El ID de grado no es válido.", "warning");
        }

        // 2. Verificar si existe al menos un registro relacionado
        $consulta = "SELECT id FROM materias_por_grado WHERE id_grado = '$id_grado' LIMIT 1";
        $resultado = MainModel::ejecutar_consultas_simples($consulta);

        if ($resultado->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Grado no encontrado", "No se encontraron materias asociadas a este grado.", "error");
        }

        // 3. Eliminar todos los registros relacionados
        $eliminar = MainModel::ejecutar_consultas_simples("
            DELETE FROM materias_por_grado 
            WHERE id_grado = '$id_grado'
        ");

        if ($eliminar && $eliminar->rowCount() > 0) {
            MainModel::jsonResponse("simple", "Eliminado", "Todas las materias del grado fueron eliminadas correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Sin cambios", "No se pudo eliminar la información o ya fue eliminada.", "warning");
        }
    }



    /*****************************portada ************************************* */

    public function guardar_portada_controlador($datos)
    {
        // Validar existencia de campos requeridos
        $codigo_institucion = isset($datos['codigo_institucion']) ? MainModel::limpiar_cadenas($datos['codigo_institucion']) : null;
        $id_sede            = isset($datos['id_sede']) ? MainModel::limpiar_cadenas($datos['id_sede']) : null;

        if (!$codigo_institucion || !$id_sede) {
            MainModel::jsonResponse("simple", "Faltan datos", "No se recibió información de sede o institución.", "warning");
        }

        // Validar archivos
        if (!isset($_FILES['imagenes'])) {
            MainModel::jsonResponse("simple", "Error", "No se recibieron imágenes para subir.", "error");
        }

        $imagenes_guardadas = [];
        $total = count($_FILES['imagenes']['name']);

        // Ruta base fija del proyecto
        $ruta_base         = __DIR__ . '/../views/resources';
        $ruta_institucion  = "$ruta_base/$codigo_institucion";
        $ruta_sede         = "$ruta_institucion/sedes/$id_sede";
        $ruta_usuario      = "$ruta_sede/documentos";
        $ruta_imagenes     = "$ruta_sede/imagenes";
        $ruta_avatares     = "$ruta_imagenes/avatares";
        $ruta_portadas     = "$ruta_imagenes/portada_institucion"; // Corrección aquí

        // Crear las carpetas si no existen
        $carpetas = [$ruta_institucion, "$ruta_institucion/sedes", $ruta_sede, $ruta_usuario, $ruta_imagenes, $ruta_avatares, $ruta_portadas];
        foreach ($carpetas as $carpeta) {
            if (!file_exists($carpeta)) {
                mkdir($carpeta, 0777, true);
            }
        }

        // Tipos de archivos permitidos
        $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        for ($i = 0; $i < $total; $i++) {
            $nombreOriginal = basename($_FILES['imagenes']['name'][$i]);
            $tipoArchivo = $_FILES['imagenes']['type'][$i];
            $tmpPath = $_FILES['imagenes']['tmp_name'][$i];

            if (!in_array($tipoArchivo, $tipos_permitidos)) {
                continue; // Ignorar archivos que no sean imagen
            }

            // Nombre final seguro
            $nombreFinal = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", $nombreOriginal);
            $rutaDestino = $ruta_portadas . "/" . $nombreFinal;

            if (move_uploaded_file($tmpPath, $rutaDestino)) {
                $imagenes_guardadas[] = $nombreFinal;
            }
        }

        if (empty($imagenes_guardadas)) {
            MainModel::jsonResponse("simple", "Error", "Ninguna imagen fue guardada correctamente.", "error");
        }

        $jsonImagenes = json_encode($imagenes_guardadas, JSON_UNESCAPED_UNICODE);

        // Guardar en la base de datos
        $query = MainModel::conectar()->prepare("
        INSERT INTO imagenes_portada (nombre_imagenes, estado, codigo_institucion, id_sede)
        VALUES (:imagenes, '0', :codigo, :sede)
        ");

        $query->bindParam(":imagenes", $jsonImagenes, PDO::PARAM_STR);
        $query->bindParam(":codigo", $codigo_institucion, PDO::PARAM_INT);
        $query->bindParam(":sede", $id_sede, PDO::PARAM_INT);

        if ($query->execute()) {
            MainModel::jsonResponse("simple", "¡Éxito!", "Imágenes de portada guardadas correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo guardar la información en la base de datos.", "error");
        }
    }

    public static function consultar_portadas_controlador($id_sede, $codigo_institucion)
    {
        $id_sede = MainModel::limpiar_cadenas($id_sede);
        $codigo_institucion = MainModel::limpiar_cadenas($codigo_institucion);

        if (!$codigo_institucion || !$id_sede) {
            MainModel::jsonResponse("simple", "Datos incompletos", "No se recibió sede o institución correctamente.", "warning");
        }

        // Buscar en la base de datos
        $query = MainModel::conectar()->prepare("
        SELECT * FROM imagenes_portada 
        WHERE codigo_institucion = :codigo AND id_sede = :sede
        ");
        $query->bindParam(":codigo", $codigo_institucion, PDO::PARAM_INT);
        $query->bindParam(":sede", $id_sede, PDO::PARAM_INT);
        $query->execute();

        if ($query->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Sin resultados", "No hay imágenes registradas para esta sede.", "info");
        }

        $fila = $query->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($fila);
    }

    public static function elimina_imagenes_portadas_controlador($datos)
    {
        // Limpiar y validar los datos recibidos
        $idPortada = MainModel::limpiar_cadenas($datos['idPortada'] ?? null);
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion'] ?? null);
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede'] ?? null);

        MainModel::validar_campos_obligatorios([
            'ID de portada' => $idPortada,
            'ID de sede' => $id_sede,
            'ID de institución' => $codigo_institucion
        ]);

        // Verificar existencia
        $check = MainModel::conectar()->prepare("SELECT nombre_imagenes FROM imagenes_portada WHERE id = :id");
        $check->bindParam(":id", $idPortada, PDO::PARAM_INT);
        $check->execute();

        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El ID de portada que intentas eliminar no existe o ya fue eliminado.", "warning");
        }

        $datosImagen = $check->fetch(PDO::FETCH_ASSOC);

        // Decodificar el JSON de imágenes
        $nombresImagenes = json_decode($datosImagen['nombre_imagenes'], true);

        if (!is_array($nombresImagenes)) {
            MainModel::jsonResponse("simple", "Error", "No se pudieron procesar las imágenes asociadas.", "error");
        }

        // Ruta base donde están las imágenes
        $ruta_base = __DIR__ . '/../views/resources';
        $ruta_portada = "$ruta_base/$codigo_institucion/sedes/$id_sede/imagenes/portada_institucion";

        // Eliminar físicamente cada imagen
        foreach ($nombresImagenes as $nombre) {
            $rutaCompleta = $ruta_portada . '/' . $nombre;

            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }
        }
        // Eliminar el registro de la base de datos
        $delete = MainModel::conectar()->prepare("DELETE FROM imagenes_portada WHERE id = :id");
        $delete->bindParam(":id", $idPortada, PDO::PARAM_INT);

        if ($delete->execute()) {
            MainModel::jsonResponse("simple", "Portada eliminada", "Las imágenes y el registro fueron eliminados correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "Ocurrió un error al intentar eliminar la portada.", "error");
        }
    }

    public static function activar_desactivar_portada_controlador($datos)
    {
        // Limpiar datos
        $idPortada = MainModel::limpiar_cadenas($datos['idPortada'] ?? null);
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede'] ?? null);
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion'] ?? null);

        // Validación
        MainModel::validar_campos_obligatorios([
            'ID de portada' => $idPortada,
            'ID de sede' => $id_sede,
            'Código de institución' => $codigo_institucion
        ]);

        // Verificar existencia y estado actual
        $consulta = MainModel::conectar()->prepare("SELECT estado FROM imagenes_portada WHERE id = :id");
        $consulta->bindParam(":id", $idPortada, PDO::PARAM_INT);
        $consulta->execute();

        if ($consulta->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "La portada no existe o ya fue eliminada.", "warning");
        }

        $resultado = $consulta->fetch(PDO::FETCH_ASSOC);
        $estadoActual = intval($resultado['estado']);
        $nuevoEstado = $estadoActual === 1 ? 0 : 1;

        $conexion = MainModel::conectar();

        // Si se va a activar esta portada, desactivar todas las demás
        if ($nuevoEstado == 1) {
            $desactivarTodas = $conexion->prepare("
            UPDATE imagenes_portada 
            SET estado = 0 
            WHERE id_sede = :id_sede 
              AND codigo_institucion = :codigo_institucion
        ");
            $desactivarTodas->bindParam(":id_sede", $id_sede, PDO::PARAM_INT);
            $desactivarTodas->bindParam(":codigo_institucion", $codigo_institucion, PDO::PARAM_STR);
            $desactivarTodas->execute();
        }

        // Activar o desactivar la seleccionada
        $update = $conexion->prepare("UPDATE imagenes_portada SET estado = :nuevo_estado WHERE id = :id");
        $update->bindParam(":nuevo_estado", $nuevoEstado, PDO::PARAM_INT);
        $update->bindParam(":id", $idPortada, PDO::PARAM_INT);

        if ($update->execute()) {
            $mensaje = $nuevoEstado === 1 ? "Portada activada correctamente. Las demás fueron desactivadas." : "Portada desactivada correctamente.";
            MainModel::jsonResponse("simple", "Actualización exitosa", $mensaje, "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar el estado de la portada.", "error");
        }
    }

    public static function Editar_logo_sede_controlador($datos)
    {
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede'] ?? '');
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion'] ?? '');

        MainModel::validar_campos_obligatorios([
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, null);

        // 1. Obtener logo actual desde la base de datos
        $conexion = MainModel::conectar();
        $sql_select = "SELECT logo_sede FROM sedes WHERE id_sede = :id_sede AND codigo_institucion = :codigo_institucion LIMIT 1";
        $stmt_select = $conexion->prepare($sql_select);
        $stmt_select->bindParam(':id_sede', $id_sede, PDO::PARAM_INT);
        $stmt_select->bindParam(':codigo_institucion', $codigo_institucion, PDO::PARAM_INT);
        $stmt_select->execute();
        $resultado = $stmt_select->fetch(PDO::FETCH_ASSOC);

        $logo_actual = $resultado['logo_sede'] ?? '';

        // 2. Validar si se está subiendo una nueva imagen
        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            return MainModel::jsonResponse("simple", "Error", "No se recibió una imagen válida.", "error");
        }

        $nombre_archivo = $_FILES['imagen']['name'];
        $tipo_archivo   = $_FILES['imagen']['type'];
        $temporal       = $_FILES['imagen']['tmp_name'];

        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($tipo_archivo, $tipos_permitidos)) {
            return MainModel::jsonResponse("simple", "Tipo inválido", "Solo se permiten imágenes JPEG, PNG o WebP.", "warning");
        }

        // 3. Definir ruta destino
        $ruta_base = __DIR__ . '/../views';
        $ruta_logo = "$ruta_base/assets/image/logos-sedes/";

        if (!file_exists($ruta_logo)) {
            mkdir($ruta_logo, 0777, true);
        }

        // 4. Generar nombre único
        $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
        $nuevo_nombre = 'logo_sede_' . uniqid() . '.' . $extension;
        $ruta_destino = $ruta_logo . $nuevo_nombre;

        // 5. Eliminar logo anterior (si existe)
        if ($logo_actual && file_exists($ruta_logo . $logo_actual)) {
            unlink($ruta_logo . $logo_actual);
        }

        // 6. Subir la nueva imagen
        if (!move_uploaded_file($temporal, $ruta_destino)) {
            return MainModel::jsonResponse("simple", "Error", "No se pudo guardar el nuevo logo.", "error");
        }

        // 7. Actualizar en la base de datos
        $sql_update = "UPDATE sedes SET logo_sede = :logo WHERE id_sede = :id_sede AND codigo_institucion = :codigo_institucion";
        $stmt_update = $conexion->prepare($sql_update);
        $stmt_update->bindParam(':logo', $nuevo_nombre, PDO::PARAM_STR);
        $stmt_update->bindParam(':id_sede', $id_sede, PDO::PARAM_INT);
        $stmt_update->bindParam(':codigo_institucion', $codigo_institucion, PDO::PARAM_INT);

        if ($stmt_update->execute()) {

           // 8. Traer la sede actualizada
        $query = "SELECT * FROM sedes WHERE id_sede = :id_sede LIMIT 1";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':id_sede', $id_sede, PDO::PARAM_INT);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $fila['colores_sede'] = json_decode($fila['colores_sede'], true);
        $fila['id_sede_encriptado'] = MainModel::encryption($fila['id_sede']);
        $fila['codigo_institucion_encriptado'] = MainModel::encryption($fila['codigo_institucion']);

        return json_encode([
                "Alerta" => "simple",
                "Titulo" => "¡Logo actualizado!",
                "Texto" => "La imagen se ha guardado exitosamente.",
                "Tipo" => "success",
                "sede" =>  $fila
            ]);
        } else {
            return MainModel::jsonResponse("simple", "Error", "No se pudo actualizar el logo en la base de datos.", "error");
        }
    }

    public static function actualizar_sede_controlador($datos){
        $nombre_sede = MainModel::limpiar_cadenas($datos['nombre_sede'] ?? '');
        $direccion = MainModel::limpiar_cadenas($datos['direccion'] ?? '');
        $telefono = MainModel::limpiar_cadenas($datos['telefono'] ?? '');
        $color_primario = MainModel::limpiar_cadenas($datos['color_primario'] ?? '');
        $color_secundario = MainModel::limpiar_cadenas($datos['color_secundario'] ?? '');
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede'] ?? '');
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion'] ?? '');
        $cambios = false;

        MainModel::validar_campos_obligatorios([
            'nombre_sede' => $nombre_sede,
            'direccion' => $direccion,
            'telefono' => $telefono,
            'color_primario' => $color_primario,
            'color_secundario' => $color_secundario,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        // 3. Obtener información actual de la sede
        $consulta_actual = MainModel::ejecutar_consultas_simples("SELECT * FROM sedes WHERE id_sede = '$id_sede' AND codigo_institucion = '$codigo_institucion'");
        if ($consulta_actual->rowCount() != 1) {
            return MainModel::jsonResponse("simple", "Sede no encontrada", "No se encontró la sede especificada..", "error");
        }

        $sede_actual = $consulta_actual->fetch(PDO::FETCH_ASSOC);

        $colores_actuales = json_decode($sede_actual['colores_sede'], true);

        if ($sede_actual['nombre_sede'] !== $nombre_sede) $cambios = true;
        if ($sede_actual['direccion'] !== $direccion) $cambios = true;
        if ($sede_actual['telefono'] !== $telefono) $cambios = true;
        if (!isset($colores_actuales['primario']) || $colores_actuales['primario'] !== $color_primario) $cambios = true;
        if (!isset($colores_actuales['secundario']) || $colores_actuales['secundario'] !== $color_secundario) $cambios = true;

        if (!$cambios) {
            return MainModel::jsonResponse("simple", "Sin cambios", "No se detectaron cambios para actualizar", "info");
        }

        // 5. Construir objeto JSON de colores
        $colores_json = json_encode([
            "primario" => $color_primario,
            "secundario" => $color_secundario
        ], JSON_UNESCAPED_UNICODE);


        $sql = "UPDATE sedes SET 
                nombre_sede = :nombre_sede,
                direccion = :direccion,
                telefono = :telefono,
                colores_sede = :colores_sede
            WHERE id_sede = :id_sede AND codigo_institucion = :codigo_institucion";

        $conexion = MainModel::conectar();
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':nombre_sede', $nombre_sede);
        $stmt->bindParam(':direccion', $direccion);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':colores_sede', $colores_json);
        $stmt->bindParam(':id_sede', $id_sede);
        $stmt->bindParam(':codigo_institucion', $codigo_institucion);

        if ($stmt->execute()) {
            // 8. Traer la sede actualizada
            $query = "SELECT * FROM sedes WHERE id_sede = :id_sede LIMIT 1";
            $stmt = $conexion->prepare($query);
            $stmt->bindParam(':id_sede', $id_sede, PDO::PARAM_INT);
            $stmt->execute();

            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            $fila['colores_sede'] = json_decode($fila['colores_sede'], true);
            $fila['id_sede_encriptado'] = MainModel::encryption($fila['id_sede']);
            $fila['codigo_institucion_encriptado'] = MainModel::encryption($fila['codigo_institucion']);

            return json_encode([
                "Alerta" => "simple",
                "Titulo" => "Sede actualizada",
                "Texto" => "La información se ha actualizado correctamente",
                "Tipo" => "success",
                "sede" =>  $fila
            ]);
        }else{
              return MainModel::jsonResponse("simple", "Error", "No se pudo actualizar la información de la sede", "error");
        }


    }
    



    /**************************************periodo academicos*********************************************** */

    public static function registrar_periodos_academicos_sede_controlador($datos)
    {
        $nombre_periodo = MainModel::limpiar_cadenas($datos['nombre_periodo']);
        $fecha_inicio = MainModel::limpiar_cadenas($datos['fecha_inicio']);
        $fecha_fin = MainModel::limpiar_cadenas($datos['fecha_fin']);
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion']);
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede']);


        MainModel::validar_campos_obligatorios([
            'Nombre del período' => $nombre_periodo,
            'Fecha de inicio' => $fecha_inicio,
            'Fecha de fin' => $fecha_fin,
            'Código institución' => $codigo_institucion,
            'Sede' => $id_sede
        ]);

        // Validar existencia de sede e institución
        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, null);

        // Verificar si ya existe un período con el mismo nombre en esa sede e institución
        $check = MainModel::ejecutar_consultas_simples("SELECT nombre_periodo FROM periodos_academicos WHERE nombre_periodo = '$nombre_periodo' AND codigo_institucion = '$codigo_institucion' AND id_sede = '$id_sede'");

        if ($check->rowCount() > 0) {
            MainModel::jsonResponse("simple", "Período duplicado", "Ya existe un período con ese nombre en esta sede.", "warning");
        }

        // Insertar nuevo período académico

        $periodo_guardada = InstitucionesModelo::guardar_periodos_academicos([
            'nombre_periodo' => $nombre_periodo,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'codigo_institucion' => $codigo_institucion,
            'id_sede' => $id_sede
        ]);

        if ($periodo_guardada) {
            MainModel::jsonResponse("simple", "Período registrado", "El período académico fue registrado correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "Ocurrió un error al registrar el período académico.", "error");
        }
    }


    public static function extraer_periodos_academicos_controlador($id_sede, $codigo_institucion)
    {
        $id_sede = MainModel::limpiar_cadenas($id_sede);
        $codigo_institucion = MainModel::limpiar_cadenas($codigo_institucion);

        $query = "SELECT * FROM periodos_academicos 
              WHERE id_sede = :id_sede 
              AND codigo_institucion = :codigo_institucion  ";

        $stmt = MainModel::conectar()->prepare($query);
        $stmt->bindParam(':id_sede', $id_sede);
        $stmt->bindParam(':codigo_institucion', $codigo_institucion);
        $stmt->execute();

        $periodos = [];

        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fila['id_periodo'] = MainModel::encryption($fila['id_periodo']);
            $fila['codigo_institucion'] = MainModel::encryption($fila['codigo_institucion']);
            $fila['id_sede'] = MainModel::encryption($fila['id_sede']);
            $periodos[] = $fila;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($periodos);
    }

    public static function editar_periodo_academico_controlador($datos)
    {
        // Limpiar entradas
        $id_periodo = MainModel::limpiar_cadenas($datos['id_periodo']);
        $nombre_periodo = MainModel::limpiar_cadenas($datos['nombre_periodo']);
        $fecha_inicio = MainModel::limpiar_cadenas($datos['fecha_inicio']);
        $fecha_fin = MainModel::limpiar_cadenas($datos['fecha_fin']);

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'ID del período' => $id_periodo,
            'Nombre del período' => $nombre_periodo,
            'Fecha de inicio' => $fecha_inicio,
            'Fecha de fin' => $fecha_fin,
        ]);

        // Desencriptar ID
        $id_periodo = MainModel::decryption($id_periodo);

        // Verificar existencia
        $check = MainModel::conectar()->prepare("SELECT * FROM periodos_academicos WHERE id_periodo = :id");
        $check->bindParam(":id", $id_periodo, PDO::PARAM_INT);
        $check->execute();

        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El período académico que intentas editar no existe.", "warning");
        }

        $actual = $check->fetch(PDO::FETCH_ASSOC);

        // Comparar si todos los valores son iguales
        $sin_cambios =
            strcasecmp(trim($actual['nombre_periodo']), trim($nombre_periodo)) === 0 &&
            trim($actual['fecha_inicio']) === trim($fecha_inicio) &&
            trim($actual['fecha_fin']) === trim($fecha_fin);

        if ($sin_cambios) {
            MainModel::jsonResponse("simple", "Sin cambios", "No realizaste ningún cambio en el período académico.", "info");
        }

        // Actualizar datos
        $actualizar = MainModel::conectar()->prepare("
        UPDATE periodos_academicos 
        SET nombre_periodo = :nombre,
            fecha_inicio = :inicio,
            fecha_fin = :fin
        WHERE id_periodo = :id
        ");

        $actualizar->bindParam(":nombre", $nombre_periodo, PDO::PARAM_STR);
        $actualizar->bindParam(":inicio", $fecha_inicio, PDO::PARAM_STR);
        $actualizar->bindParam(":fin", $fecha_fin, PDO::PARAM_STR);
        $actualizar->bindParam(":id", $id_periodo, PDO::PARAM_INT);

        if ($actualizar->execute()) {
            MainModel::jsonResponse("simple", "Actualizado", "El período académico fue actualizado correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar el período académico.", "error");
        }
    }

    public static function eliminar_periodo_academicos_controlador($datos)
    {
        // Limpiar entradas
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion']);
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede']);
        $id_periodo = MainModel::limpiar_cadenas($datos['id_periodo']);

        // Validar campos obligatorios
        MainModel::validar_campos_obligatorios([
            'ID del período' => $id_periodo,
            'Institución' => $codigo_institucion,
            'Sede' => $id_sede
        ]);

        // Desencriptar valores
        $id_periodo = MainModel::decryption($id_periodo);
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);

        // Verificar que exista el período académico
        $check = MainModel::conectar()->prepare("
        SELECT * FROM periodos_academicos 
        WHERE id_periodo = :id 
        AND codigo_institucion = :codigo 
        AND id_sede = :sede
        ");
        $check->bindParam(":id", $id_periodo, PDO::PARAM_INT);
        $check->bindParam(":codigo", $codigo_institucion, PDO::PARAM_STR);
        $check->bindParam(":sede", $id_sede, PDO::PARAM_INT);
        $check->execute();

        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El período académico que intentas eliminar no existe o ya fue eliminado.", "warning");
        }

        // Eliminar el período
        $delete = MainModel::conectar()->prepare("DELETE FROM periodos_academicos WHERE id_periodo = :id");
        $delete->bindParam(":id", $id_periodo, PDO::PARAM_INT);

        if ($delete->execute()) {
            MainModel::jsonResponse("simple", "Período eliminado", "El período académico fue eliminado exitosamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "Ocurrió un error al intentar eliminar el período académico.", "error");
        }
    }
}
