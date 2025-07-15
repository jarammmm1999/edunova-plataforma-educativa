import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { environment } from '../../environments/environment';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class Estudiantes {
  constructor(private http: HttpClient) {}

  extraerInformacionMateriaSelecionadaEstudiantes(datos: any) {
    const formData = new FormData();
    formData.append('id_materia', datos.id_materia);
    formData.append('id_grado', datos.id_grado);
    formData.append('id_grupo', datos.id_grupo);
    formData.append('documento_estudiantes', datos.documento_estudiantes);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}extraerInformacionMateriaSelecionadaEstudiantes`,
      formData
    );
  }

  ConsultarTemasEducativosEstudiantes(datos: any) {
    const formData = new FormData();
    formData.append('id_materia', datos.id_materia);
    formData.append('id_grado', datos.id_grado);
    formData.append('id_grupo', datos.id_grupo);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}ConsultarTemasEducativosEstudiantes`,
      formData
    );
  }

  consultar_contenido_tema_seleccionado_estudiantes(datos: any) {
    const formData = new FormData();
    formData.append('id_tema', datos.id_tema);
    return this.http.post(
      `${environment.baseUrl}consultar_contenido_tema_seleccionado_estudiantes`,
      formData
    );
  }

  ContestarForosUsuarios(formData: FormData) {
    return this.http.post(
      `${environment.baseUrl}ContestarForosUsuarios`,
      formData
    );
  }

  ConsultarDiscucionesForos(datos: any) {
    const formData = new FormData();
    formData.append('id_contenido', datos.id_contenido);
    formData.append('id_tema', datos.id_tema);
    formData.append('id_materia', datos.id_materia);
    formData.append('id_grupo', datos.id_grupo);
    formData.append('id_grado', datos.id_grado);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}ConsultarDiscucionesForos`,
      formData
    );
  }

  ResponderForosEstudiantes(datos: any) {
    const formData = new FormData();
    formData.append('id_discusion', datos.id_discusion);
    formData.append('comentario', datos.texto); // ✅ El backend espera 'comentario'
    formData.append('creado_por', datos.creado_por);
    if (datos.id_padre) {
      formData.append('id_padre', datos.id_padre); // Solo si existe
    }
    return this.http.post(
      `${environment.baseUrl}ResponderForosEstudiantes`,
      formData
    );
  }

  EliminarDiscusion(datos: any) {
    const formData = new FormData();
    formData.append('id_discusion', datos.id_discusion);
    return this.http.post(`${environment.baseUrl}EliminarDiscusion`, formData);
  }

  EliminarComentario(datos: any) {
    const formData = new FormData();
    formData.append('id_comentario', datos.id_comentario);
    return this.http.post(`${environment.baseUrl}EliminarComentario`, formData);
  }

  ActualizarDiscusion(datos: any) {
    const formData = new FormData();
    formData.append('id_discusion', datos.id_discusion);
    formData.append('titulo_discusion', datos.titulo);
    formData.append('descripcion', datos.descripcion);
    formData.append('creado_por', datos.creado_por);
    return this.http.post(`${environment.baseUrl}ActualizarDiscusion`, formData);
  }

    ActualizarComentario(datos: any) {
    const formData = new FormData();
    formData.append('id_comentario', datos.id_comentario);
    formData.append('texto', datos.texto);
    formData.append('creado_por', datos.creado_por);
    return this.http.post(`${environment.baseUrl}ActualizarComentario`, formData);
  }

  ConsultarEnregasTareasEstudiantes(datos: any) {
    const formData = new FormData();
    formData.append('id_tarea', datos.id_tarea);
    formData.append('documento_estudiante', datos.documento_estudiante);
    return this.http.post(`${environment.baseUrl}ConsultarEnregasTareasEstudiantes`, formData);
  }

  EnviarEntregaTareasEstudiante(formData: FormData) {
    return this.http.post(
      `${environment.baseUrl}EnviarEntregaTareasEstudiante`,
      formData
    );
  }

   EliminarArchivoEntregaEstudiantes(formData: FormData) {
    return this.http.post(
      `${environment.baseUrl}EliminarArchivoEntregaEstudiantes`,
      formData
    );
  }

   EliminartextoEntregaEstudiantes(formData: FormData) {
    return this.http.post(
      `${environment.baseUrl}EliminartextoEntregaEstudiantes`,
      formData
    );
  }

}
