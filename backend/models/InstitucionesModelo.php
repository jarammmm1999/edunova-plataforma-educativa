<?php

require_once __DIR__ . '/MainModel.php';

class InstitucionesModelo extends MainModel{

    protected static function guardar_materia($datos) {
        $sql = "INSERT INTO materias (nombre_materia, codigo_institucion, id_sede, estado)
                VALUES (:nombre, :codigo_institucion, :id_sede, :estado)";

        $stmt = self::conectar()->prepare($sql);
        $stmt->bindParam(":nombre", $datos['nombre_materia'], PDO::PARAM_STR);
        $stmt->bindParam(":codigo_institucion", $datos['codigo_institucion'], PDO::PARAM_STR);
        $stmt->bindParam(":id_sede", $datos['id_sede'], PDO::PARAM_STR);
        $stmt->bindParam(":estado", $datos['estado'], PDO::PARAM_INT);

        $stmt->execute();
        return $stmt;
    }
    

    protected static function guardar_grados($datos) {
        $sql = "INSERT INTO grados (nombre_grado, codigo_institucion, id_sede)
                VALUES (:nombre, :codigo_institucion, :id_sede)";

        $stmt = self::conectar()->prepare($sql);
        $stmt->bindParam(":nombre", $datos['nombre_grado'], PDO::PARAM_STR);
        $stmt->bindParam(":codigo_institucion", $datos['codigo_institucion'], PDO::PARAM_STR);
        $stmt->bindParam(":id_sede", $datos['id_sede'], PDO::PARAM_STR);
        $stmt->execute();
        return $stmt;
    }

     protected static function guardar_periodos_academicos($datos) {

        
        $sql = "INSERT INTO periodos_academicos (nombre_periodo,fecha_inicio, fecha_fin, codigo_institucion, id_sede, activo)
                VALUES (:nombre_periodo,:fecha_inicio,:fecha_fin,:codigo_institucion, :id_sede, :activo)";

        $activo = true;
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindParam(":nombre_periodo", $datos['nombre_periodo'], PDO::PARAM_STR);
        $stmt->bindParam(":fecha_inicio", $datos['fecha_inicio'], PDO::PARAM_STR);
        $stmt->bindParam(":fecha_fin", $datos['fecha_fin'], PDO::PARAM_STR);
        $stmt->bindParam(":codigo_institucion", $datos['codigo_institucion'], PDO::PARAM_STR);
        $stmt->bindParam(":id_sede", $datos['id_sede'], PDO::PARAM_STR);
        $stmt->bindParam(":activo", $activo, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt;
    }
}