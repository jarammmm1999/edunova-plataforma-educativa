import { Component, OnInit } from '@angular/core';
import { UsuariosService } from '../../../services/usuarios/usuarios';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ImagenesService } from '../../../services/imagenes/imagenes';
import { SedesService } from '../../../services/sedes/sedes';
import { InputsWidget } from '../../../shared/inputs/inputs';
import { ButtonSubmit } from '../../../shared/button-submit/button-submit';
import { AlertasService } from '../../../services/alertas/alertas';
@Component({
  selector: 'app-perfil-usuario',
  imports: [CommonModule, FormsModule,InputsWidget,ButtonSubmit],
  templateUrl: './perfil-usuario.html',
  styleUrl: './perfil-usuario.css',
})
export class PerfilUsuario implements OnInit {
  usuario: any = null;
  sede: any = null;
  nuevaImagen: string | ArrayBuffer | null = null;
  usuarioEdit: any = {
    nombres: '',
    correo: '',
    telefono: '',
    password: '',
    confirmPassword: '',
  };
  constructor(
    private usuariosService: UsuariosService,
    private imagenesService: ImagenesService,
     private sedeService: SedesService,
    private alertaService: AlertasService) {}

  ngOnInit(): void {
    this.usuariosService.user$.subscribe({
      next: (usuariodeData) => {
        this.usuario = usuariodeData;
      },
    });
    this.sedeService.sede$.subscribe({
      next: (sedeData) => {
        this.sede = sedeData;
       
      },
    });
  }

  // Se llama al abrir el modal (puedes conectarlo con (shown.bs.modal))
  prepararEdicion(): void {
    if (this.usuario) {
      this.usuarioEdit = {
        nombres: this.usuario.nombres,
        correo: this.usuario.correo,
        telefono: this.usuario.telefono,
        password: '',
        confirmPassword: '',
      };
    }
  }

    // generar la ruta de la imagenes de la aplicacion dinamicamente
  CargarImagenes(tipo: number, nombreArchivo: string) {
     const datos = {
      id_sede: Number(this.sede?.id_sede),
      codigo_institucion: String(this.sede?.codigo_institucion),
    };

    return this.imagenesService.generarRutaImagen(tipo, nombreArchivo, datos);
  }

  cambiarImagen(event: Event): void {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = () => {
      this.nuevaImagen = reader.result;
    };
    reader.readAsDataURL(file);
       const formData = new FormData();
    formData.append('imagen', file);
    formData.append('documento', this.usuario.documento);
    formData.append('codigo_institucion', this.sede.codigo_institucion);
    formData.append('id_sede', this.sede.id_sede);

    this.usuariosService.subirImagenPerfil(formData).subscribe({
      next: (respuesta) => {
         this.alertaService.mostrarAlerta(respuesta);
      },
      error: (err) => {
       this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error de servidor',
          Texto: 'No se pudo procesar tu solicitud',
          Tipo: 'error',
        });
      }
    });
  }
}


  actualizarPerfil() {
     const datos = {
          nombres: this.usuarioEdit.nombres,
          correo: this.usuarioEdit.correo,
          telefono: this.usuarioEdit.telefono,
          documento: this.usuario.documento,
          codigo_institucion: this.sede.codigo_institucion,
          id_sede: this.sede.id_sede,
          confirmPassword: this.usuarioEdit.confirmPassword,
          password: this.usuarioEdit.password
            ? this.usuarioEdit.password
            : null,
        };
      this.usuariosService.actualizarPerfil(datos).subscribe({
        next: (respuesta:any) => {
          this.alertaService.mostrarAlerta(respuesta);
           if (respuesta.usuario) {
             this.usuariosService.setUsuario(respuesta.usuario);
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
