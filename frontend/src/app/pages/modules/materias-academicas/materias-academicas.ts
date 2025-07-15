import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SedesService } from '../../../services/sedes/sedes';
import { AlertasService } from '../../../services/alertas/alertas';
import { AplicationService } from '../../../services/aplication/aplication';
import { Buscador } from '../../../shared/buscador/buscador';
import { InputsWidget } from '../../../shared/inputs/inputs';
import { ButtonSubmit } from '../../../shared/button-submit/button-submit';

import Swal from 'sweetalert2';
import { ErrorMessage } from '../../../shared/error-message/error-message';

@Component({
  selector: 'app-materias-academicas',
  imports: [Buscador, CommonModule, InputsWidget, FormsModule,ButtonSubmit,ErrorMessage],
  templateUrl: './materias-academicas.html',
  styleUrl: './materias-academicas.css',
})
export class MateriasAcademicas implements OnInit {
  sede: any = null;
  materias: any[] = [];
  materia: string = '';
  busquedaMateria: string = '';
  constructor(
    private alertaService: AlertasService,
    private sedeService: SedesService,
    private miServicio: AplicationService
  ) {}

  ngOnInit(): void {
    this.sedeService.sede$.subscribe({
      next: (sedeData) => {
        this.sede = sedeData;
        this.ConsultarMateriasAcademicas();
      },
    });
  }

  // consultar materias academicas
  ConsultarMateriasAcademicas(): void {
    this.miServicio
      .ConsultarMateriasAcademicas(
        this.sede?.id_sede,
        this.sede?.codigo_institucion
      )
      .subscribe({
        next: (data) => {
          this.materias = data;
          console.log(data);
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

  //registrar materias academicas
  registrarmateria(): void {
    const datosmaterias = {
      materia: this.materia,
      codigo_institucion: this.sede.codigo_institucion,
      id_sede: this.sede.id_sede,
    };

    this.miServicio.RegistrarMateriasAcademicas(datosmaterias).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.ConsultarMateriasAcademicas(); // recargar después de registrar
        this.materia = ''; // limpiar campo
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
  //filtrar materias  academicas
  getMateriasFiltradas(): any[] {
    const texto = this.busquedaMateria
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
    return this.materias.filter((m) =>
      m.nombre_materia
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .includes(texto)
    );
  }
  //eliminar materias academicas
  eliminarMateria(data: any): void {
    const datosmaterias = {
      id_materia: data.id_materia,
      codigo_institucion: data.codigo_institucion,
      id_sede: data.id_sede,
    };

    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text:
        '¿Estás seguro de eliminar la materia: ' + data.nombre_materia + ' ?',
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
        this.miServicio.EliminarMateriasAcademicas(datosmaterias).subscribe({
          next: (respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.ConsultarMateriasAcademicas();
            this.busquedaMateria = '';
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
  // actualizar materias academicas
  actualizarMateria(data: any): void {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Deseas actualizar esta materia? </h1>',
      input: 'text',
      icon: 'question',
      inputValue: data.nombre_materia,
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

        const datosmaterias = {
          id_materia: data.id_materia,
          codigo_institucion: data.codigo_institucion,
          id_sede: data.id_sede,
          nombre_materia: nuevoNombre.trim(),
        };

        return this.miServicio
          .EditarMateriasAcademicas(datosmaterias)
          .toPromise()
          .then((respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.ConsultarMateriasAcademicas();
          })
          .catch(() => {
            Swal.showValidationMessage('Error al actualizar la materia');
          });
      },
      allowOutsideClick: () => !Swal.isLoading(),
    });
  }
}
