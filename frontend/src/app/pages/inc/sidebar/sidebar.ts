import { Component, OnInit } from '@angular/core';
import { RouterModule,Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { SidebarService } from '../../../services/sidebar/sidebar';
import { UsuariosService } from '../../../services/usuarios/usuarios';
import { SedesService } from '../../../services/sedes/sedes';
import { Profesores } from '../../../services/profesores/profesores';
import { combineLatest } from 'rxjs';
import { Loader } from '../../../shared/loader/loader';
interface MenuItem {
  titulo: string;
  icono: string;
  ruta?: string;
  submenu?: MenuItem[];
}


@Component({
  selector: 'app-sidebar',
  imports: [RouterModule, CommonModule,Loader],
  templateUrl: './sidebar.html',
  styleUrl: './sidebar.css',
})
export class Sidebar implements OnInit {
  colapsado = false;
  expandido = false;
  mostrarCerrar = false;
  usuario: any = null;
  sede: any = null;
  menuDinamico: MenuItem[] = [];
  cargando: boolean = true;

  constructor(
    private sidebarService: SidebarService,
    private usuariosService: UsuariosService,
    private sedeService: SedesService,
    private servicioProfesores: Profesores,
    private router: Router
  ) {}

  activarSidebar() {
    this.colapsado = !this.colapsado;
    this.mostrarCerrar = window.innerWidth <= 768 && this.colapsado;
    document.body.style.overflow = this.colapsado ? 'hidden' : 'auto';
  }

  cerrarSidebar() {
    this.colapsado = false;
    this.mostrarCerrar = false;
    document.body.style.overflow = 'auto';
  }

  submenuAbierto: { [key: string]: boolean } = { usuarios: false };
  animando: { [key: string]: boolean } = { usuarios: false };
  mostrarSubmenu: { [key: string]: boolean } = { usuarios: false };

  toggleSubMenu(menu: string): void {
    if (this.submenuAbierto[menu]) {
      this.animando[menu] = true;
      setTimeout(() => {
        this.submenuAbierto[menu] = false;
        this.animando[menu] = false;
        this.mostrarSubmenu[menu] = false;
      }, 300); // 300ms = duración animación de salida
    } else {
      this.submenuAbierto[menu] = true;
      this.mostrarSubmenu[menu] = true;
    }
  }

  menuPorRol: { [rolId: number]: MenuItem[] } = {
    6: [
      {
        titulo: 'Home',
        icono: 'fas fa-home',
        ruta: '/home/inicio',
      },
      {
        titulo: 'Usuarios',
        icono: 'fas fa-user',
        submenu: [
          {
            titulo: 'Registrar usuarios',
            icono: 'fas fa-user-plus',
            ruta: '/home/registrar-usuarios',
          },
          {
            titulo: 'Consultar usuarios',
            icono: 'fas fa-users',
            ruta: '/home/consultar-usuarios',
          },
        ],
      },

      {
        titulo: 'Configuración del sistema',
        icono: 'fas fa-screwdriver-wrench',
        submenu: [
          {
            titulo: 'Inicio',
            icono: 'fas fa-home',
            ruta: '/home/configuracion-sistema',
          },
          {
            titulo: 'Materias Academicas',
            icono: 'fas fa-book',
            ruta: '/home/materias-academicas',
          },
          {
            titulo: 'Grados Academicos',
            icono: 'fas fa-book',
            ruta: '/home/grados-academicos',
          },
          {
            titulo: 'Portada Sedes',
            icono: 'fas fa-image',
            ruta: '/home/portada',
          },
          {
            titulo: 'periodos academicos',
            icono: 'fas fa-calendar-alt',
            ruta: '/home/periodos-academicos',
          },
          {
            titulo: '+ Materias profesores',
            icono: 'fas fa-plus-square',
            ruta: '/home/asignar-materias-profesores',
          },
          {
            titulo: 'informacion sede',
            icono: 'fas fa-school',
            ruta: '/home/informacion-sede',
          },
        ],
      },
    ],
  };

  menuFijoProfesor: MenuItem[] = [
    {
      titulo: 'Inicio',
      icono: 'fas fa-home',
      ruta: '/home/inicio',
    }
  ];

   menuFijoEstudiantes: MenuItem[] = [
    {
      titulo: 'Inicio',
      icono: 'fas fa-home',
      ruta: '/home/inicio',
    }
  ];

  ngOnInit() {
     this.cargando = true; // activa el loader inicialmente
    combineLatest([
      this.usuariosService.user$,
      this.sedeService.sede$,
    ]).subscribe(([usuario, sede]) => {
      this.usuario = usuario;
      this.sede = sede;

     if (!this.usuario || !this.sede) return;

     const rol = this.usuario?.id_rol;

     if (rol == 3) {
       this.extraerMateriasAsigandasProfesorLogueado();
     } else if (rol == 4) {
       this.extraerMateriasAsigandasEstudianteLogueado();
     } else {
       this.menuDinamico = this.menuPorRol[rol] || [];
       this.cargando = false;
     }

      const currentUrl = this.router.url;
      this.menuDinamico.forEach((item) => {
        if (item.submenu) {
          const subActivo = item.submenu.some(
            (sub) => !!sub.ruta && currentUrl.includes(sub.ruta)
          );
          if (subActivo) {
            this.submenuAbierto[item.titulo] = true;
            this.mostrarSubmenu[item.titulo] = true;
          }
        }
      });
    });

    this.sidebarService.colapsado$.subscribe((valor) => {
      this.colapsado = valor;
    });
  }

  //generar menu dinamico profesores
  generarMenuProfesor(asignaciones: any): MenuItem[] {
    const menuMap = new Map<string, MenuItem>();

    if (!asignaciones || !asignaciones.materias) return [];

    asignaciones.materias.forEach((materia: any) => {
      const idMateria = materia.id_materia;
      const nombreMateria = materia.nombre_materia;

      // Si ya existe en el mapa, usamos su submenu, si no, lo creamos
      if (!menuMap.has(nombreMateria)) {
        menuMap.set(nombreMateria, {
          titulo: nombreMateria,
          icono: 'fas fa-book',
          submenu: [],
        });
      }

      const submenuRef = menuMap.get(nombreMateria)!.submenu!;

      materia.grados.forEach((grado: any) => {
        const idGrado = grado.id_grado;
        const nombreGrado = grado.nombre_grado;

        grado.grupos.forEach((grupo: any) => {
          const idGrupo = grupo.id_grupo;
          const nombreGrupo = grupo.nombre_grupo;

          submenuRef.push({
            titulo: `${nombreGrado} ${nombreGrupo}`,
            icono: 'fas fa-arrow-right',
            ruta: `/home/materia/${idMateria}/${idGrado}/${idGrupo}`,
          });
        });
      });
    });

    // Convertimos el mapa a array
    return Array.from(menuMap.values());
  }
  //generar menu dinamico estudiantes
  generarMenuEstudiante(asignaciones: any): MenuItem[] {
  const menu: MenuItem[] = [];

  if (!asignaciones || !asignaciones.materias) return [];

  asignaciones.materias.forEach((materia: any) => {
    const idMateria = materia.id_materia;
    const nombreMateria = materia.nombre_materia;
    const idGrado = asignaciones.id_grado;
    const nombreGrado = asignaciones.nombre_grado;
    const idGrupo = asignaciones.id_grupo;
    const nombreGrupo = asignaciones.nombre_grupo;

    menu.push({
      titulo: `${nombreMateria}`,
      icono: 'fas fa-book',
      submenu: [
        {
          titulo: `${nombreGrado} ${nombreGrupo}`,
          icono: 'fas fa-arrow-right',
          ruta: `/home/materia/${idMateria}/${idGrado}/${idGrupo}`,
        }
      ]
    });
  });

  return menu;
}


  // extraer materias asignadas profesor logueado
  extraerMateriasAsigandasProfesorLogueado(): void {
    if (!this.sede || !this.usuario) return;
    this.servicioProfesores
      .extraerMateriasAsigandasProfesorLogueado(
        this.sede?.id_sede_encriptado,
        this.sede?.codigo_institucion_encriptado,
        this.usuario?.documento
      )
      .subscribe({
        next: (data) => {
          const menuMaterias = this.generarMenuProfesor(data);
          this.menuDinamico = [...this.menuFijoProfesor, ...menuMaterias];
          this.cargando = false; // ✅ ocultamos el loader al terminar la carga
        },
        error: (error) => {
          console.error('Error en backend:', error);
           this.cargando = false;
        },
      });
  }
  // extraer materias asignadas estudidnate logueado logueado
  extraerMateriasAsigandasEstudianteLogueado(): void {
    if (!this.sede || !this.usuario) return;
    this.servicioProfesores
      .extraerMateriasAsigandasEstudianteLogueado(
        this.sede?.id_sede_encriptado,
        this.sede?.codigo_institucion_encriptado,
        this.usuario?.documento
      )
      .subscribe({
        next: (data) => {
          console.log('datos extraidos: '+ data);
          const menuMaterias = this.generarMenuEstudiante(data);
          this.menuDinamico = [...this.menuFijoEstudiantes, ...menuMaterias];
          this.cargando = false; // ✅ ocultamos el loader al terminar la carga
        },
        error: (error) => {
          console.error('Error en backend:', error);
           this.cargando = false;
        },
      });
  }
}
