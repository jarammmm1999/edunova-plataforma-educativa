<?php

require_once __DIR__ . '/../models//UsuariosModelos.php';


class UsuariosController extends UsuariosModelos
{

    public static function Agregar_usuarios_controlador($datos)
    {
        // Datos básicos
        $documento   = MainModel::limpiar_cadenas($datos['documento'] ?? '');
        $nombre      = MainModel::limpiar_cadenas($datos['nombre'] ?? '');
        $correo      = MainModel::limpiar_cadenas($datos['correo'] ?? '');
        $telefono    = MainModel::limpiar_cadenas($datos['telefono'] ?? '');
        $sexo        = MainModel::limpiar_cadenas($datos['sexo'] ?? '');
        $contrasena  = MainModel::limpiar_cadenas($datos['contrasena'] ?? '');
        $confirmPass = MainModel::limpiar_cadenas($datos['confirmContrasena'] ?? '');
        $estado      = MainModel::limpiar_cadenas($datos['estado'] ?? '');
        $rol         = MainModel::limpiar_cadenas($datos['rol'] ?? '');
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion'] ?? '');
        $id_sede     = MainModel::limpiar_cadenas($datos['id_sede'] ?? '');

        //validamos que no vengan vacios
        MainModel::validar_campos_obligatorios([
            'documento' => $documento,
            'nombres' => $nombre,
            'correo' => $correo,
            'telefono' => $telefono,
            'sexo' => $sexo,
            'contraseña' => $contrasena,
            'confirmación contraseña' => $confirmPass,
            'estado' => $estado,
            'rol' => $rol,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        // Validar coincidencia de contraseñas
        if (trim($contrasena) !== trim($confirmPass)) {
            return MainModel::jsonResponse("simple", "Error", "Las contraseñas  no coinciden ", "error");
        }

        $id_sexo = MainModel::decryption($sexo);
        $id_estado = MainModel::decryption($estado);
        $id_rol = MainModel::decryption($rol);
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);
        $contrasena = MainModel::encryption($contrasena);

        $check = MainModel::ejecutar_consultas_simples("SELECT id_sexo FROM sexos WHERE id_sexo = '$id_sexo'");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Sexo inválido", "El sexo seleccionado no es válido o no existe en el sistema.", "warning");
        }

        $check = MainModel::ejecutar_consultas_simples("SELECT id_estado FROM estados WHERE id_estado = '$id_estado'");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Estado inválido", "El estado seleccionado no es válido o no existe en el sistema.", "warning");
        }

