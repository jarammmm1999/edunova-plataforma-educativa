import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SedesService } from '../../../services/sedes/sedes';
import { AlertasService } from '../../../services/alertas/alertas';
import { AplicationService } from '../../../services/aplication/aplication';
import { InputsWidget } from '../../../shared/inputs/inputs';
import { ButtonSubmit } from '../../../shared/button-submit/button-submit';
import Swal from 'sweetalert2';


@Component({
  selector: 'app-periodos-academicos',
  imports: [
    CommonModule,
    InputsWidget,
    FormsModule,
    ButtonSubmit
  ],
  templateUrl: './periodos-academicos.html',
  styleUrl: './periodos-academicos.css',
})
export class PeriodosAcademicos implements OnInit {
  sede: any = null;
  periodosAcademicos: any[] = [];
  periodo = {
    nombre_periodo: '',
    fecha_inicio: '',
    fecha_fin: '',
    codigo_institucion: 0,
    id_sede: 0,
  };
  constructor(
    private alertaService: AlertasService,
    private sedeService: SedesService,
    private miServicio: AplicationService
  ) {}

  ngOnInit(): void {
    this.sedeService.sede$.subscribe({
      next: (sedeData) => {
        this.sede = sedeData;
        this.ConsultarPeriodosAcademicos();
      },
    });
  }

  //registrar periodos academicos

  registrarPeriodo(): void {
    if (
      this.periodo.nombre_periodo.trim() === '' ||
      !this.periodo.fecha_fin ||
      !this.periodo.fecha_inicio
    ) {
      return this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Campos incompletos',
        Texto: 'por favor llene todos los campos',
        Tipo: 'error',
      });
    }

    this.periodo.codigo_institucion = this.sede?.codigo_institucion;
    this.periodo.id_sede = this.sede?.id_sede;
    this.miServicio.RegistrarPeriodosAcademicos(this.periodo).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.ConsultarPeriodosAcademicos();
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

  //consultar periodos academicos
  ConsultarPeriodosAcademicos(): void {
    const datos = {
      codigo_institucion: this.sede?.codigo_institucion,
      id_sede: this.sede?.id_sede,
    };

    this.miServicio.ConsultarPeriodosAcademicos(datos).subscribe({
      next: (respuesta: any) => {
        this.periodosAcademicos = respuesta;
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

   eliminarPeriodoAcademico(data: any): void {
    const datos = {
      id_periodo: data.id_periodo,
      codigo_institucion: data.codigo_institucion,
      id_sede: data.id_sede,
    };

    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text:
        '¿Estás seguro de eliminar el periodo academico: ' +
        data.nombre_periodo +
        ' ?',
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
        this.miServicio.eliminarPeriodoAcademico(datos).subscribe({
          next: (respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.ConsultarPeriodosAcademicos();
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

   editarPeriodoAcademico(data: any): void {
    const datos = {
      id_periodo: data.id_periodo,
      nombre_periodo: data.nombre_periodo,
      fecha_inicio: data.fecha_inicio,
      fecha_fin: data.fecha_fin,
    };

    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text: '¿Estás seguro de actualizar este periodo?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, actualizar!',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        this.miServicio.actualizarperiodosacademicos(datos).subscribe({
          next: (respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.ConsultarPeriodosAcademicos();
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

}
