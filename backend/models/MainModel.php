<?php

require_once __DIR__ . '/../config/Server.php';


class MainModel {
    // Función para conectarse a la base de datos
    protected static function conectar(){
        $conexion = new PDO('mysql:host='.SERVER.';dbname='.DB.'',USER, PASS);
        $conexion->exec("SET CHARACTER SET utf8 ");
        return $conexion;
    }

    Protected static function ejecutar_consultas_simples($consulta){
        $sql = self::conectar()->prepare($consulta);
        $sql->execute();
        return $sql;
    }

 
    public static function encryption($string){
        $output=FALSE;
        $key=hash('sha256', SECRET_KEY);
        $iv=substr(hash('sha256', SECRET_IV), 0, 16);
        $output=openssl_encrypt($string, METHOD, $key, 0, $iv);
        $output=base64_encode($output);
        return $output;
    }


    public static function decryption($string){
        $key=hash('sha256', SECRET_KEY);
        $iv=substr(hash('sha256', SECRET_IV), 0, 16);
        $output=openssl_decrypt(base64_decode($string), METHOD, $key, 0, $iv);
        return $output;
    }

       
    protected static function ocultar_contrasena($contrasena) {
        $longitud = strlen($contrasena); // Longitud de la contraseña
        if ($longitud > 3) {
            $ocultos = str_repeat('*', $longitud - 3); // Generar los asteriscos
            $visibles = substr($contrasena, -3); // Obtener los últimos 3 caracteres
            return $ocultos . $visibles; // Combinar los asteriscos con los últimos caracteres
        }
        return $contrasena; // Si la contraseña es muy corta, no la modifica
    }

    protected static function  generar_codigo_aleatorios($letra,$longitud,$numero){
        for($i=1;$i<$longitud;$i++){
            $aleatorio=rand(0,9);
            $letra.=$aleatorio;
        }
        return $letra."-".$numero;
    }

