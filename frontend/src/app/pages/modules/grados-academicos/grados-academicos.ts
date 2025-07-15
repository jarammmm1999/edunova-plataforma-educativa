import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SedesService } from '../../../services/sedes/sedes';
import { AlertasService } from '../../../services/alertas/alertas';
import { AplicationService } from '../../../services/aplication/aplication';
import { InputsWidget } from '../../../shared/inputs/inputs';
import { ButtonSubmit } from '../../../shared/button-submit/button-submit';

import Swal from 'sweetalert2';
import { ErrorMessage } from '../../../shared/error-message/error-message';
declare var bootstrap: any;

interface AsignacionCurso {
  grado: any;
  materiasAsignadas: any[];
}

@Component({
  selector: 'app-grados-academicos',
  imports: [CommonModule, FormsModule, InputsWidget, ButtonSubmit,ErrorMessage],
  templateUrl: './grados-academicos.html',
  styleUrl: './grados-academicos.css',
})
export class GradosAcademicos implements OnInit {
  sede: any = null;
  grados: any[] = [];
  materias: any[] = [];
  busquedaGrados: string = '';
  gruposPorGrado: { [id_grado: string]: any[] } = {};
  gradoSeleccionado: any = null;
  gruposModal: any[] = [];
  asignaciones: AsignacionCurso[] = [];
  grado: string = '';
  materiasPorGrado: any[] = [];
  cargando = true;
  error = true;
  activeIndex: number | null = null;
  constructor(
    private alertaService: AlertasService,
    private sedeService: SedesService,
    private miServicio: AplicationService
  ) {}


toggleAcordeon(index: number) {
  if (this.activeIndex === index) {
    this.activeIndex = null; // cerrar si ya estaba abierto
  } else {
    this.activeIndex = index; // abrir este y cerrar los demás
  }
}

