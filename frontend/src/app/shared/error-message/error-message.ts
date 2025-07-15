import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common'; // ✅ Necesario para ngIf, etc.
import { ImagenesService } from '../../services/imagenes/imagenes';

@Component({
  selector: 'app-error-message',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './error-message.html',
  styleUrls: ['./error-message.css'],
})
export class ErrorMessage {
  @Input() mensaje: string = 'Ha ocurrido un error inesperado.';
  @Input() imagen: string = 'error.png';

  constructor(private imagenesService: ImagenesService) {}

  CargarImagenes(tipo: number, nombreArchivo: string): string {
    return this.imagenesService.generarRutaImagen(tipo, nombreArchivo);
  }
}
