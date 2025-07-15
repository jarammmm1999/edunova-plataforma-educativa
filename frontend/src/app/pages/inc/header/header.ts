import { Component, OnInit } from '@angular/core';
import { SedesService } from '../../../services/sedes/sedes';
import { UsuariosService } from '../../../services/usuarios/usuarios';
import { ImagenesService } from '../../../services/imagenes/imagenes';
import { CommonModule } from '@angular/common';
import { AlertasService } from '../../../services/alertas/alertas';
import { Router } from '@angular/router';
import Swal from 'sweetalert2';


@Component({
  selector: 'app-header',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './header.html',
  styleUrl: './header.css',
})
export class Header implements OnInit {
  sede: any = null;
  usuario: any = null;
  tokenUser!: string;
  mostrarMensajes: boolean = false;
  mostrarNotificaciones: boolean = false;
  mostrarConfiguraciones: boolean = false;

  constructor(
    private sedeService: SedesService,
    private usuariosService: UsuariosService,
    private alertaService: AlertasService,
    private imagenesService: ImagenesService,
    private router: Router
  ) {}

  toggleMensajes() {
    this.mostrarMensajes = !this.mostrarMensajes;
    this.mostrarNotificaciones = false;
    this.mostrarConfiguraciones = false;
  }

  toggleNotificaciones() {
    this.mostrarNotificaciones = !this.mostrarNotificaciones;
    this.mostrarMensajes = false;
    this.mostrarConfiguraciones = false;
  }

  toggleConfiguraciones() {
    this.mostrarConfiguraciones = !this.mostrarConfiguraciones;
    this.mostrarMensajes = false;
    this.mostrarNotificaciones = false;
  }

  // generar la ruta de la imagenes de la aplicacion dinamicamente
  CargarImagenes(tipo: number, nombreArchivo: string) {
    const datos = {
      id_sede: Number(this.sede?.id_sede),
      codigo_institucion: String(this.sede?.codigo_institucion),
    };

    return this.imagenesService.generarRutaImagen(tipo, nombreArchivo, datos);
  }

  //cerrar session usuarios
  cerrarSesion(tokenRecibido: string): void {
    const usuario = JSON.parse(localStorage.getItem('usuario') || '{}');
    Swal.fire({
      title: this.usuario.nombres,
      text: 'Estás a punto de cerrar la sesión',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, salir!',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#3085d6', // Azul
      cancelButtonColor: '#d33', // Rojo
      customClass: {
        confirmButton: 'btn-confirmar',
        cancelButton: 'btn-cancelar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        if (usuario.token_usuario && usuario.token_usuario === tokenRecibido) {
          localStorage.removeItem('usuario');
          this.usuariosService.clearUsuario(); // ✅ limpia el BehaviorSubject
          this.router
            .navigateByUrl('/', { skipLocationChange: true })
            .then(() => {
              this.router.navigate(['/login', this.sede.id_sede_encriptado]);
            });
          
        } else {
          this.alertaService.mostrarAlerta({
            Alerta: 'simple',
            Titulo: 'Token inválido',
            Texto: 'No se puede cerrar la sesión, verificación fallida.',
            Tipo: 'error',
          });
        }
      }
    });
  }

  irAPerfil() {
    this.router.navigate(['/home/perfil-usuario']);
  }

  ngOnInit(): void {
    if (typeof window !== 'undefined') {
      const usuarioString = localStorage.getItem('usuario');

      if (!usuarioString) {
        this.router.navigate(['/']);
      } else {
        const usuario = JSON.parse(usuarioString);
        this.tokenUser = usuario.token_usuario;
      }
    }

    this.sedeService.sede$.subscribe({
      next: (sedeData) => {
        this.sede = sedeData;
      },
    });

    this.usuariosService.user$.subscribe({
      next: (usuariodeData) => {
        this.usuario = usuariodeData;
      },
    });
  }
}