  registrargrado(): void {
    const datosgrados = {
      grado: this.grado,
      codigo_institucion: this.sede.codigo_institucion,
      id_sede: this.sede.id_sede,
    };

    this.miServicio.RegistrarGradosAcademicos(datosgrados).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.ConsultargradosAcademicos(); // recargar después de registrar
        this.grado = ''; // limpiar campo
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

  ConsultargradosAcademicos(): void {
    this.miServicio
      .ConsultarGradosAcademicos(
        this.sede?.id_sede,
        this.sede?.codigo_institucion
      )
      .subscribe({
        next: (data) => {
          this.grados = data;
          // Llamar a obtenerGrupos para cada grado recibido
          this.grados.forEach((grado: any) => {
            this.obtenerGrupos(grado.id_grado);
          });
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

  getMateriasFaltantes(grupo: any): any[] {
    const idsAsignadas = grupo.materias.map((m: any) => m.id_materia);
    return this.materias.filter(
      (m: any) => !idsAsignadas.includes(m.id_materia)
    );
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
          this.alertaService.mostrarAlerta({
            Alerta: 'simple',
            Titulo: 'Error de servidor',
            Texto: 'No se pudo procesar tu solicitud',
            Tipo: 'error',
          });
        },
      });
  }

  getgradosFiltradas(): any[] {
    const texto = this.busquedaGrados
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
    return this.grados.filter((m) =>
      m.nombre_grado
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .includes(texto)
    );
  }

  eliminargrado(data: any): void {
    const datosgrados = {
      id_grado: data.id_grado,
      codigo_institucion: data.codigo_institucion,
      id_sede: data.id_sede,
    };

    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text: '¿Estás seguro de eliminar el grado: ' + data.nombre_grado + ' ?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar!',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        this.miServicio.EliminarGradosAcademicos(datosgrados).subscribe({
          next: (respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.ConsultargradosAcademicos();
            this.busquedaGrados = '';
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
    });
  }
  actualizargrado(data: any): void {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Deseas actualizar este grado? </h1>',
      input: 'text',
      icon: 'question',
      inputValue: data.nombre_grado,
      inputAttributes: {
        autocapitalize: 'off',
      },
      showCancelButton: true,
      confirmButtonText: '!Si, actualizar!',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
      showLoaderOnConfirm: true,
      preConfirm: (nuevoNombre) => {
        if (!nuevoNombre.trim()) {
          Swal.showValidationMessage('El nombre no puede estar vacío');
          return false;
        }

        const datosgrados = {
          id_grado: data.id_grado,
          codigo_institucion: data.codigo_institucion,
          id_sede: data.id_sede,
          nombre_grado: nuevoNombre.trim(),
        };

        return this.miServicio
          .EditarGradosAcademicos(datosgrados)
          .toPromise()
          .then((respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.ConsultargradosAcademicos();
          })
          .catch(() => {
            Swal.showValidationMessage('Error al actualizar la grado');
          });
      },
      allowOutsideClick: () => !Swal.isLoading(),
    });
  }

  obtenerGrupos(id_grado: string): void {
    this.miServicio.obtenerGruposPorGrado(id_grado).subscribe({
      next: (grupos) => {
        this.gruposPorGrado[id_grado] = grupos;

        // ⚠️ Si el modal está abierto y corresponde al grado actual, actualizar visualmente
        if (this.gradoSeleccionado?.id_grado === id_grado) {
          this.gruposModal = [...grupos]; // fuerza cambio de referencia
        }
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error',
          Texto: 'No se pudieron cargar los grupos',
          Tipo: 'error',
        });
      },
    });
  }

  getGruposPorGrado(id: string) {
    return this.gruposPorGrado[id] || [];
  }

  abrirModalGrupos(grado: any): void {
    this.gradoSeleccionado = grado;
    this.gruposModal = JSON.parse(
      JSON.stringify(this.gruposPorGrado[grado.id_grado] || [])
    );
    const modal = new bootstrap.Modal(document.getElementById('modalGrupos')!);
    modal.show();
  }

  eliminarGrupo(grupo: any): void {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text: '¿Estás seguro de eliminar el grupo: ' + grupo.nombre_grupo + ' ?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar!',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        this.miServicio.eliminarGrupo(grupo).subscribe({
          next: (respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.ConsultargradosAcademicos();
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
    });
  }

  actualizargrupo(data: any): void {
    const modalGrupos = document.getElementById('modalGrupos');

    // Quitar modal del flujo visual completamente
    if (modalGrupos) {
      modalGrupos.style.display = 'none';
      document.querySelector('.modal-backdrop')?.classList.add('d-none');
      // Espera a que termine la animación antes de ocultar
      setTimeout(() => {
        modalGrupos.style.display = 'none';
        document.querySelector('.modal-backdrop')?.classList.add('d-none');
      }, 300); // duración de la animación
    }

    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario); margin-bottom: -12px;">¿Deseas actualizar el nombre del grupo?</h1>',
      icon: 'question',
      html: `
        <input id="swal-nombre" class="swal2-input"  style="width: 82%;placeholder="Nombre del grupo" value="${data.nombre_grupo}">
        <input id="swal-cantidad" class="swal2-input" style="width: 82%; placeholder="Cantidad de estudiantes" type="number" min="1" value="${data.cantidad}">
      `,
      showCancelButton: true,
      confirmButtonText: '!Si, actualizar!',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
      showClass: {
        popup: 'animate__animated animate__fadeInDown',
      },
      hideClass: {
        popup: 'animate__animated animate__fadeOutUp',
      },

      showLoaderOnConfirm: true,
      preConfirm: () => {
        const nombre = (
          document.getElementById('swal-nombre') as HTMLInputElement
        ).value.trim();
        const cantidad = Number(
          (document.getElementById('swal-cantidad') as HTMLInputElement).value
        );

        if (!nombre || isNaN(cantidad) || cantidad <= 0) {
          Swal.showValidationMessage(
            'Todos los campos son obligatorios y válidos'
          );
          return false;
        }

        const datosGrupo = {
          id_grupo: data.id_grupo,
          nombre_grupo: nombre,
          cantidad: cantidad,
        };

        return this.miServicio
          .EditarGrupo(datosGrupo)
          .toPromise()
          .then((respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.ConsultargradosAcademicos();
          })
          .catch(() => {
            Swal.showValidationMessage('Error al actualizar el grupo');
          });
      },
      allowOutsideClick: () => !Swal.isLoading(),
    }).then(() => {
      // Restaurar modal al final
      if (modalGrupos) {
        modalGrupos.style.display = 'block';
        document.querySelector('.modal-backdrop')?.classList.remove('d-none');
      }
    });
  }

  agregarNuevoGrupo(): void {
    this.gruposModal.push({
      nombre_grupo: '',
      cantidad: 25,
      id_grado: this.gradoSeleccionado.id_grado,
    });
  }

  guardarGrupos(): void {
    const nuevosGrupos = this.gruposModal.filter((grupo) => !grupo.id_grupo);

    const gruposValidos = nuevosGrupos.filter(
      (grupo) => grupo.nombre_grupo.trim() !== '' && grupo.cantidad > 0
    );

    if (gruposValidos.length === 0) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Nada para guardar',
        Texto: 'No se han agregado nuevos grupos válidos.',
        Tipo: 'info',
      });
      return;
    }
    this.miServicio.registrarGrupos(gruposValidos).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.ConsultargradosAcademicos();
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error',
          Texto: 'No se pudo guardar los grupos.',
          Tipo: 'error',
        });
      },
    });
  }

  agregarBloqueAsignacion() {
    this.asignaciones.push({ grado: null, materiasAsignadas: [] });
  }

  eliminarAsignacion(index: number): void {
  this.asignaciones.splice(index, 1);
}

  esMateriaSeleccionada(
    asignacion: AsignacionCurso,
    id_materia: number
  ): boolean {
    return asignacion.materiasAsignadas.some(
      (m) => m.id_materia === id_materia
    );
  }

  toggleMateria(asignacion: AsignacionCurso, materia: any, event: any): void {
    const seleccionadas = asignacion.materiasAsignadas;
    const yaExiste = seleccionadas.some(
      (m) => m.id_materia === materia.id_materia
    );

    if (event.target.checked && !yaExiste) {
      seleccionadas.push(materia);
    } else if (!event.target.checked && yaExiste) {
      asignacion.materiasAsignadas = seleccionadas.filter(
        (m) => m.id_materia !== materia.id_materia
      );
    }
  }

  quitarMateria(asignacion: AsignacionCurso, materia: any): void {
    asignacion.materiasAsignadas = asignacion.materiasAsignadas.filter(
      (m) => m.id_materia !== materia.id_materia
    );
  }

  guardarTodo(): void {
    const datos = this.asignaciones.map((asignacion) => ({
      id_grado: asignacion.grado.id_grado,
      materias_json: asignacion.materiasAsignadas.map((m) => m.id_materia),
      id_sede: this.sede.id_sede,
      codigo_institucion: this.sede.codigo_institucion,
    }));

    if (datos.length === 0) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Datos incompletos',
        Texto:
          'Debes seleccionar al menos un grado y asignarle materias antes de guardar.',
        Tipo: 'warning',
      });
      return;
    }

    this.miServicio.registrarMateriasPorGrado(datos).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.obtenerMateriasPorGrado();
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

  obtenerMateriasPorGrado() {
    this.miServicio.obtenerMateriasPorGrado().subscribe({
      next: (respuesta) => {
        this.materiasPorGrado = respuesta;
        this.cargando = false;
      },
      error: (err) => {
        this.error = false;
      },
    });
  }

  eliminarMateria(
    idRegistro: number,
    idMateria: string,
    nombreMateria: string,
    nombreGrupo: string
  ) {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text:
        '¿Estás seguro de eliminar la materia: ' +
        nombreMateria +
        ' del grupo: ' +
        nombreGrupo +
        '  ?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar!',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        // Aquí va tu servicio real de eliminación
        this.miServicio
          .eliminarMateriaDeGrado(idRegistro, idMateria)
          .subscribe({
            next: (respuesta: any) => {
              this.alertaService.mostrarAlerta(respuesta);
              this.obtenerMateriasPorGrado();
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
    });
  }

  agregarMateria(id_grado: number, materia: string) {
    const nuevaMateria = {
      id_materia: materia,
      id_grado: id_grado,
    };

    this.miServicio.agregarNuevamateriaporGrados(nuevaMateria).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.obtenerMateriasPorGrado();
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

  eliminarMateriaGrado(nombreGrupo: string, id_grado: string) {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text:
        '¿Estás seguro de eliminar las materia: ' +
        ' del grado : ' +
        nombreGrupo +
        '  ?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar!',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
       
        this.miServicio.eliminarMateriasGrado(id_grado).subscribe({
          next: (respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.obtenerMateriasPorGrado();
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
    });
  }

  ngOnInit(): void {
    this.sedeService.sede$.subscribe({
      next: (sedeData) => {
        this.sede = sedeData;
        this.ConsultargradosAcademicos();
        this.ConsultarMateriasAcademicas();
        this.obtenerMateriasPorGrado();
      },
    });
  }
}
