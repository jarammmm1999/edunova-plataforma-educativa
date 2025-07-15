<?php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once './config/App.php';
require_once './controllers/InstitucionesController.php';
require_once './controllers/loginControlador.php';
require_once './controllers/UsuariosController.php';
require_once './controllers/profesoresController.php';
require_once './controllers/EstudiantesControlller.php';

$controller_institucion = new InstitucionesController();
$controller_login = new LoginControlador();
$controller_usuario = new UsuariosController();
$controller_profesores = new ProfesoresController();
$controller_estudiantes = new EstudiantesController();

$ruta = $_GET['views'] ?? '';
$segmentos = explode('/', $ruta);
$endpoint = $segmentos[0] ?? '';
$param1 = $segmentos[1] ?? null;
$param2 = $segmentos[2] ?? null;
$param3 = $segmentos[3] ?? null;
$param4 = $segmentos[4] ?? null;

function getJsonData() {
    return json_decode(file_get_contents("php://input"), true);
}

switch ($endpoint) {

    // Instituciones y sedes
    case 'instituciones':
        echo $controller_institucion->extraer_instituciones_controlador();
        break;
    case 'sedes':
        if ($param1) echo $controller_institucion->extraer_sedes_por_institucion_controlador($param1);
        break;

    // Login y sesión
    case 'login':
        if ($param1) echo $controller_login->extraer_informacion_sede_controlador($param1);
        break;
    case 'inicio_session':
        echo $controller_login->iniciar_sesion_controlador(getJsonData());
        break;

    case 'informacionUsuario':
        echo $param1 ? $controller_login->obtener_usuario_por_documento_controlador($param1)
                     : json_encode(['error' => 'Documento de usuario es requerido']);
        break;

    case 'imagenes-portada':
        echo $param1 ? $controller_login->obtener_imagenes_por_sede_controlador($param1)
                     : json_encode(['error' => 'ID de sede requerido']);
        break;

    // Usuarios
    case 'registrar_usuarios':
        echo $controller_usuario->Agregar_usuarios_controlador($_POST);
        break;
    case 'actualizar_usuarios':
        echo $controller_usuario->Actualizar_usuarios_controlador($_POST);
        break;
    case 'actualizar_grado':
        echo $controller_usuario->Actualizar_grados_usuarios_controlador($_POST);
        break;
    case 'subirImagenPerfil':
        echo $controller_usuario->Editar_imagens_usuarios_controlador($_POST);
        break;
    case 'actualizarPerfil':
        echo $controller_usuario->Editar_usuarios_controlador($_POST);
        break;

    // Documentos
    case 'cargar_documentos':
        echo $controller_usuario->Cargar_documento_usuarios_controlador($_POST);
        break;
    case 'ExtraerDocumentosUsuario':
        if ($param1) echo $controller_usuario->Consultar_documentos_usuarios_controlador($param1);
        break;
    case 'EliminarDocumentos':
        echo $controller_usuario->eliminar_documentos_estudiantes($_POST);
        break;

    // Acudientes
    case 'ExtraerAcudientesUsuario':
        if ($param1) echo $controller_usuario->extraer_acudientes_por_documento_controlador($param1);
        break;
    case 'EliminarAcudiente':
        echo $controller_usuario->eliminar_acudiente_controlador($_POST);
        break;
    case 'EditarAcudiente':
        echo $controller_usuario->editar_acudiente_controlador($_POST);
        break;
    case 'AgregarAcudiente':
        echo $controller_usuario->agregar_acudiente_controlador($_POST);
        break;

    // Información general de aplicación
    case 'aplicacion':
        switch ($param1) {
            case 'sexos':  echo $controller_institucion->extraer_sexos_aplicacion_controlador(); break;
            case 'estados': echo $controller_institucion->extraer_estados_aplicacion_controlador(); break;
            case 'roles': echo $controller_institucion->extraer_roles_aplicacion_controlador(); break;
            case 'grados': echo $controller_institucion->extraer_grado_sede_aplicacion_controlador($param2, $param3); break;
            case 'grupo': echo $controller_institucion->consultar_grupos_grados_controlador($param2); break;
            case 'ConsultarUsuariosSede': echo $controller_usuario->consultar_usuarios_sedes_controlador($param2, $param3, $param4); break;
            case 'DeleteUser': echo $controller_usuario->eliminar_usuarios_controlador($param2, $param3, $param4); break;
        }
        break;

    // Materias académicas
    case 'AgregarMateriasAcademicas':
        echo $controller_institucion->registrar_materias_academicas_controlador($_POST);
        break;
    case 'ConsultarMateriasAcademicas':
        if ($param1) echo $controller_institucion->extraer_materias_por_sede_controlador($param1, $param2);
        break;
    case 'EliminarMateriasAcademicas':
        echo $controller_institucion->elimina_materias_controlador($_POST);
        break;
    case 'EditarMateriasAcademicas':
        echo $controller_institucion->editar_materias_controlador($_POST);
        break;

    // Grados académicos
    case 'AgregarGradosAcademicos':
        echo $controller_institucion->registrar_grados_academicas_controlador($_POST);
        break;
    case 'ConsultarGradosAcademicos':
        if ($param1) echo $controller_institucion->extraer_grado_por_sede_controlador($param1, $param2);
        break;
    case 'EliminarGradosAcademicos':
        echo $controller_institucion->elimina_grado_controlador($_POST);
        break;
    case 'EditarGradosAcademicos':
        echo $controller_institucion->editar_grado_controlador($_POST);
        break;

    // Grupos
    case 'obtenerGruposPorInstitucionYSede':
        if ($param1) echo $controller_institucion->obtener_grupos_por_grado_controlador($param1);
        break;
    case 'RegistrarGrupos':
        echo $controller_institucion->registrar_grupo_controlador($_POST);
        break;
    case 'eliminarGrupo':
        echo $controller_institucion->eliminar_grupo_controlador($_POST);
        break;
    case 'EdiatrarGrupo':
        echo $controller_institucion->editar_grupo_controlador($_POST);
        break;

    // Materias por grado
    case 'registrarMateriasPorGrado':
        echo $controller_institucion->registrar_materias_por_grado_controlador($_POST);
        break;
    case 'extraerMateriasPorGrado':
        echo $controller_institucion->consultar_todas_materias_por_grado_controlador();
        break;
    case 'eliminarMateriaDeGrado':
        echo $controller_institucion->eliminar_materia_de_grado_controlador($_POST);
        break;
    case 'agregarNuevamateriaporGrados':
        echo $controller_institucion->agregar_nueva_materia_por_grado_controlador($_POST);
        break;
    case 'eliminarMateriasGrado':
        echo $controller_institucion->eliminar_materias_asignadas_grados_controlador($_POST);
        break;

    // Portadas
    case 'GuardarImagenesPortada':
        echo $controller_institucion->guardar_portada_controlador($_POST);
        break;
    case 'ConsultarImagenesPortada':
        if ($param1) echo $controller_institucion->consultar_portadas_controlador($param1, $param2);
        break;
    case 'eliminarImagenesPortadas':
        echo $controller_institucion->elimina_imagenes_portadas_controlador($_POST);
        break;
    case 'editarEstadoPortadas':
        echo $controller_institucion->activar_desactivar_portada_controlador($_POST);
        break;

    // Sedes
    case 'subirLogoSede':
        echo $controller_institucion->Editar_logo_sede_controlador($_POST);
        break;
    case 'actualizarInformacionSede':
        echo $controller_institucion->actualizar_sede_controlador($_POST);
        break;

    // Periodos académicos
    case 'RegistrarPeriodosAcademicos':
        echo $controller_institucion->registrar_periodos_academicos_sede_controlador($_POST);
        break;
    case 'ConsultarPeriodosAcademicos':
        if ($param1) echo $controller_institucion->extraer_periodos_academicos_controlador($param1, $param2);
        break;
    case 'actualizarPeriodosAcademicos':
        echo $controller_institucion->editar_periodo_academico_controlador($_POST);
        break;
    case 'eliminarPeriodoAcademicos':
        echo $controller_institucion->eliminar_periodo_academicos_controlador($_POST);
        break;

    // Profesores
    case 'extraerProfesoresAcademicos':
        if ($param1) echo $controller_profesores->extraer_profesores_controlador($param1, $param2);
        break;
    case 'guardarAsignacionesDocente':
        echo $controller_profesores->guardar_asignaciones_docente_controlador($_POST);
        break;
    case 'extraerMateriasAsigandasProfesores':
        if ($param1) echo $controller_profesores->consultar_asignaciones_docentes_controlador($param1, $param2);
        break;
    case 'eliminargradosAsignadosProfesores':
        echo $controller_profesores->eliminargradosAsignadosProfesores_controlador($_POST);
        break;
    case 'eliminargruposAsignadosProfesores':
        echo $controller_profesores->eliminargruposAsignadosProfesores_controlador($_POST);
        break;
    case 'eliminarMateriasAsignadosProfesores':
        echo $controller_profesores->eliminarMateriasAsignadosProfesores_controlador($_POST);
        break;
    case 'extraerMateriasAsigandasProfesorLogueado':
        if ($param1) echo $controller_profesores->consultar_asignaciones_docentes_logueado_controlador($param1, $param2, $param3);
        break;
    case 'extraerInformacionMateriaSelecionada':
        echo $controller_profesores->extraer_informacion_materia_seleccionada_controlador($_POST);
        break;
    // Temas educativos
    case 'CreartemasEducativos':
        echo $controller_profesores->crear_temas_educativos_controlador($_POST);
        break;
    case 'SubirImagenesMaterias':
        echo $controller_profesores->Editar_imagenenes_portadas_materias_controlador($_POST);
        break;
    case 'ConsultarTemasEducativos':
        echo $controller_profesores->obtener_temas_educativos_controlador($_POST);
        break;
    case 'ActualizarNombreTema':
        echo $controller_profesores->editar_nombre_temas_educativos($_POST);
        break;
    case 'ActualizarEstadoTema':
        echo $controller_profesores->editar_estado_temas_educativos($_POST);
        break;
    case 'actualizarOrdenTemas':
        echo $controller_profesores->actualizar_orden_temas_controlador($_POST);
    case 'actualizarContenidoTemas':
        echo $controller_profesores->actualizar_orden_contenido_temas_controlador($_POST);
    case 'consultar_contenido_tema_seleccionado':
        echo $controller_profesores->extraer_contenido_temas_controlador($_POST);
    case 'CreartextosEducativos':
    case 'CargarImagenesTemas':
    case 'CargarVideosTemas':
    case 'CrearTareasEducativas':
    case 'CrearArchivosEducativos':
        echo $controller_profesores->crear_contenido_tema_controlador($_POST);
        break;
    case 'ActualizartextosEducativos':
        echo $controller_profesores->actualizar_contenido_tema_controlador($_POST);
        break;
    case 'EliminartextosEducativos':
        echo $controller_profesores->eliminar_texto_educativos_controlador($_POST);
        break;
    case 'EliminarImagenesEducativas':
        echo $controller_profesores->eliminar_imagenes_educativos_controlador($_POST);
        break;
     case 'EliminarArchivosEducativas':
        echo $controller_profesores->eliminar_archivos_educativos_controlador($_POST);
        break;

    /*************************************tareas educativas*************************************** */
     case 'ConsultarInformacionTareasEducativas':
        echo $controller_profesores->extraer_contenido_tareas_controlador($_POST);
        break;
    case 'eliminar_archivos_tareas_registrados':
        echo $controller_profesores->eliminar_archivos_tareas_registrados_controlador($_POST);
        break;
    case 'EditarTareasEducativas':
        echo $controller_profesores->actualizar_informacion_tareas_controlador($_POST);
        break;

    case 'EliminarTareasEducativas':
        echo $controller_profesores->eliminar_tareas_educativas_controlador($_POST);
        break;

    /*************************************tareas educativas*************************************** */

    case 'ConsultarInformacionTalleresEducativas':
        echo $controller_profesores->extraer_contenido_talleres_controlador($_POST);
        break;
    case 'eliminar_archivos_taller_registrados':
        echo $controller_profesores->eliminar_archivos_taller_registrados_controlador($_POST);
        break;
    case 'EditarTallereEducativos':
        echo $controller_profesores->actualizar_informacion_taller_controlador($_POST);
        break;
    case 'EliminarTalleresEducativos':
        echo $controller_profesores->eliminar_taller_educativas_controlador($_POST);
        break;
      
    /************************************estudiantes****************************************** */
   case 'extraerMateriasAsigandasEstudianteLogueado':
        if ($param1) echo $controller_estudiantes->consultar_materias_estudiante_logueado_controlador($param1, $param2, $param3);
        break;

    case 'extraerInformacionMateriaSelecionadaEstudiantes':
        echo $controller_estudiantes->extraer_informacion_materia_seleccionada_controlador($_POST);
        break;
    
    case 'ConsultarTemasEducativosEstudiantes':
        echo $controller_estudiantes->obtener_temas_educativos_controlador($_POST);
        break;
    case 'ContestarForosUsuarios':
        echo $controller_estudiantes->crear_temas_discucion_foro_educativo_controlador($_POST);
        break;
    case 'ConsultarDiscucionesForos':
        echo $controller_estudiantes->obtener_discusiones_foro_educativo_controlador($_POST);
        break;
    case 'ResponderForosEstudiantes':
        echo $controller_estudiantes->registrar_comentario_foro_controlador($_POST);
        break;
    case 'EliminarDiscusion':
        echo $controller_estudiantes->eliminar_discusion_controlador($_POST);
        break;
    case 'EliminarComentario':
        echo $controller_estudiantes->eliminar_comentario_foro_controlador($_POST);
        break;
    case 'ActualizarDiscusion':
        echo $controller_estudiantes->editar_discusion_controlador($_POST);
        break;
    case 'ActualizarComentario':
        echo $controller_estudiantes->actualizar_comentario_foro_controlador($_POST);
        break;
    case 'ConsultarEnregasTareasEstudiantes':
        echo $controller_estudiantes->consultar_entrega_tarea_estudiante_controlador($_POST);
        break;
    case 'EnviarEntregaTareasEstudiante':
        echo $controller_estudiantes->registrar_entrega_tarea_estudiante_controlador($_POST);
        break;
    case 'EliminarArchivoEntregaEstudiantes':
        echo $controller_estudiantes->eliminar_entrega_tarea_estudiante_controlador($_POST);
        break;
    case 'EliminartextoEntregaEstudiantes':
        echo $controller_estudiantes->eliminar_texto_entrega_tarea_estudiante_controlador($_POST);
        break;
    


    // Default
    default:
        echo json_encode(['error' => 'Ruta no válida']);
        break;
}
