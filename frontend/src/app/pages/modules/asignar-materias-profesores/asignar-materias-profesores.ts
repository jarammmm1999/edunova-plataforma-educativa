import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SedesService } from '../../../services/sedes/sedes';
import { AlertasService } from '../../../services/alertas/alertas';
import { AplicationService } from '../../../services/aplication/aplication';
import { Profesores } from '../../../services/profesores/profesores';
import { ButtonSubmit } from '../../../shared/button-submit/button-submit';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-asignar-materias-profesores',
  standalone: true,
  imports: [CommonModule, FormsModule, ButtonSubmit],
  templateUrl: './asignar-materias-profesores.html',
  styleUrls: ['./asignar-materias-profesores.css'],
})
export class AsignarMateriasProfesores implements OnInit {
  sede: any = null;
  grados: any[] = [];
  materias: any[] = [];
  profesoresregistrados: any[] = [];
  materiasAsignadasProfesores: any[] = [];
  profesorSeleccionado: any = null;
  materiaSeleccionada: any = null;
  gradoSeleccionado: any = null;
  gruposModal: any[] = [];
  gruposPorGrado: any = {};
  activeProfesor: number | null = null;
  asignaciones: any[] = [];

  constructor(
    private alertaService: AlertasService,
    private sedeService: SedesService,
    private miServicio: AplicationService,
    private servicioProfesores: Profesores
  ) {}

  ngOnInit(): void {
    this.sedeService.sede$.subscribe({
      next: (sedeData) => {
        this.sede = sedeData;
        this.ConsultarMateriasAcademicas();
        this.ConsultarProfesoresSede();
        this.ConsultargradosAcademicos();
        this.ConsultarMateriasProfesoresAcademicas();
      },
    });
  }

  toggleProfesor(index: number) {
    if (this.activeProfesor === index) {
      this.activeProfesor = null; // cerrar si ya estaba abierto
    } else {
      this.activeProfesor = index; // abrir este y cerrar los demás
    }
  }

  ConsultarProfesoresSede() {
    this.servicioProfesores
      .ConsulrtarProfesores(
        this.sede?.id_sede_encriptado,
        this.sede?.codigo_institucion_encriptado
      )
      .subscribe({
        next: (response) => {
          this.profesoresregistrados = response;
        },
        error: (error) => {
          console.error('Error en backend:', error);
        },
      });
  }

  ConsultarMateriasAcademicas(): void {
    this.miServicio
      .ConsultarMateriasAcademicas(
        this.sede?.id_sede,
        this.sede?.codigo_institucion
      )
      .subscribe({
        next: (data) => {
          this.materias = data;
        },
        error: (error) => {
          console.error('Error en backend:', error);
        },
      });
  }

  ConsultargradosAcademicos(): void {
    this.miServicio
      .ConsultarGradosAcademicos(
        this.sede?.id_sede,
        this.sede?.codigo_institucion
      )
      .subscribe({
        next: (data) => {
          this.grados = data;
          this.grados.forEach((grado: any) => {
            this.obtenerGrupos(grado.id_grado);
          });
        },
        error: (error) => {
          console.error('Error en backend:', error);
        },
      });
  }

  obtenerGrupos(id_grado: string): void {
    this.miServicio.obtenerGruposPorGrado(id_grado).subscribe({
      next: (grupos) => {
        this.gruposPorGrado[id_grado] = grupos;
      },
      error: (error) => {
        console.error('Error en backend:', error);
      },
    });
  }

  seleccionarMateria(materia: any) {
    this.materiaSeleccionada = materia;
  }

  seleccionarGrado(grado: any) {
    this.gradoSeleccionado = grado;
    this.gruposModal = this.gruposPorGrado[grado.id_grado] || [];
  }

  asignarGrupoAGrilla(grupo: any) {
    const profesor = this.profesorSeleccionado;
    const materia = this.materiaSeleccionada;
    const grado = this.gradoSeleccionado;
    if (!profesor || !materia || !grado || !grupo) return;

    let prof = this.asignaciones.find(
      (p) => p.profesor.documento === profesor.documento
    );
    if (!prof) {
      prof = {
        profesor: profesor,
        materias: [],
      };
      this.asignaciones.push(prof);
    }

    let mat = prof.materias.find(
      (m: { id_materia: any }) => m.id_materia === materia.id_materia
    );
    if (!mat) {
      mat = {
        id_materia: materia.id_materia,
        nombre_materia: materia.nombre_materia,
        grados: [],
      };
      prof.materias.push(mat);
    }

    let grad = mat.grados.find(
      (g: { id_grado: any }) => g.id_grado === grado.id_grado
    );
    if (!grad) {
      grad = {
        id_grado: grado.id_grado,
        nombre_grado: grado.nombre_grado,
        grupos: [],
      };
      mat.grados.push(grad);
    }

    const yaExiste = grad.grupos.some(
      (g: { id_grupo: any }) => g.id_grupo === grupo.id_grupo
    );
    if (!yaExiste) {
      grad.grupos.push({
        id_grupo: grupo.id_grupo,
        nombre_grupo: grupo.nombre_grupo,
      });
    }
  }

  eliminarGrupo(
    profIndex: number,
    matIndex: number,
    gradIndex: number,
    grupoIndex: number
  ) {
    this.asignaciones[profIndex].materias[matIndex].grados[
      gradIndex
    ].grupos.splice(grupoIndex, 1);
  }

