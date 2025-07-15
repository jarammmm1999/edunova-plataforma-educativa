import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from '../../environments/environment.prod';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class MateriaService {
  constructor(private http: HttpClient) {}

  CreartemasEducativos(datos: any) {
    const formData = new FormData();
    formData.append('titulo_tema', datos.titulo_tema);
    formData.append('descripcion_tema', datos.descripcion_tema);
    formData.append('id_materia', datos.id_materia);
    formData.append('id_grado', datos.id_grado);
    formData.append('id_grupo', datos.id_grupo);
    formData.append('documento_profesor', datos.documento_profesor);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}CreartemasEducativos`,
      formData
    );
  }

  SubirImagenesMaterias(datos: FormData): Observable<any> {
    return this.http.post<any>(
      `${environment.baseUrl}SubirImagenesMaterias`,
      datos
    );
  }

  CreartextosEducativos(datos: any) {
    const formData = new FormData();
    formData.append('titulo_texto', datos.titulo_texto);
    formData.append('contenido', datos.contenido);
    formData.append('id_tema', datos.id_tema);
    formData.append('documento_profesor', datos.documento_profesor);
    formData.append('tipo_contenido', datos.tipo_contenido);
    return this.http.post(
      `${environment.baseUrl}CreartextosEducativos`,
      formData
    );
  }

  ActualizartextosEducativos(datos: any) {
    const formData = new FormData();
    formData.append('id_contenido', datos.id_contenido);
    formData.append('contenido_texto', datos.contenido_texto);
    formData.append('titulo_texto', datos.titulo);
    formData.append('tipo_contenido', datos.tipo_contenido);
    return this.http.post(
      `${environment.baseUrl}ActualizartextosEducativos`,
      formData
    );
  }

  EliminartextosEducativos(datos: any) {
    const formData = new FormData();
    formData.append('id_contenido', datos.id_contenido);
    formData.append('documento_profesor', datos.creado_por);
    return this.http.post(
      `${environment.baseUrl}EliminartextosEducativos`,
      formData
    );
  }

  EliminarTemasEducativos(datos: any) {
    const formData = new FormData();
    formData.append('id_contenido', datos.id_contenido);
    formData.append('id_tema', datos.id_tema);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('documento_profesor', datos.documento_profesor);
    return this.http.post(
      `${environment.baseUrl}EliminarTemasEducativos`,
      formData
    );
  }

  ConsultarTemasEducativos(datos: any) {
    const formData = new FormData();
    formData.append('id_materia', datos.id_materia);
    formData.append('id_grado', datos.id_grado);
    formData.append('id_grupo', datos.id_grupo);
    formData.append('documento_profesor', datos.documento_profesor);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}ConsultarTemasEducativos`,
      formData
    );
  }

  actualizarOrdenTemas(datos: any[]) {
    const formData = new FormData();
    formData.append('datos', JSON.stringify(datos));
    return this.http.post(
      `${environment.baseUrl}actualizarOrdenTemas`,
      formData
    );
  }

  actualizarContenidoTemas(datos: any[]) {
    const formData = new FormData();
    formData.append('datos', JSON.stringify(datos));
    return this.http.post(
      `${environment.baseUrl}actualizarContenidoTemas`,
      formData
    );
  }

  consultar_contenido_tema_seleccionado(datos: any) {
    const formData = new FormData();
    formData.append('documento_profesor', datos.documento_profesor);
    formData.append('id_tema', datos.id_tema);
    return this.http.post(
      `${environment.baseUrl}consultar_contenido_tema_seleccionado`,
      formData
    );
  }

  ActualizarNombreTema(datos: any) {
    const formData = new FormData();
    formData.append('id_tema', datos.id_tema);
    formData.append('nombre_tema', datos.nombre_tema);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}ActualizarNombreTema`,
      formData
    );
  }

  ActualizarEstadoTema(datos: any) {
    const formData = new FormData();
    formData.append('id_tema', datos.id_tema);
    formData.append('estado', datos.estado);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}ActualizarEstadoTema`,
      formData
    );
  }

  CargarImagenesTemas(datos: any) {
    const formData = new FormData();
    formData.append('tipo_archivo', datos.tipo);
    formData.append('imagen', datos.contenido);
    formData.append('id_tema', datos.id_tema);
    formData.append('documento_profesor', datos.documento_profesor);
    formData.append('tipo_contenido', datos.tipo_contenido);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}CargarImagenesTemas`,
      formData
    );
  }

  EliminarImagenesEducativas(datos: any) {
    const formData = new FormData();
    formData.append('id_contenido', datos.id_contenido);
    formData.append('documento_profesor', datos.creado_por);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_tema', datos.id_tema);
    return this.http.post(
      `${environment.baseUrl}EliminarImagenesEducativas`,
      formData
    );
  }

  CrearArchivosEducativos(formData: FormData) {
    return this.http.post(
      `${environment.baseUrl}CrearArchivosEducativos`,
      formData
    );
  }

  EliminarArchivosEducativas(datos: any) {
    const formData = new FormData();
    formData.append('id_contenido', datos.id_contenido);
    formData.append('documento_profesor', datos.creado_por);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_tema', datos.id_tema);
    return this.http.post(
      `${environment.baseUrl}EliminarArchivosEducativas`,
      formData
    );
  }

  /*****************videos************************ */

  CargarVideosTemas(datos: any) {
    const formData = new FormData();
    formData.append('video', datos.video);
    formData.append('id_tema', datos.id_tema);
    formData.append('documento_profesor', datos.documento_profesor);
    formData.append('tipo_contenido', datos.tipo_contenido);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(`${environment.baseUrl}CargarVideosTemas`, formData);
  }

  /*********************tareas educativas****************************** */

  CrearTareasEducativas(formData: FormData) {
    return this.http.post(
      `${environment.baseUrl}CrearTareasEducativas`,
      formData
    );
  }

  EditarTareasEducativas(formData: FormData) {
    return this.http.post(
      `${environment.baseUrl}EditarTareasEducativas`,
      formData
    );
  }

  ConsultarInformacionTareasEducativas(datos: any) {
    const formData = new FormData();
    formData.append('id_tema', datos.id_tema);
    formData.append('id_contenido', datos.id_contenido);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}ConsultarInformacionTareasEducativas`,
      formData
    );
  }

  eliminar_archivos_tareas_registrados(datos: any) {
    const formData = new FormData();
    formData.append('id_tarea', datos.id_tarea);
    formData.append('nombre_archivo', datos.nombre_archivo);
    formData.append('documento_profesor', datos.documento_profesor);
    formData.append('id_tema', datos.id_tema);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}eliminar_archivos_tareas_registrados`,
      formData
    );
  }

  EliminarTareasEducativas(datos: any) {
    const formData = new FormData();
    formData.append('id_contenido', datos.id_contenido);
    formData.append('id_tema', datos.id_tema);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('documento_profesor', datos.documento_profesor);
    return this.http.post(
      `${environment.baseUrl}EliminarTareasEducativas`,
      formData
    );
  }

  /*********************talleres educativas****************************** */

  ConsultarInformacionTalleresEducativas(datos: any) {
    const formData = new FormData();
    formData.append('id_tema', datos.id_tema);
    formData.append('id_contenido', datos.id_contenido);
    formData.append('documento_profesor', datos.documento_profesor);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}ConsultarInformacionTalleresEducativas`,
      formData
    );
  }

  eliminar_archivos_taller_registrados(datos: any) {
    const formData = new FormData();
    formData.append('id_taller', datos.id_taller);
    formData.append('nombre_archivo', datos.nombre_archivo);
    formData.append('documento_profesor', datos.documento_profesor);
    formData.append('id_tema', datos.id_tema);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}eliminar_archivos_taller_registrados`,
      formData
    );
  }

  EditarTallereEducativos(formData: FormData) {
    return this.http.post(
      `${environment.baseUrl}EditarTallereEducativos`,
      formData
    );
  }

  EliminarTalleresEducativos(datos: any) {
    const formData = new FormData();
    formData.append('id_contenido', datos.id_contenido);
    formData.append('id_tema', datos.id_tema);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('documento_profesor', datos.documento_profesor);
    return this.http.post(
      `${environment.baseUrl}EliminarTalleresEducativos`,
      formData
    );
  }
}