    public static function jsonResponse($alerta, $titulo, $texto, $tipo, $extra = [])
    {
        $response = [
            "Alerta" => $alerta,
            "Titulo" => $titulo,
            "Texto"  => $texto,
            "Tipo"   => $tipo
        ];

        // Agrega datos extra si los hay
        if (!empty($extra) && is_array($extra)) {
            $response = array_merge($response, $extra);
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }


    public static function comparar_claves($contrasena_guardada, $contrasena_ingresada_encriptada)
    {
        return $contrasena_guardada === $contrasena_ingresada_encriptada;
    }


    protected static function limpiar_cadenas($cadena) {
        // Eliminar espacios en blanco al inicio y al final
        $cadena = trim($cadena);
        // Convertir caracteres especiales a entidades HTML para prevenir XSS
        $cadena = htmlspecialchars($cadena, ENT_QUOTES, 'UTF-8');
        // Eliminar posibles inyecciones SQL, pero se recomienda utilizar prepared statements
        $cadena = preg_replace('/\b(SELECT|DELETE|INSERT|UPDATE|DROP|SHOW|TRUNCATE)\b/i', '', $cadena);
        // Eliminar cualquier rastro de PHP o scripts
        $cadena = preg_replace('/<\?php.*?\?>/i', '', $cadena);  // Eliminar cualquier rastro de PHP
        $cadena = preg_replace('/<script.*?<\/script>/i', '', $cadena);  // Eliminar cualquier script
        // Eliminar posibles caracteres peligrosos adicionales
        $cadena = str_replace(array("'", "\"", "--", ";", ">", "<", "[", "]", "^", "==", "::"), '', $cadena);
        // Eliminar barras invertidas
        // Reemplazar múltiples espacios con uno solo
        $cadena = preg_replace('/\s+/', ' ', $cadena);
        $cadena = stripslashes($cadena);
    
        return $cadena;
    }
    
    protected static function verificar_datos($filtro,$cadena){
        if(preg_match("/^".$filtro."$/",$cadena)){
            return false;
        }else{
            return true;
        }
    }

    protected static function verificar_fecha($fecha){
        $valores=explode('-',$fecha);
        if(count($valores)==3 && checkdate($valores[1],$valores[2],$valores[3])){
            return false;
        }else{
            return true;
        }
    }
	
    /************************************* paginar de tablas *************************** */
    protected static function paginador_tablas($pagina, $Npaginas, $url, $botones)
    {
        $clase_color = "btn-color-institucion"; // Clase CSS dinámica según la institución

        $tabla = '<nav aria-label="Page navigation example"><ul class="pagination justify-content-center">';

        // Botón « ir a la primera
        if ($pagina <= 1) {
            $tabla .= '<li class="page-item disabled"><span class="page-link"><i class="fas fa-angle-double-left"></i></span></li>';
        } else {
            $tabla .= '<li class="page-item"><a class="page-link btn-pagina ' . $clase_color . '" data-pagina="1"><i class="fas fa-angle-double-left"></i></a></li>';
        }

        // Botón Anterior
        if ($pagina > 1) {
            $tabla .= '<li class="page-item"><a class="page-link btn-pagina ' . $clase_color . '" data-pagina="' . ($pagina - 1) . '">Anterior</a></li>';
        }

        // Botones numerados
        $ci = 0;
        for ($i = $pagina; $i <= $Npaginas; $i++) {
            if ($ci >= $botones) break;

            $active = ($pagina == $i) ? 'active' : '';
            $tabla .= '<li class="page-item ' . $active . '">
            <a class="page-link btn-pagina ' . $clase_color . '" data-pagina="' . $i . '">' . $i . '</a>
        </li>';

            $ci++;
        }

        // Botón Siguiente
        if ($pagina < $Npaginas) {
            $tabla .= '<li class="page-item"><a class="page-link btn-pagina ' . $clase_color . '" data-pagina="' . ($pagina + 1) . '">Siguiente</a></li>';
        }

        // Botón » ir a la última
        if ($pagina >= $Npaginas) {
            $tabla .= '<li class="page-item disabled"><span class="page-link"><i class="fas fa-angle-double-right"></i></span></li>';
        } else {
            $tabla .= '<li class="page-item"><a class="page-link btn-pagina ' . $clase_color . '" data-pagina="' . $Npaginas . '"><i class="fas fa-angle-double-right"></i></a></li>';
        }

        $tabla .= '</ul></nav>';
        return $tabla;
    }

    public static function validar_institucion_y_sede_y_usuario($codigo_institucion, $id_sede, $documento_usuario = null)
    {
        // Verificar código de institución
        $check_codigo_institucion = self::ejecutar_consultas_simples("SELECT codigo_institucion FROM instituciones WHERE codigo_institucion = '$codigo_institucion'");
        if ($check_codigo_institucion->rowCount() <= 0) {
            self::jsonResponse("simple", "Código inválido", "El código de la institución ingresado no existe.", "error");
        }

        // Verificar ID de sede
        $check_id_sede = self::ejecutar_consultas_simples("SELECT id_sede FROM sedes WHERE id_sede = '$id_sede'");
        if ($check_id_sede->rowCount() <= 0) {
            self::jsonResponse("simple", "Código inválido", "El código de la sede ingresado no existe.", "error");
        }

        // Verificar documento del usuario si se pasa como parámetro
        if (!is_null($documento_usuario)) {
            $check_usuario = self::ejecutar_consultas_simples("SELECT documento FROM usuarios 
            WHERE documento = '$documento_usuario' 
            AND codigo_institucion = '$codigo_institucion' 
            AND id_sede = '$id_sede'");

            if ($check_usuario->rowCount() <= 0) {
                self::jsonResponse("simple", "Usuario inválido", "El número de documento ingresado no existe o no pertenece a esta institución/sede.", "error");
            }
        }

        return true;
    }

    public static function validar_campos_obligatorios(array $campos)
    {
        foreach ($campos as $nombre => $valor) {
            if (empty($valor) && $valor !== "0") {
                self::jsonResponse("simple", "Campos vacíos", "El campo \"$nombre\" no puede estar vacío.", "warning");
            }
        }

        return true;
    }

    public static function correo_valido($correo)
    {
        return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
    }


}

