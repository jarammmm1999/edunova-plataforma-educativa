<?php

require_once __DIR__ . '/MainModel.php';

class UsuariosModelos extends MainModel{
    
    public static function agregar_usuario_modelo($datos)
    {
        $sql = MainModel::conectar()->prepare("
        INSERT INTO usuarios (
            documento,
            nombres,
            correo,
            telefono,
            id_sexo,
            imagen,
            estado,
            contrasena,
            codigo_institucion,
            id_rol,
            id_sede,
            fecha_creacion
        ) VALUES (
            :documento,
            :nombres,
            :correo,
            :telefono,
            :id_sexo,
            :imagen,
            :estado,
            :contrasena,
            :codigo_institucion,
            :id_rol,
            :id_sede,
            :fecha_creacion
        )
        ");

        $sql->bindParam(":documento",           $datos['documento']);
        $sql->bindParam(":nombres",             $datos['nombres']);
        $sql->bindParam(":correo",              $datos['correo']);
        $sql->bindParam(":telefono",            $datos['telefono']);
        $sql->bindParam(":id_sexo",             $datos['id_sexo']);
        $sql->bindParam(":imagen",              $datos['imagen']);
        $sql->bindParam(":estado",              $datos['estado']);
        $sql->bindParam(":contrasena",          $datos['contrasena']);
        $sql->bindParam(":codigo_institucion",  $datos['codigo_institucion']);
        $sql->bindParam(":id_rol",              $datos['id_rol']);
        $sql->bindParam(":id_sede",             $datos['id_sede']);
        $sql->bindParam(":fecha_creacion",      $datos['fecha_creacion']);

        return $sql->execute();
    }


     public static function registrar_matricula_modelo($data) {
        try {
            $sql = MainModel::conectar()->prepare("
                INSERT INTO matriculas (
                    documento,
                    codigo_institucion,
                    id_grado,
                    id_grupo,
                    id_sede,
                    fecha_matricula,
                    observaciones
                ) VALUES (
                    :documento,
                    :codigo_institucion,
                    :id_grado,
                    :id_grupo,
                    :id_sede,
                    :fecha_matricula,
                    :observaciones
                )
            ");

            $sql->bindParam(":documento", $data['documento']);
            $sql->bindParam(":codigo_institucion", $data['codigo_institucion']);
            $sql->bindParam(":id_grado", $data['id_grado']);
            $sql->bindParam(":id_grupo", $data['id_grupo']);
            $sql->bindParam(":id_sede", $data['id_sede']);
            $sql->bindParam(":fecha_matricula", $data['fecha_matricula']);
            $sql->bindParam(":observaciones", $data['observaciones']);

            $sql->execute();
            return true;

        } catch (PDOException $e) {
            // Puedes hacer log o mostrar el error
            return false;
        }
    }

    protected static function agregar_acudiente_modelo($datos) {
        $sql = MainModel::conectar()->prepare("
            INSERT INTO acudientes(
                nombres, correo, telefono, direccion,numero_documento, sexo, contrasena, imagen, estado, 
                fecha_creacion, codigo_institucion, id_sede,parentesco,documento_estudiante
            ) VALUES (
                :nombres, :correo, :telefono, :direccion,
                :numero_documento, :sexo, :contrasena, :imagen, :estado,
                :fecha_creacion, :codigo_institucion, :id_sede, :parentesco, :documento_estudiante
            )
        ");

        $sql->bindParam(":nombres", $datos['nombres']);
        $sql->bindParam(":correo", $datos['correo']);
        $sql->bindParam(":telefono", $datos['telefono']);
        $sql->bindParam(":direccion", $datos['direccion']);
        $sql->bindParam(":numero_documento", $datos['numeroDocumento']);
        $sql->bindParam(":sexo", $datos['sexo']);
        $sql->bindParam(":contrasena", $datos['contrasena']);
        $sql->bindParam(":imagen", $datos['imagen']);
        $sql->bindParam(":estado", $datos['estado']);
        $sql->bindParam(":fecha_creacion", $datos['fecha_creacion']);
        $sql->bindParam(":codigo_institucion", $datos['codigo_institucion']);
        $sql->bindParam(":id_sede", $datos['id_sede']);
        $sql->bindParam(":parentesco", $datos['parentesco']);
        $sql->bindParam(":documento_estudiante", $datos['documento_estudiante']);


        try {
            $sql->execute();
            return $sql;
        } catch (PDOException $e) {
            echo "❌ Error al ejecutar: " . $e->getMessage();
            exit();
        }
    }

     public static function registrar_documentos_matricula_modelo($data) {
        try {
            $sql = MainModel::conectar()->prepare("
                INSERT INTO documentos_matricula (
                    id_matricula,
                    nombres_documentos,
                    descripcion_documentos,
                    entregado,
                    fecha_entrega
                ) VALUES (
                    :id_matricula,
                    :nombres_documentos,
                    :descripcion_documentos,
                    :entregado,
                    :fecha_entrega
                )
            ");

            $sql->bindParam(":id_matricula", $data['id_matricula'], PDO::PARAM_INT);
            $sql->bindParam(":nombres_documentos", $data['nombres_documentos'], PDO::PARAM_STR);
            $sql->bindParam(":descripcion_documentos", $data['descripcion_documentos'], PDO::PARAM_STR);
            $sql->bindParam(":entregado", $data['entregado'], PDO::PARAM_BOOL);
            $sql->bindParam(":fecha_entrega", $data['fecha_entrega']);

            $sql->execute();
            return true;

        } catch (PDOException $e) {
            // Registrar o depurar el error si lo necesitas
            return false;
        }
    }

     protected static function eliminar_usuario_modelo($documento, $codigo_institucion, $id_sede)
    {
        $sql = MainModel::conectar()->prepare("
        UPDATE usuarios 
        SET estado = 2 
        WHERE documento = :documento 
        AND codigo_institucion = :codigo_institucion 
        AND id_sede = :id_sede
    ");

        $sql->bindParam(":documento", $documento);
        $sql->bindParam(":codigo_institucion", $codigo_institucion);
        $sql->bindParam(":id_sede", $id_sede);

        $sql->execute();
        return $sql;
    }
}