import { Component, OnInit, ViewEncapsulation } from '@angular/core';
import { Router, RouterModule, NavigationEnd } from '@angular/router';
import { CommonModule } from '@angular/common';
import { TextosComponent } from '../../shared/textos/textos';
import { UsuariosService } from '../../services/usuarios/usuarios';
import { SidebarService } from '../../services/sidebar/sidebar';
import { Profesores } from '../../services/profesores/profesores';
import { SedesService } from '../../services/sedes/sedes';
import { combineLatest, filter as rxFilter } from 'rxjs';
import { Estudiantes } from '../../services/estudiantes/estudiantes';

@Component({
  selector: 'app-home',
  imports: [CommonModule, RouterModule, TextosComponent],
  standalone: true,
  encapsulation: ViewEncapsulation.None,
  templateUrl: './home.html',
  styleUrl: './home.css',
})
export class Home implements OnInit {
  usuario: any = null;
  sede: any = null;
  ruta: string = '';
  mostrarTextPage: boolean = true;
  private consultaEjecutada = false;

  nombreMateria: string = '';
  nombreGrado: string = '';
  nombreGrupo: string = '';

  constructor(
    private router: Router,
    private sidebarService: SidebarService,
    private usuariosService: UsuariosService,
    private servicioProfesores: Profesores,
    private servicioEstudiantes: Estudiantes,
    private sedeService: SedesService
  ) {}

  titulosPorRuta: { [clave: string]: string } = {
    inicio: 'Bienvenido',
    perfil: 'Mi Perfil',
    'consultar-usuarios': 'Consulta de usario',
    'registrar-usuarios': 'Registro de Usuarios',
    'configuracion-sistema': 'Configuración del Sistema',
    'materias-academicas': 'Materias Académicas',
    'grados-academicos': 'Grados Académicos',
    portada: 'Portada de institución',
    'periodos-academicos': 'Periodos Académicos',
    'asignar-materias-profesores': 'Asignar materias a profesores',
    'perfil-usuario': 'Perfil de usuario',
    'informacion-sede': 'Información de la sede',
  };

  iconosPorRuta: { [clave: string]: string } = {
    inicio: 'fas fa-home',
    perfil: 'fas fa-user',
    'consultar-usuarios': 'fas fa-magnifying-glass',
    'registrar-usuarios': 'fas fa-users',
    'configuracion-sistema': 'fas fa-cog',
    'materias-academicas': 'fas fa-book',
    'grados-academicos': 'fas fa-book',
    portada: 'fa fa-image',
    'periodos-academicos': 'fa fa-calendar',
    'asignar-materias-profesores': 'fas fa-plus-square',
    'perfil-usuario': 'fa fa-user-circle',
    'informacion-sede': 'fa fa-school',
     materia: 'fa fa-book',
  };

  titulo: string = 'Bienvenido';
  icono: string = 'fas fa-home';

  ngOnInit(): void {
    // Combinar usuario y sede
    combineLatest([this.usuariosService.user$, this.sedeService.sede$])
      .pipe(rxFilter(([usuario, sede]) => !!usuario && !!sede))
      .subscribe(([usuario, sede]) => {
        this.usuario = usuario;
        this.sede = sede;

        // Verifica si los datos de URL están listos para consultar
        this.detectarRutaYConsultar();
      });

    // Detectar cambio de navegación para actualizar título y ruta
    this.router.events
      .pipe(rxFilter((event) => event instanceof NavigationEnd))
      .subscribe(() => {
        // Detectar nueva ruta y volver a consultar si cambia
        this.consultaEjecutada = false;
        this.detectarRutaYConsultar();
      });
  }

  detectarRutaYConsultar() {
    const url = this.router.url;
    const segmento = url.split('/')[2] || 'inicio';
    const idMateria = url.split('/')[3] || '';
    const idGrado = url.split('/')[4] || '';
    const idGrupo = url.split('/')[5] || '';


    this.ruta = segmento;
    this.icono = this.iconosPorRuta[this.ruta];
    this.titulo = this.titulosPorRuta[this.ruta] || this.formatTitle(this.ruta);

    // Redibujar componente dinámico (opcional)
    this.mostrarTextPage = false;
    setTimeout(() => (this.mostrarTextPage = true), 0);

    // Ejecutar solo si todo está disponible
    if (
      this.usuario &&
      this.sede &&
      idMateria &&
      idGrado &&
      idGrupo &&
      !this.consultaEjecutada
    ) {
      this.consultaEjecutada = true;
      if(this.usuario.id_rol == 3){
         this.consultarInfromacionMaterias(idMateria, idGrado, idGrupo);
      }else if(this.usuario.id_rol == 4){
        this.extraerInformacionMateriaSelecionadaEstudiantes(idMateria, idGrado, idGrupo);
      }
  
    }
  }

  consultarInfromacionMaterias(
    idMateria: string,
    idGrado: string,
    idGrupo: string
  ) {
    const datos = {
      id_materia: idMateria,
      id_grado: idGrado,
      id_grupo: idGrupo,
      documento_profesor: this.usuario?.documento,
      codigo_institucion: this.sede?.codigo_institucion_encriptado,
      id_sede: this.sede?.id_sede_encriptado,
    };

    this.servicioProfesores
      .extraerInformacionMateriaSelecionada(datos)
      .subscribe({
        next: (data: any) => {
          this.nombreMateria = data.nombre_materia || '';
          this.nombreGrado = data.nombre_grado || '';
          this.nombreGrupo = data.nombre_grupo || '';

          // Opcional: también podrías actualizar el título aquí
          if (this.ruta === 'materia') {
            this.titulo = `${this.nombreMateria} - ${this.nombreGrado} ${this.nombreGrupo}`;
          }
        },
        error: (error) => {
          console.error('[Error Materia]:', error);
        },
      });
  }

   extraerInformacionMateriaSelecionadaEstudiantes(
    idMateria: string,
    idGrado: string,
    idGrupo: string
  ) {
    const datos = {
      id_materia: idMateria,
      id_grado: idGrado,
      id_grupo: idGrupo,
      documento_estudiantes: this.usuario?.documento,
      codigo_institucion: this.sede?.codigo_institucion_encriptado,
      id_sede: this.sede?.id_sede_encriptado,
    };
    
    this.servicioEstudiantes
      .extraerInformacionMateriaSelecionadaEstudiantes(datos)
      .subscribe({
        next: (data: any) => {
          this.nombreMateria = data.nombre_materia || '';
          this.nombreGrado = data.nombre_grado || '';
          this.nombreGrupo = data.nombre_grupo || '';
          // Opcional: también podrías actualizar el título aquí
          this.titulo = `${this.nombreMateria} - ${this.nombreGrado} ${this.nombreGrupo}`;
        },
        error: (error) => {
          console.error('[Error Materia]:', error);
        },
      });
  }

  activarSidebar() {
    this.sidebarService.toggleSidebar();
  }

  formatTitle(ruta: string): string {
    // Fallback por si no está definida la ruta en el diccionario
    return ruta.charAt(0).toUpperCase() + ruta.slice(1).replace(/-/g, ' ');
  }
}
