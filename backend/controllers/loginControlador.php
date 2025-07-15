<?php

require_once __DIR__ . '/../models/loginModelo.php';



class LoginControlador extends LoginModelo
{

    public static function extraer_informacion_sede_controlador($id_sede)
    {
        $id_sede = MainModel::decryption($id_sede); // Si viene encriptado
        $query = "SELECT * FROM sedes WHERE id_sede = '$id_sede'";
        $stmt = MainModel::ejecutar_consultas_simples($query);

        $sede = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fila['colores_sede'] = json_decode($fila['colores_sede'], true);
            // Agregar campos encriptados
            $fila['id_sede_encriptado'] = MainModel::encryption($fila['id_sede']);
            $fila['codigo_institucion_encriptado'] = MainModel::encryption($fila['codigo_institucion']);
            $sede[] = $fila;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($sede[0]);
    }

    public static function obtener_imagenes_por_sede_controlador($id_sede)
{
    $id_sede = MainModel::decryption($id_sede); // Solo si está encriptado

    $conexion = self::conectar();
    $query = "SELECT nombre_imagenes FROM imagenes_portada WHERE id_sede = :id_sede AND estado = '1'";
    $stmt = $conexion->prepare($query);
    $stmt->bindParam(':id_sede', $id_sede, PDO::PARAM_INT);
    $stmt->execute();

    $imagenes = [];

    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($fila['nombre_imagenes'])) {
            // Convertir el campo JSON a arreglo
            $imagenesDecodificadas = json_decode($fila['nombre_imagenes'], true);

            // Asegurar que sea un arreglo y fusionarlo con el resultado
            if (is_array($imagenesDecodificadas)) {
                $imagenes = array_merge($imagenes, $imagenesDecodificadas);
            }
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($imagenes);
}

    public static function iniciar_sesion_controlador($datos)
    {
        $documento = MainModel::limpiar_cadenas($datos['usuario']);
        $contrasena = MainModel::limpiar_cadenas($datos['contrasena']);
        $id_sede = MainModel::limpiar_cadenas($datos['id_sede']);
        $codigo_institucion = MainModel::limpiar_cadenas($datos['codigo_institucion']);
        MainModel::validar_campos_obligatorios([
            'usuario' => $documento,
            'contrasena' => $contrasena,
            'institución' => $codigo_institucion,
            'sede' => $id_sede
        ]);
        $codigo_institucion = MainModel::decryption($codigo_institucion);
        $id_sede = MainModel::decryption($id_sede);
        $contrasena = MainModel::encryption($contrasena);

        MainModel::validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento);
        
        $datos = [
            "numero_documento" => $documento,
            "contrasena_usuario" => $contrasena,
            "codigo_institucion" => $codigo_institucion,
            "id_sede" => $id_sede,
        ];

        

        $respuesta = LoginModelo::iniciar_session_modelo($datos);

        switch ($respuesta['status']) {
            case 'ok':
            
                $usuario = $respuesta['usuario']; 

                $datos_usuario = [
                    "numero_documento"     => MainModel::encryption($usuario['documento']),
                    "codigo_institucion"   => MainModel::encryption($codigo_institucion),
                    "id_sede"              => MainModel::encryption($id_sede),
                    "token_usuario"        => bin2hex(random_bytes(32))
                ];

                echo json_encode([
                    "Alerta" => "redireccionar",
                    "datos"  => $datos_usuario,
                    "URL" =>  "/home"
                ]);
                exit();
                break;
            case 'bloqueado':
            case 'error':
            case 'institucion_invalida':
            case 'sede_invalida':
                MainModel::jsonResponse("simple", $respuesta['status'], $respuesta['mensaje'], "warning");
                break;
            default:
                MainModel::jsonResponse("simple", "error", "Ocurrió un error inesperado", "error");
                break;
        }
    }


    public static function obtener_usuario_por_documento_controlador($documento_encriptado)
    {
        $documento = MainModel::decryption($documento_encriptado);
        $conexion = self::conectar();

        $query = "
            SELECT 
                u.documento,
                u.nombres, 
                u.correo, 
                u.telefono, 
                u.id_sexo, 
                s.descripcion AS sexo,
                u.id_rol, 
                r.descripcion AS rol,
                u.imagen, 
                u.estado,
                e.descripcion AS estado_texto
            FROM usuarios u
            LEFT JOIN sexos s ON u.id_sexo = s.id_sexo
            LEFT JOIN roles r ON u.id_rol = r.id_rol
            LEFT JOIN estados e ON u.estado = e.id_estado
            WHERE u.documento = :documento
            LIMIT 1
        ";


        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':documento', $documento, PDO::PARAM_STR);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$usuario) {
            echo json_encode(["error" => "Usuario no encontrado"]);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($usuario);
    }

}
