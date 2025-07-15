import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule,  } from '@angular/forms';
import { SedesService } from '../../../services/sedes/sedes';
import { AlertasService } from '../../../services/alertas/alertas';
import { AplicationService } from '../../../services/aplication/aplication';
import { ButtonSubmit } from '../../../shared/button-submit/button-submit';
import { ImagenesService } from '../../../services/imagenes/imagenes';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-portada',
  imports: [
    CommonModule,
    FormsModule,
    ButtonSubmit,
  ],
  templateUrl: './portada.html',
  styleUrl: './portada.css',
})
export class Portada implements OnInit {
  sede: any = null;
  imagenesSeleccionadas: { archivo: File; url: string }[] = [];
  imagenesSwiper: any[] = [];
  imagenesAgrupadas: { [id: string]: any[] } = {};

  constructor(
    private alertaService: AlertasService,
    private sedeService: SedesService,
    private miServicio: AplicationService,
    private imagenesService: ImagenesService
  ) {}

  onFileSelected(event: any): void {
    const archivos = event.target.files;
    this.procesarArchivos(archivos);
  }

  onDrop(event: DragEvent): void {
    event.preventDefault();
    const archivos = event.dataTransfer?.files;
    if (archivos) {
      this.procesarArchivos(archivos);
    }
  }

  onDragOver(event: DragEvent): void {
    event.preventDefault();
  }

  procesarArchivos(archivos: FileList | null): void {
    if (!archivos) return;

    Array.from(archivos).forEach((archivo) => {
      const reader = new FileReader();
      reader.onload = (e: any) => {
        this.imagenesSeleccionadas.push({
          archivo,
          url: e.target.result,
        });
      };
      reader.readAsDataURL(archivo);
    });
  }

  eliminarImagen(index: number): void {
    this.imagenesSeleccionadas.splice(index, 1);
  }

  guardarImagenes(): void {
    if (this.imagenesSeleccionadas.length === 0) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Sin imágenes',
        Texto: 'Por favor selecciona al menos una imagen para guardar.',
        Tipo: 'warning',
      });
      return;
    }

    const formData = new FormData();

    // Adjuntar cada imagen al FormData
    this.imagenesSeleccionadas.forEach((imagen: any, index: number) => {
      formData.append('imagenes[]', imagen.archivo, imagen.archivo.name);
    });

    // Adjuntar información adicional
    formData.append('codigo_institucion', this.sede.codigo_institucion);
    formData.append('id_sede', this.sede.id_sede);

    this.miServicio.subirImagenesPortada(formData).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.imagenesSeleccionadas = [];
        this.consultarImagenesPortadas();
      },
      error: (error) => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error',
          Texto: 'No se pudieron guardar las imágenes',
          Tipo: 'error',
        });
      },
    });
  }

  
  // generar la ruta de la imagenes de la aplicacion dinamicamente
  CargarImagenes(tipo: number, nombreArchivo: string) {
     const datos = {
      id_sede: Number(this.sede?.id_sede),
      codigo_institucion: String(this.sede?.codigo_institucion),
    };

    return this.imagenesService.generarRutaImagen(tipo, nombreArchivo, datos);
  }

   activarImagen(estado: string, id_estado: string): void {
    const estadoNumero = Number(estado); 
    const esActivo = id_estado ;

    const datos ={
      id_sede: this.sede.id_sede,
      codigo_institucion: this.sede.codigo_institucion,
      estado: estadoNumero,
    };
    
    Swal.fire({
      title: `<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario); margin-bottom: -12px;">¿Estás seguro?</h1>`,
      text:
        esActivo == '1'
          ? '¿Deseas desactivar la portada para que no se muestre en el inicio?'
          : '¿Deseas activar la portada para que se muestre en el inicio?',

      icon: 'question',
      showCancelButton: true,
      confirmButtonText:  esActivo == '1' ? 'Sí, desactivar' : 'Sí, activar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: esActivo ? '#e74c3c' : '#2ecc71',
      cancelButtonColor: '#d33',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        this.miServicio.actualizarEstadoPortada(datos).subscribe({
          next: (respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.consultarImagenesPortadas();
          },
          error: () => {
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

  eliminarImagenportada(imagen: string): void {
    const datos = {
      id_sede: this.sede.id_sede,
      codigo_institucion: this.sede.codigo_institucion,
      imagen,
    };

    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text:
        '¿Estás seguro de eliminar las imagenes de la portada : ' +
        imagen +
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
        this.miServicio.eliminarImagenesPortadas(datos).subscribe({
          next: (respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.consultarImagenesPortadas();
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

  consultarImagenesPortadas(): void {
    const datos = {
      id_sede: this.sede?.id_sede,
      codigo_institucion: this.sede?.codigo_institucion,
    };

    this.miServicio.ConsultarImagenesPortada(datos).subscribe({
      next: (respuesta: any[]) => {
        this.imagenesAgrupadas = {};

        respuesta.forEach((item) => {
          let imagenes: string[] = [];

          try {
            imagenes = JSON.parse(item.nombre_imagenes);
          } catch (e) {
            console.error('Error al parsear nombre_imagenes:', e);
          }

          if (!this.imagenesAgrupadas[item.id]) {
            this.imagenesAgrupadas[item.id] = [];
          }

          imagenes.forEach((nombre) => {
            this.imagenesAgrupadas[item.id].push({
              id: item.id,
              nombre_imagen: nombre,
              estado: item.estado,
              codigo_institucion: item.codigo_institucion,
              id_sede: item.id_sede,
              fecha_subida: item.fecha_subida,
            });
          });
        });
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error',
          Texto: 'No se pudieron cargar las imágenes',
          Tipo: 'error',
        });
      },
    });
  }

  ngOnInit(): void {
    this.sedeService.sede$.subscribe({
      next: (sedeData) => {
        this.sede = sedeData;
      },
    });
    this.consultarImagenesPortadas();
  }

}