  eliminarMateria(profIndex: number, matIndex: number) {
    this.asignaciones[profIndex].materias.splice(matIndex, 1);
  }

  eliminarProfesor(profIndex: number) {
    this.asignaciones.splice(profIndex, 1);
  }
  guardarAsignacionesEnBackend() {
    const asignacionesFinal = this.asignaciones.map((asignacion) => {
      return {
        ...asignacion,
        codigo_institucion: this.sede.codigo_institucion,
        id_sede: this.sede.id_sede,
      };
    });

    this.servicioProfesores
      .guardarAsignacionesDocente(asignacionesFinal)
      .subscribe({
        next: (respuesta: any) => {
          this.alertaService.mostrarAlerta(respuesta);
            this.ConsultarMateriasProfesoresAcademicas();
            this.ConsultarMateriasProfesoresAcademicas();
        },
        error: (error) => {
          this.alertaService.mostrarAlerta({
            Alerta: 'simple',
            Titulo: 'Error de servidor',
            Texto: 'No se pudo procesar tu solicitud',
            Tipo: 'error',
          });
        },
      });
  }

  ConsultarMateriasProfesoresAcademicas(): void {
    this.servicioProfesores
      .extraerMateriasAsigandasProfesores(
        this.sede?.id_sede_encriptado,
        this.sede?.codigo_institucion_encriptado
      )
      .subscribe({
        next: (data) => {
          this.materiasAsignadasProfesores = data;
        },
        error: (error) => {
          console.error('Error en backend:', error);
        },
      });
  }

  eliminarGradoAsignadoProfesores(
    documento_docente: number,
    nombre_profesor: string,
    id_grado: number,
    nombre_grado: string
  ): void {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario); margin-bottom: -8px;">¿Estás seguro?</h1>',
      html: `¿Deseas eliminar el grado <strong>${nombre_grado}</strong> del profesor <strong>${nombre_profesor}</strong>?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#d33',
      cancelButtonColor: '#d33',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        const datos = {
          id_sede_encriptado: this.sede?.id_sede_encriptado,
          codigo_institucion_encriptado:
            this.sede?.codigo_institucion_encriptado,
          documento_docente: documento_docente,
          id_grado: id_grado,
        };
        this.servicioProfesores
          .eliminargradosAsignadosProfesores(datos)
          .subscribe({
            next: (respuesta) => {
              this.alertaService.mostrarAlerta(respuesta);
              this.ConsultarMateriasProfesoresAcademicas();
            },
            error: (error) => {
              console.error('❌ Error en backend al eliminar grado:', error);
              this.alertaService.mostrarAlerta({
                Alerta: 'simple',
                Titulo: 'Error de servidor',
                Texto: 'No se pudo eliminar el grado asignado',
                Tipo: 'error',
              });
            },
          });
      }
    });
  }

  eliminarGrupoAsignadoProfesores(
    documento_docente: number,
    nombre_profesor: string,
    id_grupo: number,
    nombre_grupo: string,
    nombre_grado: string,
  ): void {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario); margin-bottom: -8px;">¿Estás seguro?</h1>',
      html: `¿Deseas eliminar el grupo <strong>${nombre_grupo }</strong> del grado <strong>${nombre_grado}</strong> del profesor <strong>${nombre_profesor}</strong>?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#d33',
      cancelButtonColor: '#d33',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        const datos = {
          id_sede_encriptado: this.sede?.id_sede_encriptado,
          codigo_institucion_encriptado:
            this.sede?.codigo_institucion_encriptado,
          documento_docente: documento_docente,
          id_grupo: id_grupo,
        };
        this.servicioProfesores
          .eliminargruposAsignadosProfesores(datos)
          .subscribe({
            next: (respuesta) => {
              this.alertaService.mostrarAlerta(respuesta);
              this.ConsultarMateriasProfesoresAcademicas();
            },
            error: (error) => {
              console.error('❌ Error en backend al eliminar grado:', error);
              this.alertaService.mostrarAlerta({
                Alerta: 'simple',
                Titulo: 'Error de servidor',
                Texto: 'No se pudo eliminar el grado asignado',
                Tipo: 'error',
              });
            },
          });
      }
    });
  }

  eliminarMateriaAsignadoProfesores(
    documento_docente: number,
    nombre_profesor: string,
    nombre_materia: string,
    id_materia: number,

  ): void {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario); margin-bottom: -8px;">¿Estás seguro?</h1>',
      html: `¿Deseas eliminar la materia <strong>${nombre_materia }</strong> del profesor <strong>${nombre_profesor}</strong>?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#d33',
      cancelButtonColor: '#d33',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        const datos = {
          id_sede_encriptado: this.sede?.id_sede_encriptado,
          codigo_institucion_encriptado:
            this.sede?.codigo_institucion_encriptado,
          documento_docente: documento_docente,
          id_materia: id_materia,
        };
        this.servicioProfesores
          .eliminarMateriasAsignadosProfesores(datos)
          .subscribe({
            next: (respuesta) => {
              this.alertaService.mostrarAlerta(respuesta);
              this.ConsultarMateriasProfesoresAcademicas();
            },
            error: (error) => {
              console.error('❌ Error en backend al eliminar grado:', error);
              this.alertaService.mostrarAlerta({
                Alerta: 'simple',
                Titulo: 'Error de servidor',
                Texto: 'No se pudo eliminar el grado asignado',
                Tipo: 'error',
              });
            },
          });
      }
    });
  }


}
