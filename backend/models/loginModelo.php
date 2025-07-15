<?php

require_once __DIR__ . '/MainModel.php';

class LoginModelo extends MainModel{
     public static function iniciar_session_modelo($datos) {
        $conexion = MainModel::conectar();
    
        // 1. Buscar al usuario solo por documento y contraseña
        $consulta = $conexion->prepare("
            SELECT * FROM usuarios 
            WHERE documento = :numero_documento 
              AND contrasena = :contrasena_usuario
        ");
    
        $consulta->bindParam(':numero_documento', $datos['numero_documento']);
        $consulta->bindParam(':contrasena_usuario', $datos['contrasena_usuario']);
        $consulta->execute();
    
        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);
    
        if (!$usuario) {
            return [
                "status" => "error",
                "mensaje" => "Credenciales incorrectas"
            ];
        }
    
        $nombre = htmlspecialchars($usuario['nombres']);
    
        if ((int)$usuario['estado'] !== 1) {
            return [
                "status" => "bloqueado",
                "mensaje" => "El usuario $nombre tiene su cuenta bloqueada o inactiva"
            ];
        }
    
        if ((int)$usuario['codigo_institucion'] !== (int)$datos['codigo_institucion']) {
            return [
                "status" => "institucion_invalida",
                "mensaje" => "El usuario $nombre no pertenece a esta institución"
            ];
        }
    
        if ((int)$usuario['id_sede'] !== (int)$datos['id_sede']) {
            return [
                "status" => "sede_invalida",
                "mensaje" => "El usuario $nombre no pertenece a esta sede"
            ];
        }
    
        return [
            "status" => "ok",
            "usuario" => $usuario
        ];
    }
}