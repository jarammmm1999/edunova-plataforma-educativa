import { Component, Inject, OnInit, PLATFORM_ID } from '@angular/core';
import { UsuariosService } from '../../../services/usuarios/usuarios';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ImagenesService } from '../../../services/imagenes/imagenes';
import { SedesService } from '../../../services/sedes/sedes';
import { AlertasService } from '../../../services/alertas/alertas';
import { InstitucionesServices } from '../../../services/instituciones/instituciones';

@Component({
  selector: 'app-informacion-sede',
  imports: [CommonModule, FormsModule],
  templateUrl: './informacion-sede.html',
  styleUrl: './informacion-sede.css',
})
export class InformacionSede implements OnInit {
  sede: any = null;
  nuevaImagen: string | null = null;
  modoEdicion = false;
  constructor(
    private usuariosService: UsuariosService,
    private imagenesService: ImagenesService,
    private sedeService: SedesService,
    private alertaService: AlertasService,
    private institucionesServices: InstitucionesServices,
    @Inject(PLATFORM_ID) private platformId: Object
  ) {}

  ngOnInit(): void {
    this.sedeService.sede$.subscribe({
      next: (sedeData) => {
        this.sede = sedeData;
      },
    });
  }

  CargarImagenes(tipo: number, nombreArchivo: string) {
    const datos = {
      id_sede: Number(this.sede?.id_sede),
      codigo_institucion: String(this.sede?.codigo_institucion),
    };

    return this.imagenesService.generarRutaImagen(tipo, nombreArchivo, datos);
  }

  seleccionarImagen(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = () => {
        this.nuevaImagen = reader.result as string;

        // Aquí puedes enviar la imagen directamente a la base de datos si deseas:
        const formData = new FormData();
        formData.append('imagen', file);
        formData.append('id_sede', this.sede.id_sede);
        formData.append('codigo_institucion', this.sede.codigo_institucion);

        this.imagenesService.subirLogoSede(formData).subscribe({
          next: (respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            if (respuesta.sede) {
              this.sedeService.setSede(respuesta.sede);
            }
          },
          error: () =>
            this.alertaService.mostrarAlerta({
              Alerta: 'simple',
              Titulo: 'Error',
              Texto: 'No se pudo subir el logo',
              Tipo: 'error',
            }),
        });
      };
      reader.readAsDataURL(file);
    }
  }

  actualizarColoresTemporales(): void {
    if (this.sede?.colores_sede?.primario && this.sede?.colores_sede?.secundario) {
      this.institucionesServices.aplicarTemaSedes(this.sede.colores_sede);
    }
  }

  actualizarColores() {
    document.documentElement.style.setProperty(
      '--color-primario',
      this.sede.colores.primario
    );
    document.documentElement.style.setProperty(
      '--color-secundario',
      this.sede.colores.secundario
    );
  }

  guardarCambios() {
      const datos = {
        nombre_sede: this.sede?.nombre_sede,
        direccion: this.sede?.direccion,
        telefono: this.sede?.telefono,
        color_primario: this.sede?.colores_sede?.primario,
        color_secundario: this.sede?.colores_sede?.secundario,
        codigo_institucion: this.sede.codigo_institucion,
        id_sede: this.sede?.id_sede,
      };
        this.sedeService.actualizarInformacionSede(datos).subscribe({
        next: (respuesta:any) => {
          this.alertaService.mostrarAlerta(respuesta);
           if (respuesta.sede) {
              this.sedeService.setSede(respuesta.sede);
            } 
        },
        error: (err) => {
          this.alertaService.mostrarAlerta({
            Alerta: 'simple',
            Titulo: 'Error de servidor',
            Texto: 'No se pudo procesar tu solicitud',
            Tipo: 'error',
          });
        },
      });
      
  }
}
