import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser'; // ✅ Importar esto
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { ImagenesService } from '../../services/imagenes/imagenes';
import { SedesService } from '../../services/sedes/sedes';
import { AlertasService } from '../../services/alertas/alertas';
import { Estudiantes } from '../../services/estudiantes/estudiantes';
import { UsuariosService } from '../../services/usuarios/usuarios';
import Swal from 'sweetalert2';
import { SocketService } from '../../services/socket/socket';


@Component({
  selector: 'app-comentarios',
  imports: [CommonModule, FormsModule],
  templateUrl: './comentarios.html',
  styleUrl: './comentarios.css',
})
export class Comentarios implements OnInit {
  @Input() comentarios: any[] = [];
  @Output() refrescarComentarios = new EventEmitter<void>();
  sede: any;
  usuario: any;

  constructor(
    private alertaService: AlertasService,
    private sedeService: SedesService,
    private servicioEstudiantes: Estudiantes,
    private imagenesService: ImagenesService,
    private usuariosService: UsuariosService,
    private sanitizer: DomSanitizer,
    private socketService: SocketService
  ) {}

  ngOnInit() {
    this.comentarios.forEach((c) => {
      c.mostrarRespuestas = false;
      c.editandoComentario = false;
      c.textoEditado = c.comentario; // valor inicial
    });
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

    this.socketService.recibirEvento((mensaje) => {
      const tiposActualizarDiscusion = new Set([
        'discusion_eliminada',
        'comentario_creado',
        'discusion_creada',
        'NuevoForoRegistrado',
        'comentario_editado',
      ]);

      if (tiposActualizarDiscusion.has(mensaje.tipo)) {
        this.refrescarComentarios.emit();
      }
    });
  }

  decodeHTML(html: string): SafeHtml {
    const cleanedHtml = html.replace(/&nbsp;/g, ' ');
    return this.sanitizer.bypassSecurityTrustHtml(cleanedHtml); // ✅ Ahora sí funcionará
  }

  responderAComentario(comentario: any) {
    comentario.mostrandoRespuesta = true;
  }

  enviarRespuestaHija(comentarioPadre: any) {
    const texto = comentarioPadre.nuevaRespuesta?.trim();
    if (!texto) return;

    const payload = {
      id_discusion: comentarioPadre.id_discusion,
      texto: texto,
      id_padre: comentarioPadre.id_comentario,
      creado_por: this.usuario?.documento, // ← no olvides esto
    };

    this.servicioEstudiantes.ResponderForosEstudiantes(payload).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        comentarioPadre.mostrandoRespuesta = false;
        comentarioPadre.nuevaRespuesta = '';
        this.refrescarComentarios.emit();
        this.socketService.enviarEvento({
          tipo: 'discusion_creada',
          timestamp: Date.now(),
        });
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error',
          Texto: 'No se pudo registrar la respuesta',
          Tipo: 'error',
        });
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

  eliminarComentario(comentario: any) {
    Swal.fire({
      title: '¿Eliminar comentario?',
      text: 'Esta acción no se puede deshacer',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        const payload = { id_comentario: comentario.id_comentario };

        this.servicioEstudiantes.EliminarComentario(payload).subscribe({
          next: (respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            // Quitar el comentario de la lista local (opcional)
            this.comentarios = this.comentarios.filter(
              (c) => c.id_comentario !== comentario.id_comentario
            );
            this.refrescarComentarios.emit();
            // Notifica a los demás usuarios
            this.socketService.enviarEvento({
              tipo: 'discusion_eliminada',
              timestamp: Date.now(),
            });
          },
          error: () => {
            this.alertaService.mostrarAlerta({
              Alerta: 'simple',
              Titulo: 'Error',
              Texto: 'No se pudo eliminar el comentario',
              Tipo: 'error',
            });
          },
        });
      }
    });
  }

  editarComentario(comentario: any) {
    comentario.editandoComentario = true;
    comentario.textoEditado = comentario.comentario; // carga el texto original
  }

  guardarEdicionComentario(comentario: any) {
    const nuevoTexto = comentario.textoEditado?.trim();
    if (!nuevoTexto) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Campo vacío',
        Texto: 'El comentario no puede estar vacío.',
        Tipo: 'warning',
      });
      return;
    }

    const payload = {
      id_comentario: comentario.id_comentario,
      texto: nuevoTexto,
      creado_por: this.usuario?.documento,
    };

    this.servicioEstudiantes.ActualizarComentario(payload).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        comentario.comentario = nuevoTexto;
        comentario.editandoComentario = false;
        this.refrescarComentarios.emit();
        this.socketService.enviarEvento({
          tipo: 'comentario_editado',
          timestamp: Date.now(),
        });
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error',
          Texto: 'No se pudo actualizar el comentario.',
          Tipo: 'error',
        });
      },
    });
  }

  cancelarEdicionComentario(comentario: any) {
    comentario.editandoComentario = false;
  }
}
