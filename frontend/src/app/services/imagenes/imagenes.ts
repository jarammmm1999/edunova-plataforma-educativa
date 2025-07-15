import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable } from 'rxjs';
import { environment } from '../../environments/environment.prod';

@Injectable({
  providedIn: 'root',
})
export class ImagenesService {
  constructor(private http: HttpClient) {}

  generarRutaImagen(
  tipo: number,
  nombreArchivo: string,
  sede?: { codigo_institucion: string; id_sede: number },
  documentoProfesor?: string,
  idTema?: number
): string {
  if (!nombreArchivo) return '';

  // Imagen aplicación
  if (tipo === 1) {
    return `${environment.baseUrl}views/assets/image/${nombreArchivo}`;
  }

  // Imagen tipo portada
  if (tipo === 2 || tipo === 5) {
    if (sede?.codigo_institucion && sede?.id_sede) {
      return `${environment.baseUrlImagenes}${sede.codigo_institucion}/sedes/${sede.id_sede}/imagenes/portada_institucion/${nombreArchivo}`;
    }
    return '';
  }

  // Imagen tipo avatar
  if (tipo === 3) {
    if (nombreArchivo === 'AvatarNone.png') {
      return `${environment.baseUrl}views/assets/image/${nombreArchivo}`;
    }

    if (sede?.codigo_institucion && sede?.id_sede) {
      return `${environment.baseUrlImagenes}${sede.codigo_institucion}/sedes/${sede.id_sede}/imagenes/avatares/${nombreArchivo}`;
    }

    return '';
  }

  // Imagen logos de sede
  if (tipo === 4) {
    if (nombreArchivo === 'imagen_principal.png') {
      return `${environment.baseUrl}views/assets/image/sinescudo.png`;
    }
    return `${environment.baseUrl}views/assets/image/logos-sedes/${nombreArchivo}`;
  }

  // Imagen temas educativos profesores
  if (tipo === 6) {
    if (sede?.codigo_institucion && sede?.id_sede && documentoProfesor && idTema) {
      return `${environment.baseUrlImagenes}${sede.codigo_institucion}/sedes/${sede.id_sede}/imagenes/temas_educativos/${documentoProfesor}/${idTema}/${nombreArchivo}`;
    }
    return '';
  }

  if (tipo === 7) {
    if (sede?.codigo_institucion && sede?.id_sede && documentoProfesor && idTema) {
      return `${environment.baseUrlImagenes}${sede.codigo_institucion}/sedes/${sede.id_sede}/imagenes/temas_educativos/${documentoProfesor}/${idTema}/archivos/${nombreArchivo}`;
    }
    return '';
  }

    // Imagenes  de fondo de materias profesores
  if (tipo === 8) {
    if (sede?.codigo_institucion && sede?.id_sede && documentoProfesor) {
      return `${environment.baseUrlImagenes}${sede.codigo_institucion}/sedes/${sede.id_sede}/imagenes/imagenes_materias/${documentoProfesor}/${nombreArchivo}`;
    }
    return '';
  }


   if (tipo === 9) {
    if (sede?.codigo_institucion && sede?.id_sede && documentoProfesor && idTema) {
      return `${environment.baseUrlImagenes}${sede.codigo_institucion}/sedes/${sede.id_sede}/imagenes/temas_educativos/${documentoProfesor}/${idTema}/archivos_entregas_tareas/${nombreArchivo}`;
    }
    return '';
  }

  return '';
}


  subirLogoSede(datos: FormData): Observable<any> {
    return this.http.post<any>(
      `${environment.baseUrl}subirLogoSede`,
      datos
    );
  }
}
