<?php

require_once __DIR__ . '/MainModel.php';

class profesoresModelo extends MainModel{

    public static function eliminar_grados_asignados_profesores($datos)
    {

        $conexion = MainModel::conectar();

        $sql = "DELETE FROM materias_asigandas_profesores 
            WHERE documento_docente = :documento_docente 
            AND id_grado = :id_grado 
            AND id_sede = :id_sede 
            AND codigo_institucion = :codigo_institucion";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":documento_docente", $datos['documento_docente']);
        $stmt->bindParam(":id_grado", $datos['id_grado']);
        $stmt->bindParam(":id_sede", $datos['id_sede']);
        $stmt->bindParam(":codigo_institucion", $datos['codigo_institucion']);

       return $stmt->execute();
    }

     public static function eliminar_grupos_asignados_profesores($datos)
    {

        $conexion = MainModel::conectar();

        $sql = "DELETE FROM materias_asigandas_profesores 
            WHERE documento_docente = :documento_docente 
            AND id_grupo = :id_grupo 
            AND id_sede = :id_sede 
            AND codigo_institucion = :codigo_institucion";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":documento_docente", $datos['documento_docente']);
        $stmt->bindParam(":id_grupo", $datos['id_grupo']);
        $stmt->bindParam(":id_sede", $datos['id_sede']);
        $stmt->bindParam(":codigo_institucion", $datos['codigo_institucion']);

       return $stmt->execute();
    }

     public static function eliminar_materia_asignados_profesores($datos)
    {

        $conexion = MainModel::conectar();

        $sql = "DELETE FROM materias_asigandas_profesores 
            WHERE documento_docente = :documento_docente 
            AND id_materia = :id_materia 
            AND id_sede = :id_sede 
            AND codigo_institucion = :codigo_institucion";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":documento_docente", $datos['documento_docente']);
        $stmt->bindParam(":id_materia", $datos['id_materia']);
        $stmt->bindParam(":id_sede", $datos['id_sede']);
        $stmt->bindParam(":codigo_institucion", $datos['codigo_institucion']);

       return $stmt->execute();
    }


    public static function registrar_temas_academicos_modelos($datos)
    {
        $conexion = MainModel::conectar();

        $sql = "INSERT INTO temas_materia 
    (id_materia, documento_docente, id_grado, id_grupo, titulo_tema, 
    descripcion, orden, estado, codigo_institucion, id_sede) 
    VALUES (:id_materia, :documento_docente, :id_grado, :id_grupo, :titulo_tema, 
    :descripcion, :orden, 'activo', :codigo_institucion, :id_sede)";

        $sql = $conexion->prepare($sql);

        $sql->bindParam(":id_materia",  $datos['id_materia']);
        $sql->bindParam(":documento_docente", $datos['documento_docente']);
        $sql->bindParam(":id_grado", $datos['id_grado']);
        $sql->bindParam(":id_grupo", $datos['id_grupo']);
        $sql->bindParam(":titulo_tema", $datos['titulo_tema']);
        $sql->bindParam(":descripcion", $datos['descripcion']);
        $sql->bindParam(":orden", $datos['orden']);
        $sql->bindParam(":codigo_institucion", $datos['codigo_institucion']);
        $sql->bindParam(":id_sede", $datos['id_sede']);

        return $sql->execute();
    }

    

}