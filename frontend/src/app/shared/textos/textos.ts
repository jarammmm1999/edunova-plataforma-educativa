import { Component, Input, OnInit, output } from '@angular/core';


@Component({
  selector: 'app-textos',
  imports: [],
  templateUrl: './textos.html',
  styleUrl: './textos.css',
})
export class TextosComponent implements OnInit {
  @Input() nombreUsuario!: string;
  @Input() rol!: string;
  @Input() title!: string;
  @Input() vista!: string;
  @Input() icono: string = 'fas fa-home'; // Ícono por defecto

  descripcion: string = '';

  ngOnInit(): void {
    this.descripcion = this.generarMensaje(this.vista, this.rol, this.title);
  }

  generarMensaje(vista: string, rol: string, title: string): string {
    if (rol == '6') {
      if (vista && vista !== '' && vista == 'inicio') {
        return 'Este módulo ha sido diseñado para facilitar tu labor como Secretario Académico. Desde esta plataforma podrás gestionar de forma centralizada a los usuarios administrativos y académicos, asignar docentes y estudiantes a las diferentes materias academicas de la institución, coordinar horarios institucionales, controlar el acceso al sistema, generar reportes administrativos detallados.';
      } else if (vista && vista !== '' && vista == 'registrar-usuarios') {
        return 'Este módulo te permite realizar la matrícula y registro formal de nuevos usuarios dentro del sistema institucional. Desde aquí podrás ingresar los datos básicos de estudiantes, docentes y personal administrativo, asignar roles según su función, vincularlos a sedes e instituciones correspondientes y activar o desactivar sus accesos al sistema.';
      } else if (vista && vista !== '' && vista == 'consultar-usuarios') {
        return 'Este módulo te permite consultar, filtrar y visualizar la información detallada de los        usuarios registrados en el sistema institucional. Desde aquí podrás acceder a los datos de estudiantes, docentes y personal administrativo, verificar su estado activo o inactivo, y realizar búsquedas por nombre, documento, rol, grados o grupos para facilitar la gestión y seguimiento de la comunidad educativa.';
      } else if (vista && vista !== '' && vista == 'configuracion-sistema') {
        return 'Desde esta sección puedes registrar, actualizar y administrar los cursos y grupos académicos de la institución educativa. Desde aqui  podrás crear nuevos cursos, asignarles grupos, modificar su información, eliminar registros obsoletos y mantener organizada la estructura académica de cada sede. Una gestión adecuada de cursos y grupos facilita la planificación escolar y el seguimiento de los estudiantes.';
      } else if (vista && vista !== '' && vista == 'materias-academicas') {
        return 'Este módulo te permite gestionar las materias académicas de la institución educativa. Desde aquí podrás crear, editar y eliminar materias, asignarlas a grupos y gestionar la asignación de docentes a cada materia. Además, puedes asignar grupos a las materias y gestionar la asignación de alumnos a grupos para facilitar la planificación escolar.';
      } else if (vista && vista !== '' && vista == 'grados-academicos') {
        return 'Este módulo te permite gestionar los grados académicos de la institución educativa. Desde aquí podrás crear, editar y eliminar grados académicos, asignarlos a cursos y gestionar la asignación de alumnos a cada grado. Además, puedes asignar grupos a los grados y gestionar la asignación de alumnos a grupos para facilitar la planificación escolar.';
      } else if (vista && vista !== '' && vista == 'portada') {
        return 'En esta sección podrás gestionar las portadas de la institución educativa que se mostrarán en el inicio de sesión. Tienes la opción de subir una o varias imágenes de portada de forma simultánea, activarlas para que se visualicen en la página principal de la institución y eliminarlas cuando lo desees. Esta funcionalidad te permite personalizar la presentación de tu sede de manera fácil y dinámica.';
      } else if (vista && vista !== '' && vista == 'periodos-academicos') {
        return ' Este módulo te permite gestionar los periodos académicos de la institución educativa. Desde aquí podrás crear, editar y eliminar periodos académicos, asignarlos a cursos y gestionar la asignación de alumnos a cada periodo. Además, puedes asignar grupos a los periodos y gestionar la asignación de alumnos a grupos para facilitar la planificación escolar.';
      } else if (
        vista &&
        vista !== '' &&
        vista == 'asignar-materias-profesores'
      ) {
        return 'Este módulo te permite asignar materias a docentes. Desde aquí podrás seleccionar los docentes y las materias que quieras asignar, asignar grupos a las materias y gestionar la asignación de alumnos a grupos para facilitar la planificación escolar.';
      } else if (vista && vista !== '' && vista == 'perfil-usuario') {
        return 'Este módulo te permite gestionar tu perfil personal en el sistema. Desde aquí podrás actualizar tus datos personales, cambiar tu contraseña y acceder a la configuración de tu cuenta.';
      } else if (vista && vista !== '' && vista == 'informacion-sede') {
        return 'Este módulo permite administrar de forma integral la información de la sede. Desde esta sección, podrás actualizar datos clave como la dirección, el número de contacto, los colores institucionales y el logotipo..';
      }

      /**************************profesores**************************************** */
    } else if (rol == '3') {
      if (vista && vista !== '' && vista == 'inicio') {
        return 'Este módulo ha sido diseñado para facilitar tu labor como docente. Desde esta plataforma podrás gestionar de forma centralizada a los usuarios administrativos, académicos, estudiantes y personal administrativo, asignar docentes y estudiantes a las diferentes materias academicas de la institución, coordinar horarios institucionales, controlar el acceso al sistema, generar reportes administrativos detallados.';
      } else if (vista && vista !== '' && vista == 'materia') {
        return `Este módulo ha sido diseñado para facilitar tu labor como docente. En este espacio,  podrás gestionar integralmente todos los aspectos relacionados con la asignatura, incluyendo planificación, contenidos, evaluaciones y seguimiento académico.`;

      }
    } else if (rol == '4') {
      if (vista && vista !== '' && vista == 'inicio') {
        return 'Bienvenido al panel estudiantil. Este módulo ha sido creado para brindarte acceso directo a tus asignaturas, actividades académicas, calificaciones y recursos educativos. Desde aquí podrás estar al tanto de tu progreso, organizar tus tareas y participar activamente en tu proceso formativo.';
      } else if (vista && vista !== '' && vista == 'materia') {
        return 'Este espacio está diseñado para que explores y gestiones los contenidos de cada asignatura. Aquí podrás acceder a materiales de estudio, actividades, evaluaciones y observaciones académicas relacionadas con tu proceso de aprendizaje.';
      }

    }

    return `Bienvenido al sistema.`;
  }
}