        $check = MainModel::ejecutar_consultas_simples("SELECT 	id_rol FROM roles WHERE id_rol = '$id_rol'");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Rol inválido", "El rol seleccionado no es válido o no existe en el sistema.", "warning");
        }

        if (!MainModel::correo_valido($correo)) {
            return MainModel::jsonResponse("simple", "Correo inválido", "El correo enviado no es válido", "warning");
        }

        $check = MainModel::ejecutar_consultas_simples("SELECT correo FROM usuarios WHERE correo = '$correo' 
        and codigo_institucion ='$codigo_institucion' and id_sede = '$id_sede'");
        if ($check->rowCount() > 0) {
            MainModel::jsonResponse("simple", "Duplicado", "Ya existe un usuario registrado con este correo.", "error");
        }

        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, null);

        // Imagen (si la estás enviando como base64 o nombre de archivo)
        $imagen = isset($datos['imagen']) ? $datos['imagen'] : 'AvatarNone.png';

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $nombre_archivo = $_FILES['imagen']['name'];
            $tipo_archivo   = $_FILES['imagen']['type'];
            $temporal       = $_FILES['imagen']['tmp_name'];

            // Tipos MIME válidos
            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($tipo_archivo, $tipos_permitidos)) {
                return MainModel::jsonResponse(
                    "simple",
                    "Tipo de imagen inválido",
                    "Solo se permiten imágenes en formato JPEG, PNG o WebP.",
                    "warning"
                );
            }
        }

        // Ruta base fija porque ya existe en tu proyecto
        $ruta_base = __DIR__ . '/../views/resources';
        $ruta_institucion = "$ruta_base/$codigo_institucion";
        $ruta_sede = "$ruta_institucion/sedes/$id_sede";
        $ruta_usuario = "$ruta_sede/documentos";
        $ruta_imagenes = "$ruta_sede/imagenes";
        $ruta_avatares = "$ruta_imagenes/avatares";
        
        // Crear carpetas necesarias
        $carpetas = [$ruta_institucion, "$ruta_institucion/sedes", $ruta_sede, $ruta_usuario, $ruta_imagenes, $ruta_avatares];

        foreach ($carpetas as $carpeta) {
            if (!file_exists($carpeta)) {
                mkdir($carpeta, 0777, true);
            }
        }

        // Reescribimos el nombre de la imaegn del usuario para que sea unica y la guardamo
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            // guardamos la imagen del usuario en la carpeta
            $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
            $nuevo_nombre = 'usuario_' . uniqid() . '.' . $extension;
            $ruta_destino = $ruta_avatares . '/' . $nuevo_nombre;

            if (move_uploaded_file($temporal, $ruta_destino)) {
                $imagen = $nuevo_nombre;
            }
        }

        //validmso si es un estudiante

        if ($id_rol == 4) {

            $id_grado       = MainModel::limpiar_cadenas($datos['grado'] ?? '');
            $id_grupo       = MainModel::limpiar_cadenas($datos['grupo'] ?? '');
            $id_grado = MainModel::decryption($id_grado);
            $id_grupo = MainModel::decryption($id_grupo);


            //validamos que el cupo de usuarios registrado a ese grupo no se haya completado

            // 1. Consultar cantidad máxima permitida en el grupo
            $consultaGrupo = MainModel::ejecutar_consultas_simples("SELECT cantidad FROM grupos WHERE id_grado = '$id_grado' AND id_grupo = '$id_grupo' LIMIT 1");
            if ($consultaGrupo->rowCount() <= 0) {
                MainModel::jsonResponse("simple", "Configuración faltante", "No se encontró la configuración del grupo y grado.", "warning");
            }
            $cantidadMaxima = $consultaGrupo->fetchColumn();

            // 2. Consultar cantidad actual de estudiantes matriculados
            $consultaMatriculas = MainModel::ejecutar_consultas_simples("SELECT COUNT(*) FROM matriculas WHERE id_grado = '$id_grado' AND id_grupo = '$id_grupo'");
            $cantidadActual = $consultaMatriculas->fetchColumn();
            
            if ($cantidadActual >= $cantidadMaxima) {
                MainModel::jsonResponse("simple", "Límite alcanzado", "Este grupo ya tiene los $cantidadMaxima estudiantes permitidos. No se pueden registrar más.", "warning");
            }


            $documentos_info = [];

            if (isset($datos['documentos']) && is_array($datos['documentos'])) {
                $documentos_info = $datos['documentos'];
            } else {
                // Si no llegaron documentos personalizados
                MainModel::jsonResponse("simple", "Ocurrió un error inesperado", "Por favor selecciona uno o varios documentos", "warning");
                exit;
            }
            $formatosPermitidos = ["application/pdf"];
            $total = count($_FILES['documentos']['name'] ?? []);


            if (empty($id_grado)) {
                MainModel::jsonResponse("simple", "Campos vacíos", "Debes llenar todos los campos requeridos.", "warning");
            }


            for ($i = 0; $i < $total; $i++) {
                // Validar archivo
                $tipoArchivo = $_FILES['documentos']['type'][$i]['archivo'] ?? '';
                $nombreArchivo = $_FILES['documentos']['name'][$i]['archivo'] ?? "Sin nombre";

                if (!in_array($tipoArchivo, $formatosPermitidos)) {
                    MainModel::jsonResponse("simple", "Archivo inválido", "El archivo '$nombreArchivo' no es un PDF válido.", "warning");
                    exit;
                }

                // Validar nombre personalizado
                $nombrePersonalizado = $documentos_info[$i]['nombrePersonalizado'] ?? '';
                if (trim($nombrePersonalizado) === '') {
                    MainModel::jsonResponse("simple", "Nombre personalizado vacío", "El nombre personalizado del documento  $nombreArchivo no puede estar vacío.", "warning");
                    exit;
                }
            }

            // Realizaremos la secuencia de la inserccion de los datos

            // 1. registrar en la tabla usuarios

            $datos_usuario = [
                "documento" => $documento,
                "nombres" => $nombre,
                "correo" => $correo,
                "telefono" => $telefono,
                "id_sexo" => $id_sexo,
                "imagen" => $imagen,
                "estado" => $id_estado,
                "contrasena" => $contrasena,
                "codigo_institucion" => $codigo_institucion,
                "id_rol" => $id_rol,
                "id_sede" => $id_sede,
                "fecha_creacion" => date("Y-m-d H:i:s")
            ];


            $guardar = UsuariosModelos::agregar_usuario_modelo($datos_usuario);

            if ($guardar) {

                // 2. creamos la carpeta del usuario

                $carpetaDocumentoUsuario = "$ruta_usuario/$documento";

                if (!file_exists($carpetaDocumentoUsuario)) {
                    mkdir($carpetaDocumentoUsuario, 0777, true);
                }

                // 3. guardamos los datos en la tabla matricula

                $datamatricula = [
                    "documento" => $documento,
                    "codigo_institucion" => $codigo_institucion,
                    "id_grado" => $id_grado,
                    "id_grupo" => $id_grupo,
                    "id_sede" => $id_sede,
                    "fecha_matricula" => date("Y-m-d H:i:s"),
                    "observaciones" => 'Matricula exitosa'
                ];

                $matricula = UsuariosModelos::registrar_matricula_modelo($datamatricula);

                if ($matricula) {

                    // extraemos el id de la matricula

                    $extraer_id_matricula = MainModel::ejecutar_consultas_simples(
                        "SELECT id_matricula FROM matriculas WHERE documento = '$documento' ORDER BY id_matricula DESC LIMIT 1"
                    );
                    if ($extraer_id_matricula->rowCount() > 0) {
                        $id_matricula = $extraer_id_matricula->fetch(PDO::FETCH_ASSOC)['id_matricula'];
                    }

                    // 4. guardamos los documento en la base de datos y en la carpeta del usuario

                    $documentosGuardadas = [];
                    $nombres_personalizados = [];

                    for ($i = 0; $i < $total; $i++) {
                        $nombreArchivoOriginal = $_FILES['documentos']['name'][$i]['archivo']; // ✅ quitamos ['archivo'] porque no aplica
                        $nombrePersonalizado = $documentos_info[$i]['nombrePersonalizado'];
                        $extension = pathinfo($nombreArchivoOriginal, PATHINFO_EXTENSION); // ✅ usamos variable correcta
                        $nombreArchivo = uniqid("doc_", true) . "." . $extension;

                        $rutaArchivo = $carpetaDocumentoUsuario . '/' . $nombreArchivo;
                        $tmp_name = $_FILES['documentos']['tmp_name'][$i]['archivo']; // ✅ agregamos esto correctamente

                        if (move_uploaded_file($tmp_name, $rutaArchivo)) {
                            $documentosGuardadas[] = $nombreArchivo;
                            $nombres_personalizados[] = $nombrePersonalizado; // ✅ corregimos clave incorrecta
                        }
                    }

                    if (!empty($documentosGuardadas)) {

                        $data = [
                            "id_matricula" => $id_matricula, // ID real de la matrícula registrada
                            "nombres_documentos" => json_encode($documentosGuardadas), // Arreglo con nombres de archivos
                            "descripcion_documentos" => json_encode($nombres_personalizados), // Arreglo con descripciones personalizadas
                            "entregado" => true,
                            "fecha_entrega" => date("Y-m-d H:i:s")
                        ];

                        $documentos_matricula = UsuariosModelos::registrar_documentos_matricula_modelo($data);

                        if ($documentos_matricula) {

                            $acudientes_data = [];

                            if (isset($datos['acudientes'])) {
                                $acudientes_data = json_decode($datos['acudientes'], true); // ✅ decodificamos el JSON string

                                if (!is_array($acudientes_data)) {
                                    MainModel::jsonResponse("simple", "Error", "Los datos de los acudientes no son válidos.", "warning");
                                    exit;
                                }
                            } else {
                                MainModel::jsonResponse("simple", "Ocurrió un error inesperado", "No se han recibido los datos de los acudientes.", "warning");
                                exit;
                            }

                            // Validar campos obligatorios por cada acudiente
                            $camposObligatorios = ['nombres', 'correo', 'telefono', 'direccion', 'numeroDocumento', 'parentesco', 'sexo', 'contrasena'];

                            foreach ($acudientes_data as $index => $acudiente) {
                                foreach ($camposObligatorios as $campo) {
                                    if (!isset($acudiente[$campo]) || trim($acudiente[$campo]) === '') {
                                        $nombreCampo = ucfirst(str_replace('numeroDocumento', 'número de documento', $campo));
                                        MainModel::jsonResponse(
                                            "simple",
                                            "Campo vacío",
                                            "El campo <b>$nombreCampo</b> está vacío en el <b>acudiente " . ($index + 1) . "</b>. Por favor complétalo.",
                                            "warning"
                                        );
                                        exit;
                                    }
                                }
                            }

                            // Recorrer y agregar los nuevos campos
                            foreach ($acudientes_data as $index => $acudiente) {
                                $acudientes_data[$index]['codigo_institucion'] = $codigo_institucion;
                                $acudientes_data[$index]['id_sede'] = $id_sede;
                                $acudientes_data[$index]['fecha_creacion'] = date("Y-m-d H:i:s");
                                $acudientes_data[$index]['estado'] = true;
                                $acudientes_data[$index]['imagen'] = "AvatarNone.png"; // ✅ Valor por defecto
                                $acudientes_data[$index]['documento_estudiante'] = "$documento"; // ✅ Valor por defecto
                            }

                            foreach ($acudientes_data as $acudiente) {
                                // Encriptar contraseña
                                $acudiente['contrasena'] = MainModel::encryption($acudiente['contrasena']);

                                $acudiente['sexo'] = MainModel::decryption($acudiente['sexo']);

                                // Guardar
                                $guardar = UsuariosModelos::agregar_acudiente_modelo($acudiente);

                                // Validar error
                                if ($guardar->rowCount() <= 0) {
                                    MainModel::jsonResponse("simple", "Error", "No se pudo guardar el acudiente: " . $acudiente['nombres'], "error");
                                    exit();
                                }
                            }

                            MainModel::jsonResponse("recargar", "Estudiante Matriculado", "El estudiante se matriculo correctamnete", "success");
                        } else {
                            MainModel::jsonResponse("simple", "Error", "No se pudo registrar los documentos del estudiante", "error");
                        }
                    }
                } else {
                    MainModel::jsonResponse("simple", "Error", "No se pudo realizar la matricula del usuario. Intenta nuevamente.", "error");
                }
            } else {
                // ❌ Eliminar la imagen si se subió pero no se guardó el usuario
                if ($imagen && file_exists($ruta_destino)) {
                    unlink($ruta_destino);
                }
                MainModel::jsonResponse("simple", "Error", "No se pudo registrar el usuario. Intenta nuevamente.", "error");
            }
        }

        $datos_usuario = [
            "documento" => $documento,
            "nombres" => $nombre,
            "correo" => $correo,
            "telefono" => $telefono,
            "id_sexo" => $id_sexo,
            "imagen" => $imagen,
            "estado" => $id_estado,
            "contrasena" => $contrasena,
            "codigo_institucion" => $codigo_institucion,
            "id_rol" => $id_rol,
            "id_sede" => $id_sede,
            "fecha_creacion" => date("Y-m-d H:i:s")
        ];

        // Llamar al modelo para guardar
        $guardar = UsuariosModelos::agregar_usuario_modelo($datos_usuario);

        if ($guardar) {
            MainModel::jsonResponse("recargar", "Registro exitoso", "El usuario ". $nombre. "  se registró correctamente.", "success");
        } else {

            if ($imagen && file_exists($ruta_destino)) {
                unlink($ruta_destino);
            }
            MainModel::jsonResponse("simple", "Error", "No se pudo registrar el usuario. Intentalo nuevamente.", "error");
        }
    }

    public static function Actualizar_usuarios_controlador($datos) {
         // Datos básicos
        $documento   = MainModel::limpiar_cadenas($datos['documento'] ?? '');
        $nombre      = MainModel::limpiar_cadenas($datos['nombre'] ?? '');
        $correo      = MainModel::limpiar_cadenas($datos['correo'] ?? '');
        $telefono    = MainModel::limpiar_cadenas($datos['telefono'] ?? '');
        $sexo        = MainModel::limpiar_cadenas($datos['sexo'] ?? '');
        $contrasena  = MainModel::limpiar_cadenas($datos['contrasena'] ?? '');
        $confirmPass = MainModel::limpiar_cadenas($datos['confirmContrasena'] ?? '');
        $estado      = MainModel::limpiar_cadenas($datos['estado'] ?? '');
        $rol         = MainModel::limpiar_cadenas($datos['rol'] ?? '');
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion'] ?? '');
        $id_sede     = MainModel::limpiar_cadenas($datos['id_sede'] ?? '');


        //validamos que no vengan vacios

        MainModel::validar_campos_obligatorios([
            'documento' => $documento,
            'nombres' => $nombre,
            'correo' => $correo,
            'telefono' => $telefono,
            'sexo' => $sexo,
            'contraseña' => $contrasena,
            'confirmación contraseña' => $confirmPass,
            'estado' => $estado,
            'rol' => $rol,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);


        // Validar coincidencia de contraseñas
        if (trim($contrasena) !== trim($confirmPass)) {
            return MainModel::jsonResponse("simple", "Error", "Las contraseñas  no coinciden ", "error");
        }


        $id_sexo = MainModel::decryption($sexo);
        $documento = MainModel::decryption($documento);
        $id_estado = MainModel::decryption($estado);
        $id_rol = MainModel::decryption($rol);
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);
        $contrasena = MainModel::encryption($contrasena);

        $check = MainModel::ejecutar_consultas_simples("SELECT id_sexo FROM sexos WHERE id_sexo = '$id_sexo'");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Sexo inválido", "El sexo seleccionado no es válido o no existe en el sistema.", "warning");
        }

        $check = MainModel::ejecutar_consultas_simples("SELECT id_estado FROM estados WHERE id_estado = '$id_estado'");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Estado inválido", "El estado seleccionado no es válido o no existe en el sistema.", "warning");
        }

        $check = MainModel::ejecutar_consultas_simples("SELECT 	id_rol FROM roles WHERE id_rol = '$id_rol'");
        if ($check->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "Rol inválido", "El rol seleccionado no es válido o no existe en el sistema.", "warning");
        }

        if (!MainModel::correo_valido($correo)) {
             MainModel::jsonResponse("simple", "Correo inválido", "El correo enviado no es válido", "warning");
        }


         $consulta = "SELECT nombres, correo, telefono, id_sexo, contrasena, estado, id_rol, imagen
            FROM usuarios  WHERE documento = :documento AND codigo_institucion = :codigo AND id_sede = :sede
        ";

        $conexion = MainModel::conectar();
        $stmt = $conexion->prepare($consulta);
        $stmt->bindParam(":documento", $documento, PDO::PARAM_STR);
        $stmt->bindParam(":codigo", $codigo_institucion, PDO::PARAM_STR);
        $stmt->bindParam(":sede", $id_sede, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            MainModel::jsonResponse("simple", "Usuario no encontrado", "No existe un usuario con ese documento en esa institución y sede.", "warning");
        }

        $usuario_actual = $stmt->fetch(PDO::FETCH_ASSOC);

        $cambios = [];

        if ($usuario_actual['nombres'] !== $nombre) {
            $cambios['nombres'] = $nombre;
        }

        if ($usuario_actual['correo'] !== $correo) {
            $cambios['correo'] = $correo;
        }

        if ($usuario_actual['telefono'] !== $telefono) {
            $cambios['telefono'] = $telefono;
        }

        if ($usuario_actual['id_sexo'] != $id_sexo) {
            $cambios['id_sexo'] = $id_sexo;
        }

        if ($usuario_actual['estado'] != $id_estado) {
            $cambios['estado'] = $id_estado;
        }

        if ($usuario_actual['id_rol'] != $id_rol) {
            $cambios['id_rol'] = $id_rol;
        }

        // Comparamos contraseña desencriptando la actual (si está encriptada con el método propio)
        if (!MainModel::comparar_claves($usuario_actual['contrasena'], $contrasena)) {
            $cambios['contrasena'] = $contrasena;
        }
    
        $imagen_nombre = $usuario_actual['imagen']; // Por si no se sube imagen

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $nombre_archivo = $_FILES['imagen']['name'];
            $tipo_archivo = $_FILES['imagen']['type'];
            $temporal = $_FILES['imagen']['tmp_name'];

            // Tipos MIME válidos
            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($tipo_archivo, $tipos_permitidos)) {
                MainModel::jsonResponse("simple", "Tipo inválido", "Solo se permiten imágenes JPEG, PNG o WebP.", "warning");
            }

            // Crear nombre único
            $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
            $nuevo_nombre = 'usuario_' . uniqid() . '.' . $extension;

            // Ruta del avatar
            $ruta_avatares = __DIR__ . "/../views/resources/{$codigo_institucion}/sedes/{$id_sede}/imagenes/avatares";

            // Crear carpeta si no existe
            if (!file_exists($ruta_avatares)) {
                mkdir($ruta_avatares, 0775, true);
            }

            // Mover archivo
            $ruta_destino = $ruta_avatares . '/' . $nuevo_nombre;

            if (move_uploaded_file($temporal, $ruta_destino)) {
                // ✅ Eliminar imagen anterior si no es "AvatarNone.png" y no está vacía
                if (!empty($usuario_actual['imagen']) && $usuario_actual['imagen'] !== "AvatarNone.png") {
                    $ruta_anterior = $ruta_avatares . '/' . $usuario_actual['imagen'];
                    if (file_exists($ruta_anterior)) {
                        unlink($ruta_anterior);
                    }
                }

                // ✅ Asignar nueva imagen
                $imagen_nombre = $nuevo_nombre;
            } else {
                MainModel::jsonResponse("simple", "Error", "No se pudo guardar la imagen del usuario.", "error");
            }
        }

        if ($usuario_actual['imagen'] != $imagen_nombre) {
            $cambios['imagen'] = $imagen_nombre;
        }

         if (empty($cambios)) {
            MainModel::jsonResponse("simple", "Sin cambios", "No se detectaron modificaciones en la información del usuario.", "info");
        }

        /*************************************************************** */

        $campos_sql = [];
        $valores_sql = [];

        foreach ($cambios as $campo => $valor) {
            $campos_sql[] = "$campo = :$campo";
            $valores_sql[$campo] = $valor;
        }

        $update_sql = "UPDATE usuarios SET " . implode(", ", $campos_sql) . " WHERE documento = :documento AND codigo_institucion = :codigo AND id_sede = :sede";
        $update_stmt = $conexion->prepare($update_sql);

        // Bind dinámico
        foreach ($valores_sql as $campo => $valor) {
            $update_stmt->bindValue(":$campo", $valor, PDO::PARAM_STR);
        }

        $update_stmt->bindValue(":documento", $documento, PDO::PARAM_STR);
        $update_stmt->bindValue(":codigo", $codigo_institucion, PDO::PARAM_STR);
        $update_stmt->bindValue(":sede", $id_sede, PDO::PARAM_STR);

        if ($update_stmt->execute()) {
            MainModel::jsonResponse("recargar", "Actualizado", "La información del usuario fue actualizada correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar la información del usuario.", "error");
        }
    
    }

    public static function Actualizar_grados_usuarios_controlador($datos){
        $documento   = MainModel::limpiar_cadenas($datos['documento'] ?? '');
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion'] ?? '');
        $id_grado      = MainModel::limpiar_cadenas($datos['id_grado'] ?? '');
        $id_grupo         = MainModel::limpiar_cadenas($datos['id_grupo'] ?? '');
        $id_sede     = MainModel::limpiar_cadenas($datos['id_sede'] ?? '');

        //validamos que no vengan vacios
        MainModel::validar_campos_obligatorios([
            'documento' => $documento,
            'institución' => $codigo_institucion,
            'id_grado' => $id_grado,
            'id_grupo' => $id_grupo,
            'sede' => $id_sede
        ]);

        $documento = MainModel::decryption($documento);
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);
        $id_grado = MainModel::decryption($id_grado);
        $id_grupo = MainModel::decryption($id_grupo);

        $consulta = "SELECT id_grado, id_grupo FROM matriculas 
        WHERE documento = :documento AND codigo_institucion = :codigo AND id_sede = :sede";

        $conexion = MainModel::conectar();
        $stmt = $conexion->prepare($consulta);
        $stmt->bindParam(":documento", $documento, PDO::PARAM_STR);
        $stmt->bindParam(":codigo", $codigo_institucion, PDO::PARAM_STR);
        $stmt->bindParam(":sede", $id_sede, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            MainModel::jsonResponse("simple", "Matrícula no encontrada", "No se encontró ninguna matrícula registrada para este estudiante.", "warning");
        }

        $matricula_actual = $stmt->fetch(PDO::FETCH_ASSOC);

        $cambios_matricula = [];

        if ($matricula_actual['id_grado'] != $id_grado) {
            $cambios_matricula['id_grado'] = $id_grado;
        }

        if ($matricula_actual['id_grupo'] != $id_grupo) {
            $cambios_matricula['id_grupo'] = $id_grupo;
        }

        if (empty($cambios_matricula)) {
            MainModel::jsonResponse("simple", "Sin cambios en la matrícula", "No hay cambios en el grado ni grupo para este estudiante.", "info");
        }

        $campos_sql = [];
        $valores_sql = [];

        foreach ($cambios_matricula as $campo => $valor) {
            $campos_sql[] = "$campo = :$campo";
            $valores_sql[$campo] = $valor;
        }

        $update_sql = "UPDATE matriculas SET " . implode(", ", $campos_sql) . " WHERE documento = :documento AND codigo_institucion = :codigo AND id_sede = :sede";
        $update_stmt = $conexion->prepare($update_sql);

        // Bind dinámico
        foreach ($valores_sql as $campo => $valor) {
            $update_stmt->bindValue(":$campo", $valor, PDO::PARAM_STR);
        }

        $update_stmt->bindValue(":documento", $documento, PDO::PARAM_STR);
        $update_stmt->bindValue(":codigo", $codigo_institucion, PDO::PARAM_STR);
        $update_stmt->bindValue(":sede", $id_sede, PDO::PARAM_STR);

        if ($update_stmt->execute()) {
            MainModel::jsonResponse("simple", "Grado y Grupo actualizados", "Los datos del grado o grupo fueron actualizados correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error al actualizar", "No se pudo actualizar el grado o el curso del usuario.", "error");
        }
    }

    public static function consultar_usuarios_sedes_controlador($id_sede, $codigo_institucion, $documentoUser)
    {
        $id_sede = MainModel::limpiar_cadenas($id_sede);
        $codigo_institucion = MainModel::limpiar_cadenas($codigo_institucion);
        $documentoUser = MainModel::limpiar_cadenas($documentoUser);
        $id_sede = MainModel::decryption($id_sede);
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $documentoUser = MainModel::decryption($documentoUser);
        // ✅ Realizamos la consulta usando ejecutar_consultas_simples
        $consulta = "
        SELECT 
            u.documento AS numero_documento,
            u.nombres AS nombre_usuario,
            u.correo AS correo_usuario,
            u.telefono AS telefono_usuario,
            r.descripcion AS rol,
            u.estado,
            u.id_rol,
            u.contrasena,
            m.id_matricula,
            u.id_sexo ,
            u.imagen,
            u.fecha_creacion, 
            g.nombre_grado AS grado, 
            gr.nombre_grupo AS grupo,
            g.id_grado,
            gr.id_grupo
        FROM usuarios u
        INNER JOIN roles r ON u.id_rol = r.id_rol
        LEFT JOIN matriculas m ON u.documento = m.documento
        LEFT JOIN grados g ON m.id_grado = g.id_grado
        LEFT JOIN grupos gr ON m.id_grupo = gr.id_grupo
        WHERE u.documento != '$documentoUser'
          AND u.codigo_institucion = '$codigo_institucion'
          AND u.id_sede = '$id_sede'
        ";

        $consulta_usuarios = MainModel::ejecutar_consultas_simples($consulta);

        $usuarios = [];

        while ($fila = $consulta_usuarios->fetch(PDO::FETCH_ASSOC)) {
            $fila['documento_encription'] = MainModel::encryption($fila['numero_documento']);
            $fila['id_sexo'] = MainModel::encryption($fila['id_sexo']);
            $fila['id_rol'] = MainModel::encryption($fila['id_rol']);
            $fila['estado'] = MainModel::encryption($fila['estado']);
            $fila['id_grado'] = MainModel::encryption($fila['id_grado']);
            $fila['id_grupo'] = MainModel::encryption($fila['id_grupo']);
            $fila['id_matricula'] = MainModel::encryption($fila['id_matricula']);
            $fila['estado_desecriptado'] = MainModel::decryption($fila['estado']);
            $fila['contrasena'] = MainModel::decryption($fila['contrasena']);
            $fila['confirmContrasena'] = $fila['contrasena'];
            $usuarios[] = $fila;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($usuarios);
    }

    public static function Cargar_documento_usuarios_controlador($datos){
        $documento   = MainModel::limpiar_cadenas($datos['documento'] ?? '');
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion'] ?? '');
        $id_sede     = MainModel::limpiar_cadenas($datos['id_sede'] ?? '');

        MainModel::validar_campos_obligatorios([
            'documento' => $documento,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        $documento = MainModel::decryption($documento);
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);

        //consultar id matricula uaurio

        $sql = "SELECT id_matricula FROM matriculas WHERE documento = :documento";
        $stmt = MainModel::conectar()->prepare($sql);
        $stmt->bindParam(":documento", $documento, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            MainModel::jsonResponse("simple", "Usuario no valido", "No se encontró el matricula relacionado con este usuario.", "error");
        }

        $matricula = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $id_matricula = $matricula['id_matricula'];

        // Ruta base fija porque ya existe en tu proyecto
        $ruta_base = __DIR__ . '/../views/resources';
        $ruta_institucion = "$ruta_base/$codigo_institucion";
        $ruta_sede = "$ruta_institucion/sedes/$id_sede";
        $ruta_usuario = "$ruta_sede/documentos";
        $ruta_imagenes = "$ruta_sede/imagenes";
        $ruta_avatares = "$ruta_imagenes/avatares";

        // Crear carpetas necesarias
        $carpetas = [$ruta_institucion, "$ruta_institucion/sedes", $ruta_sede, $ruta_usuario, $ruta_imagenes, $ruta_avatares];

        foreach ($carpetas as $carpeta) {
            if (!file_exists($carpeta)) {
                mkdir($carpeta, 0777, true);
            }
        }


        $documentos_info = [];
        if (isset($datos['documentos']) && is_array($datos['documentos'])) {
            $documentos_info = $datos['documentos'];
        } else {
            // Si no llegaron documentos personalizados
            MainModel::jsonResponse("simple", "Ocurrió un error inesperado", "Por favor selecciona uno o varios documentos", "warning");
            exit;
        }
        $formatosPermitidos = ["application/pdf"];
        $total = count($_FILES['documentos']['name'] ?? []);

        for ($i = 0; $i < $total; $i++) {
            // Validar archivo
            $tipoArchivo = $_FILES['documentos']['type'][$i]['archivo'] ?? '';
            $nombreArchivo = $_FILES['documentos']['name'][$i]['archivo'] ?? "Sin nombre";

            if (!in_array($tipoArchivo, $formatosPermitidos)) {
                MainModel::jsonResponse("simple", "Archivo inválido", "El archivo '$nombreArchivo' no es un PDF válido.", "warning");
                exit;
            }

            // Validar nombre personalizado
            $nombrePersonalizado = $documentos_info[$i]['nombrePersonalizado'] ?? '';
            if (trim($nombrePersonalizado) === '') {
                MainModel::jsonResponse("simple", "Nombre personalizado vacío", "El nombre personalizado del documento  $nombreArchivo no puede estar vacío.", "warning");
                exit;
            }
        }


        $carpetaDocumentoUsuario = "$ruta_usuario/$documento";

        if (!file_exists($carpetaDocumentoUsuario)) {
            mkdir($carpetaDocumentoUsuario, 0777, true);
        }

        $documentosGuardadas = [];
        $nombres_personalizados = [];

        for ($i = 0; $i < $total; $i++) {
            $nombreArchivoOriginal = $_FILES['documentos']['name'][$i]['archivo']; // ✅ quitamos ['archivo'] porque no aplica
            $nombrePersonalizado = $documentos_info[$i]['nombrePersonalizado'];
            $extension = pathinfo($nombreArchivoOriginal, PATHINFO_EXTENSION); // ✅ usamos variable correcta
            $nombreArchivo = uniqid("doc_", true) . "." . $extension;

            $rutaArchivo = $carpetaDocumentoUsuario . '/' . $nombreArchivo;
            $tmp_name = $_FILES['documentos']['tmp_name'][$i]['archivo']; // ✅ agregamos esto correctamente

            if (move_uploaded_file($tmp_name, $rutaArchivo)) {
                $documentosGuardadas[] = $nombreArchivo;
                $nombres_personalizados[] = $nombrePersonalizado; // ✅ corregimos clave incorrecta
            }
        }

        $sql = "SELECT nombres_documentos, descripcion_documentos FROM documentos_matricula WHERE id_matricula = :id";
        $stmt = MainModel::conectar()->prepare($sql);
        $stmt->bindParam(":id", $id_matricula, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            MainModel::jsonResponse("simple", "Matrícula inválida", "No se encontraron documentos para esta matrícula.", "error");
        }

        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        // Decodificar los arrays actuales
        $array_nombres = json_decode($datos['nombres_documentos'], true);
        $array_descripciones = json_decode($datos['descripcion_documentos'], true);

        // Asegurar que son arreglos válidos (por si vienen null)
        $array_nombres = is_array($array_nombres) ? $array_nombres : [];
        $array_descripciones = is_array($array_descripciones) ? $array_descripciones : [];

        // Agregar los nuevos documentos al final
        foreach ($documentosGuardadas as $index => $archivo) {
            $array_nombres[] = $archivo;
            $array_descripciones[] = $nombres_personalizados[$index] ?? "Sin descripción";
        }

        // Reindexar y codificar
        $array_nombres = array_values($array_nombres);
        $array_descripciones = array_values($array_descripciones);

        $nombres_json = json_encode($array_nombres, JSON_UNESCAPED_UNICODE);
        $descripciones_json = json_encode($array_descripciones, JSON_UNESCAPED_UNICODE);

        // Actualizar la base de datos
        $update = MainModel::conectar()->prepare("
                UPDATE documentos_matricula 
                SET nombres_documentos = :nombres, descripcion_documentos = :descripciones 
                WHERE id_matricula = :id
            ");

        $update->bindParam(":nombres", $nombres_json, PDO::PARAM_STR);
        $update->bindParam(":descripciones", $descripciones_json, PDO::PARAM_STR);
        $update->bindParam(":id", $id_matricula, PDO::PARAM_STR);



        if ($update->execute()) {
            MainModel::jsonResponse("simple", "Documentos guardados", "Los documentos se agregaron correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar la lista de documentos.", "error");
        }


    }

    public static function eliminar_usuarios_controlador($id_sede, $codigo_institucion, $documentoUser)
    {
        $documento_usuario_del = MainModel::limpiar_cadenas($documentoUser);
        $codigo_institucion = MainModel::limpiar_cadenas($codigo_institucion);
        $id_sede = MainModel::limpiar_cadenas($id_sede);
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);
        $documento_usuario_del = MainModel::decryption($documento_usuario_del);
        // Validar existencia del usuario
        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_usuario_del);

        $numero_documento = (int) $documento_usuario_del;
        // Ejecutar eliminación
        $eliminar = UsuariosModelos::eliminar_usuario_modelo($numero_documento, $codigo_institucion, $id_sede);

        if ($eliminar->rowCount() == 1) {
            MainModel::jsonResponse("simple", "Usuario desactivado", "El usuario ha sido desactivado correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error inesperado", "No se pudo desactivar  el usuario, intente nuevamente.", "error");
        }
    }

    public static function Consultar_documentos_usuarios_controlador($id_matricula)
    {
        // ✅ Limpiar y desencriptar el ID
        $id_matricula = MainModel::limpiar_cadenas($id_matricula);
        $id_matricula = MainModel::decryption($id_matricula);
        // ✅ Consultar los documentos
        $consulta = "SELECT * FROM documentos_matricula WHERE id_matricula = '$id_matricula'";
        $stmt = MainModel::ejecutar_consultas_simples($consulta);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $respuesta = [];
        if ($fila) {
            $nombres = json_decode($fila['nombres_documentos'], true);
            $descripciones = json_decode($fila['descripcion_documentos'], true);

            if (is_array($nombres) && is_array($descripciones)) {
                foreach ($nombres as $index => $nombre) {
                    $respuesta[] = [
                        'nombre' => $nombre,
                        'descripcion' => $descripciones[$index] ?? ''
                    ];
                }
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($respuesta);
    }

     public static function eliminar_documentos_estudiantes($datos)
    {
        $nombre_documento_user = MainModel::limpiar_cadenas($datos['nombre_documento_user']);
        $descripcion_documento_user = MainModel::limpiar_cadenas($datos['descripcion_documento_user']);
        $id_matricula = MainModel::limpiar_cadenas($datos['id_matricula']);
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion']);
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede']);
        $documento_usuario = MainModel::limpiar_cadenas($datos['documento_usuario']);

        $id_matricula = MainModel::decryption($id_matricula);
        $documento_usuario = MainModel::decryption($documento_usuario);


        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_usuario);

        $id_matricula = (int) $id_matricula;
        // Extraer el registro completo
        $sql = "SELECT nombres_documentos, descripcion_documentos FROM documentos_matricula WHERE id_matricula = :id";
        $stmt = MainModel::conectar()->prepare($sql);
        $stmt->bindParam(":id", $id_matricula, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            MainModel::jsonResponse("simple", "Matrícula inválida", "No se encontraron documentos para esta matrícula.", "error");
        }

        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        $array_nombres = json_decode($datos['nombres_documentos'], true);
        $array_descripciones = json_decode($datos['descripcion_documentos'], true);

        $encontrado = false;

        foreach ($array_nombres as $index => $nombre) {
            if ($nombre === $nombre_documento_user && isset($array_descripciones[$index]) && $array_descripciones[$index] === $descripcion_documento_user) {
                unset($array_nombres[$index]);
                unset($array_descripciones[$index]);
                $encontrado = true;
                break;
            }
        }

        $ruta_archivo = __DIR__."/../views/resources/{$codigo_institucion}/sedes/{$id_sede}/documentos/{$documento_usuario}/{$nombre_documento_user}";

        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo); // Elimina el archivo físico
        }

        if (!$encontrado) {
            MainModel::jsonResponse("simple", "Documento no encontrado", "No se encontró un documento que coincida exactamente.", "warning");
        }

        $array_nombres = array_values($array_nombres);
        $array_descripciones = array_values($array_descripciones);

        $nombres_json = json_encode($array_nombres, JSON_UNESCAPED_UNICODE);
        $descripciones_json = json_encode($array_descripciones, JSON_UNESCAPED_UNICODE);

        $update = MainModel::conectar()->prepare("
            UPDATE documentos_matricula 
            SET nombres_documentos = :nombres, descripcion_documentos = :descripciones 
            WHERE id_matricula = :id
        ");

        $update->bindParam(":nombres", $nombres_json, PDO::PARAM_STR);
        $update->bindParam(":descripciones", $descripciones_json, PDO::PARAM_STR);
        $update->bindParam(":id", $id_matricula, PDO::PARAM_STR);

        if ($update->execute()) {
            MainModel::jsonResponse("simple", "Documento eliminado", "El documento fue eliminado correctamente.", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "No se pudo actualizar la información.", "error");
        }
    }

    public static function extraer_acudientes_por_documento_controlador($documento_encriptado)
    {

        $documento_estudiante = MainModel::limpiar_cadenas($documento_encriptado);
        $documento_estudiante = MainModel::decryption($documento_estudiante);

        $consulta = "SELECT * FROM acudientes WHERE documento_estudiante = '$documento_estudiante'";
        $stmt = MainModel::ejecutar_consultas_simples($consulta);
        $acudientes = [];

        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fila['id_acudiente'] = MainModel::encryption($fila['id_acudiente']);
             $fila['sexo'] = MainModel::encryption($fila['sexo']);
             $fila['documento_estudiante_encriptado'] = MainModel::encryption($fila['documento_estudiante']);
            $acudientes[] = $fila;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($acudientes);
    }

    public static function eliminar_acudiente_controlador($datos)
    {
        // ✅ Validar y limpiar datos recibidos
        $id_acudiente = MainModel::limpiar_cadenas($datos['id_acudiente']);
        $documento_estudiante = MainModel::limpiar_cadenas($datos['documento_estudiante']);
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion']);
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede']);

        MainModel::validar_campos_obligatorios([
            'id_acudiente' => $id_acudiente,
            'documento_estudiante' => $documento_estudiante,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        // ✅ Desencriptar datos sensibles
        $id_acudiente = MainModel::decryption($id_acudiente);
        $documento_estudiante = MainModel::decryption($documento_estudiante);
    
        $consulta = "DELETE FROM acudientes 
                 WHERE id_acudiente = :id_acudiente 
                 AND documento_estudiante = :documento_estudiante 
                 AND codigo_institucion = :codigo_institucion 
                 AND id_sede = :id_sede";

        $stmt = MainModel::conectar()->prepare($consulta);
        $stmt->bindParam(':id_acudiente', $id_acudiente);
        $stmt->bindParam(':documento_estudiante', $documento_estudiante);
        $stmt->bindParam(':codigo_institucion', $codigo_institucion);
        $stmt->bindParam(':id_sede', $id_sede);

        // ✅ Respuesta
        if ($stmt->execute()) {
             MainModel::jsonResponse("simple", "Acudiente eliminado", "Acudiente eliminado correctamente", "success");
        } else {
            MainModel::jsonResponse("simple", "Error", "Error al intentar eliminar el acudiente.", "error");
        }
    }


    public static function editar_acudiente_controlador($datos){
        $id_acudiente   = MainModel::limpiar_cadenas($datos['id_acudiente'] ?? '');
        $nombres      = MainModel::limpiar_cadenas($datos['nombres'] ?? '');
        $telefono      = MainModel::limpiar_cadenas($datos['telefono'] ?? '');
        $correo    = MainModel::limpiar_cadenas($datos['correo'] ?? '');
        $direccion        = MainModel::limpiar_cadenas($datos['direccion'] ?? '');
        $parentesco  = MainModel::limpiar_cadenas($datos['parentesco'] ?? '');
        $sexo = MainModel::limpiar_cadenas($datos['sexo'] ?? '');
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion'] ?? '');
        $id_sede     = MainModel::limpiar_cadenas($datos['id_sede'] ?? '');

        MainModel::validar_campos_obligatorios([
            'id_acudiente' => $id_acudiente,
            'nombres' => $nombres,
            'correo' => $correo,
            'telefono' => $telefono,
            'sexo' => $sexo,
            'direccion' => $direccion,
            'parentesco' => $parentesco,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);
        $id_acudiente = MainModel::decryption($id_acudiente);
        $sexo = MainModel::decryption($sexo);

        $consulta = "SELECT * FROM acudientes WHERE id_acudiente = :id_acudiente";
        $stmt = MainModel::conectar()->prepare($consulta);
        $stmt->bindParam(":id_acudiente", $id_acudiente, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() <= 0) {
            MainModel::jsonResponse("simple", "No encontrado", "El acudiente no existe en el sistema.", "warning");
        }

        $acudiente_actual = $stmt->fetch(PDO::FETCH_ASSOC);

         $cambios = [];

            if ($acudiente_actual['nombres'] !== $nombres) {
                $cambios['nombres'] = $nombres;
            }

            if ($acudiente_actual['correo'] !== $correo) {
                $cambios['correo'] = $correo;
            }

            if ($acudiente_actual['telefono'] !== $telefono) {
                $cambios['telefono'] = $telefono;
            }

            if ($acudiente_actual['direccion'] !== $direccion) {
                $cambios['direccion'] = $direccion;
            }

            if ($acudiente_actual['parentesco'] !== $parentesco) {
                $cambios['parentesco'] = $parentesco;
            }

            if ($acudiente_actual['sexo'] !== $sexo) {
                $cambios['sexo'] = $sexo;
            }

            if (empty($cambios)) {
                MainModel::jsonResponse("simple", "Sin cambios", "No se detectaron modificaciones en los datos del acudiente.", "info");
            }

             $campos_sql = [];
            foreach ($cambios as $campo => $valor) {
                $campos_sql[] = "$campo = :$campo";
            }

            $update_sql = "UPDATE acudientes SET " . implode(", ", $campos_sql) . " WHERE id_acudiente = :id_acudiente";
            $update_stmt = MainModel::conectar()->prepare($update_sql);

            // Asignar valores dinámicamente
            foreach ($cambios as $campo => $valor) {
                $update_stmt->bindValue(":$campo", $valor, PDO::PARAM_STR);
            }

            $update_stmt->bindValue(":id_acudiente", $id_acudiente, PDO::PARAM_STR);

            if ($update_stmt->execute()) {
                MainModel::jsonResponse("simple", "Actualizado", "La información del acudiente fue actualizada correctamente.", "success");
            } else {
                MainModel::jsonResponse("simple", "Error", "No se pudo actualizar la información del acudiente.", "error");
            }

    }

    public static function agregar_acudiente_controlador($datos){
        $documento_estudiante   = MainModel::limpiar_cadenas($datos['documento_estudiante'] ?? '');
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion'] ?? '');
        $id_sede     = MainModel::limpiar_cadenas($datos['id_sede'] ?? '');
        MainModel::validar_campos_obligatorios([
            'documento' => $documento_estudiante,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);
      
        $documento_estudiante = MainModel::decryption($documento_estudiante);

        $acudientes_data = [];

        if (isset($datos['acudientes'])) {
            $acudientes_data = json_decode($datos['acudientes'], true); // ✅ decodificamos el JSON string

            if (!is_array($acudientes_data)) {
                MainModel::jsonResponse("simple", "Error", "Los datos de los acudientes no son válidos.", "warning");
                exit;
            }
        } else {
            MainModel::jsonResponse("simple", "Ocurrió un error inesperado", "No se han recibido los datos de los acudientes.", "warning");
            exit;
        }

        $camposObligatorios = ['nombres', 'correo', 'telefono', 'direccion', 'numeroDocumento', 'parentesco', 'sexo', 'contrasena'];

        foreach ($acudientes_data as $index => $acudiente) {
            foreach ($camposObligatorios as $campo) {
                if (!isset($acudiente[$campo]) || trim($acudiente[$campo]) === '') {
                    $nombreCampo = ucfirst(str_replace('numeroDocumento', 'número de documento', $campo));
                    MainModel::jsonResponse(
                        "simple",
                        "Campo vacío",
                        "El campo ($nombreCampo) está vacío en el (acudiente " . ($index + 1) . "). Por favor complétalo.",
                        "warning"
                    );
                    exit;
                }
            }
        }

        // Recorrer y agregar los nuevos campos
        foreach ($acudientes_data as $index => $acudiente) {
            $acudientes_data[$index]['codigo_institucion'] = $codigo_institucion;
            $acudientes_data[$index]['id_sede'] = $id_sede;
            $acudientes_data[$index]['fecha_creacion'] = date("Y-m-d H:i:s");
            $acudientes_data[$index]['estado'] = true;
            $acudientes_data[$index]['imagen'] = "AvatarNone.png"; // ✅ Valor por defecto
            $acudientes_data[$index]['documento_estudiante'] = "$documento_estudiante"; // ✅ Valor por defecto
        }

        foreach ($acudientes_data as $acudiente) {

            $documento = MainModel::limpiar_cadenas($acudiente['numeroDocumento']);

            $verificar = MainModel::ejecutar_consultas_simples("SELECT id_acudiente FROM acudientes WHERE numero_documento = '$documento' AND documento_estudiante = '$documento_estudiante'");

            if ($verificar->rowCount() > 0) {
                // Ya existe → responder con mensaje o continuar
                MainModel::jsonResponse("simple", "Atención", "Ya existe un acudiente con ese documento para este estudiante: " . $documento, "warning");
                continue; 
            }
            $acudiente['contrasena'] = MainModel::encryption($acudiente['contrasena']);

            $acudiente['sexo'] = MainModel::decryption($acudiente['sexo']);
            
            $guardar = UsuariosModelos::agregar_acudiente_modelo($acudiente);

            if ($guardar->rowCount() <= 0) {
                MainModel::jsonResponse("simple", "Error", "No se pudo guardar el acudiente: " . $acudiente['nombres'], "error");
                exit();
            }
        }

        if ($guardar->rowCount() >= 0) {
            MainModel::jsonResponse("simple", "Acudiente Registrado", "El acudiente se registró correctamente", "success");
        }


         
    }

    public static function Editar_imagens_usuarios_controlador($datos)
    {
        $id_sede     = MainModel::limpiar_cadenas($datos['id_sede'] ?? '');
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion'] ?? '');
        $documento   = MainModel::limpiar_cadenas($datos['documento'] ?? '');

        MainModel::validar_campos_obligatorios([
            'documento' => $documento,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento);

        // 1. Obtener imagen actual desde la base de datos
        $conexion = MainModel::conectar();
        $sql_select = "SELECT imagen FROM usuarios WHERE documento = :documento LIMIT 1";
        $stmt_select = $conexion->prepare($sql_select);
        $stmt_select->bindParam(':documento', $documento, PDO::PARAM_STR);
        $stmt_select->execute();
        $resultado = $stmt_select->fetch(PDO::FETCH_ASSOC);

        $imagen_actual = $resultado['imagen'] ?? 'AvatarNone.png';

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

        // 3. Definir rutas
        $ruta_base = __DIR__ . '/../views/resources';
        $ruta_institucion = "$ruta_base/$codigo_institucion";
        $ruta_sede = "$ruta_institucion/sedes/$id_sede";
        $ruta_imagenes = "$ruta_sede/imagenes";
        $ruta_avatares = "$ruta_imagenes/avatares";

        // Crear carpetas si no existen
        $carpetas = [$ruta_institucion, "$ruta_institucion/sedes", $ruta_sede, $ruta_imagenes, $ruta_avatares];
        foreach ($carpetas as $carpeta) {
            if (!file_exists($carpeta)) {
                mkdir($carpeta, 0777, true);
            }
        }

        // 4. Generar nuevo nombre único para la imagen
        $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
        $nuevo_nombre = 'usuario_' . uniqid() . '.' . $extension;
        $ruta_destino = $ruta_avatares . '/' . $nuevo_nombre;

        // 5. Eliminar imagen anterior (si no es AvatarNone.png)
        if ($imagen_actual !== 'AvatarNone.png') {
            $ruta_actual = $ruta_avatares . '/' . $imagen_actual;
            if (file_exists($ruta_actual)) {
                unlink($ruta_actual); // Elimina la imagen anterior
            }
        }

        // 6. Subir la nueva imagen
        if (!move_uploaded_file($temporal, $ruta_destino)) {
            return MainModel::jsonResponse("simple", "Error", "No se pudo guardar la nueva imagen.", "error");
        }

        // 7. Actualizar la base de datos
        $sql_update = "UPDATE usuarios SET imagen = :imagen WHERE documento = :documento";
        $stmt_update = $conexion->prepare($sql_update);
        $stmt_update->bindParam(':imagen', $nuevo_nombre, PDO::PARAM_STR);
        $stmt_update->bindParam(':documento', $documento, PDO::PARAM_STR);

        if ($stmt_update->execute()) {
            return MainModel::jsonResponse("simple", "Éxito", "La imagen se actualizó correctamente.", "success");
        } else {
            return MainModel::jsonResponse("simple", "Error", "No se pudo actualizar la base de datos.", "error");
        }
    }

    public static function Editar_usuarios_controlador($datos)
    {
        $nombres     = MainModel::limpiar_cadenas($datos['nombres'] ?? '');
        $correo      = MainModel::limpiar_cadenas($datos['correo'] ?? '');
        $telefono    = MainModel::limpiar_cadenas($datos['telefono'] ?? '');
        $password    = MainModel::limpiar_cadenas($datos['password'] ?? '');
        $confirmPassword = MainModel::limpiar_cadenas($datos['confirmPassword'] ?? '');
        $id_sede     = MainModel::limpiar_cadenas($datos['id_sede'] ?? '');
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion'] ?? '');
        $documento   = MainModel::limpiar_cadenas($datos['documento'] ?? '');

        MainModel::validar_campos_obligatorios([
            'documento' => $documento,
            'correo' => $correo,
            'nombres' => $nombres,
            'telefono' => $telefono,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);

        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento);

        $conexion = MainModel::conectar();

        // 1. Obtener datos actuales del usuario
        $sql_actual = "SELECT nombres, correo, telefono FROM usuarios WHERE documento = :documento LIMIT 1";
        $stmt_actual = $conexion->prepare($sql_actual);
        $stmt_actual->bindParam(':documento', $documento);
        $stmt_actual->execute();
        $datos_actuales = $stmt_actual->fetch(PDO::FETCH_ASSOC);

        if (!$datos_actuales) {
            return MainModel::jsonResponse("simple", "Usuario no encontrado", "No se encontró el usuario para editar.", "error");
        }

        // 2. Comparar si hay cambios
        $cambios = false;
        if ($nombres !== $datos_actuales['nombres']) $cambios = true;
        if ($correo !== $datos_actuales['correo'])   $cambios = true;
        if ($telefono !== $datos_actuales['telefono']) $cambios = true;

        // 3. Si la contraseña viene vacía
        $actualizarPassword = false;
        $password_encriptada = '';


        if ( isset($password, $confirmPassword) &&
            $password !== '' && $confirmPassword !== '' &&
            $password !== null && $confirmPassword !== null
        ) {
            if ($password !== $confirmPassword) {
                return MainModel::jsonResponse("simple", "Contraseñas no coinciden", "Debes confirmar correctamente la nueva contraseña.", "warning");
            }

            $actualizarPassword = true;
            $cambios = true;
            $password_encriptada = MainModel::encryption($password);
        }


        if (!$cambios) {
            return MainModel::jsonResponse("simple", "Sin cambios", "No se detectaron modificaciones en los datos.", "info");
        }

        // 4. Preparar SQL dinámico
        $campos = "nombres = :nombres, correo = :correo, telefono = :telefono";
        if ($actualizarPassword) {
            $campos .= ", contrasena = :password";
        }

        $sql_update = "UPDATE usuarios SET $campos WHERE documento = :documento LIMIT 1";
        $stmt_update = $conexion->prepare($sql_update);
        $stmt_update->bindParam(':nombres', $nombres);
        $stmt_update->bindParam(':correo', $correo);
        $stmt_update->bindParam(':telefono', $telefono);
        $stmt_update->bindParam(':documento', $documento);
        if ($actualizarPassword) {
            $stmt_update->bindParam(':password', $password_encriptada);
        }

        if ($stmt_update->execute()) {

            // CONSULTAR LOS NUEVOS DATOS DEL USUARIO
            $sql_usuario = "
                SELECT 
                    u.nombres, 
                    u.correo, 
                    u.telefono, 
                    u.id_sexo, 
                    s.descripcion AS sexo,
                    u.id_rol, 
                    r.descripcion AS rol,
                    u.imagen, 
                    u.estado,
                    e.descripcion AS estado_texto,
                    u.documento
                FROM usuarios u
                LEFT JOIN sexos s ON u.id_sexo = s.id_sexo
                LEFT JOIN roles r ON u.id_rol = r.id_rol
                LEFT JOIN estados e ON u.estado = e.id_estado
                WHERE u.documento = :documento
                LIMIT 1
            ";

            $stmt_usuario = $conexion->prepare($sql_usuario);
            $stmt_usuario->bindParam(':documento', $documento);
            $stmt_usuario->execute();
            $usuario_actualizado = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

            return json_encode([
                "Alerta" => "simple",
                "Titulo" => "Actualización exitosa",
                "Texto" => "Los datos del usuario han sido actualizados correctamente.",
                "Tipo" => "success",
                "usuario" => $usuario_actualizado
            ]);
        } else {
            return MainModel::jsonResponse("simple", "Error", "No se pudo actualizar la información.", "error");
        }
    }
}
