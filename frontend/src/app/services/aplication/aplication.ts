import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { environment } from '../../environments/environment';
import { Observable } from 'rxjs';

export interface Sexo {
  id_sexo: string;
  descripcion: string;
}

export interface Estado {
  id_estado: string;
  descripcion: string;
}

export interface Roles {
  id_rol: string;
  descripcion: string;
}

export interface Grados {
  id_grado : string;
  nombre_grado: string;
}

export interface Grupos {
  id_grupo  : string;
  nombre_grupo: string;
}

@Injectable({
  providedIn: 'root',
})
export class AplicationService {
  constructor(private http: HttpClient) {}

  ObtenedorSexosAplication(): Observable<Sexo[]> {
    return this.http.get<Sexo[]>(`${environment.baseUrl}aplicacion/sexos`);
  }

  ObtenedorEstadoAplication(): Observable<Estado[]> {
    return this.http.get<Estado[]>(`${environment.baseUrl}aplicacion/estados`);
  }
  ObtenedorRolesAplication(): Observable<Roles[]> {
    return this.http.get<Roles[]>(`${environment.baseUrl}aplicacion/roles`);
  }

  ObtenedorGradosAplication(
    idSede: string,
    codigoInstitucion: string
  ): Observable<Grados[]> {
    return this.http.get<Grados[]>(
      `${environment.baseUrl}aplicacion/grados/${idSede}/${codigoInstitucion}`
    );
  }

  ObtenedorGruposGradosAplication(idgrupo: string): Observable<Grupos[]> {
    return this.http.get<Grupos[]>(
      `${environment.baseUrl}aplicacion/grupo/${idgrupo}`
    );
  }

  RegistraUsuarios(datos: any): Observable<any> {
    const url = `${environment.baseUrl}registrar_usuarios`;

    const formData = new FormData();

    // Campos simples
    formData.append('documento', datos.documento);
    formData.append('nombre', datos.nombre);
    formData.append('correo', datos.correo);
    formData.append('telefono', datos.telefono);
    formData.append('sexo', datos.sexo);
    formData.append('contrasena', datos.contrasena);
    formData.append('confirmContrasena', datos.confirmContrasena);
    formData.append('estado', datos.estado);
    formData.append('rol', datos.rol);
    formData.append('grado', datos.grado);
    formData.append('grupo', datos.grupo);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);

    // Imagen
    if (datos.imagen) {
      formData.append('imagen', datos.imagen);
    }

    // Documentos
    datos.documentos.forEach((doc: any, i: number) => {
      formData.append(`documentos[${i}][archivo]`, doc.archivo);
      formData.append(
        `documentos[${i}][nombrePersonalizado]`,
        doc.nombrePersonalizado
      );
    });

    // Acudientes
    formData.append('acudientes', JSON.stringify(datos.acudientes)); // los envías como string JSON

    return this.http.post<any>(url, formData); // NO pongas headers
  }

  ActualizarUsuario(datos: any): Observable<any> {
    const url = `${environment.baseUrl}actualizar_usuarios`;

    const formData = new FormData();
    formData.append('documento', datos.documento_encription);
    formData.append('nombre', datos.nombre_usuario);
    formData.append('correo', datos.correo_usuario);
    formData.append('telefono', datos.telefono_usuario);
    formData.append('sexo', datos.id_sexo);
    formData.append('contrasena', datos.contrasena);
    formData.append('confirmContrasena', datos.confirmContrasena);
    formData.append('estado', datos.estado);
    formData.append('rol', datos.id_rol);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);
    // Imagen
    if (datos.imagen) {
      formData.append('imagen', datos.imagen);
    }

    return this.http.post<any>(url, formData); // NO pongas headers
  }

  ActualizarGradoUsuario(datos: any): Observable<any> {
    const url = `${environment.baseUrl}actualizar_grado`;
    const formData = new FormData();
    formData.append('documento', datos.documento_encription);
    formData.append('id_grado', datos.id_grado);
    formData.append('id_grupo', datos.id_grupo);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);

    return this.http.post<any>(url, formData); // NO pongas headers
  }

  CargarDocumentosUsuarios(datos: any): Observable<any> {
    const url = `${environment.baseUrl}cargar_documentos`;
    const formData = new FormData();
    formData.append('documento', datos.documento_encription);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);
    // Documentos
    datos.documentos.forEach((doc: any, i: number) => {
      formData.append(`documentos[${i}][archivo]`, doc.archivo);
      formData.append(
        `documentos[${i}][nombrePersonalizado]`,
        doc.nombrePersonalizado
      );
    });

    return this.http.post<any>(url, formData); // NO pongas headers
  }

  ObtenederUsuariosSede(
    idSede: string,
    codigo_institucion: string,
    documento: string
  ): Observable<any[]> {
    return this.http.get<any[]>(
      `${environment.baseUrl}aplicacion/ConsultarUsuariosSede/${idSede}/${codigo_institucion}/${documento}`
    );
  }

  ObtenerDocumentosUsuarios(id_matricula: string): Observable<any[]> {
    return this.http.get<any[]>(
      `${environment.baseUrl}ExtraerDocumentosUsuario/${id_matricula}`
    );
  }

  eliminarUsuario(
    idSede: string,
    codigo_institucion: string,
    documento: string
  ): Observable<any> {
    return this.http.get<Grupos[]>(
      `${environment.baseUrl}aplicacion/DeleteUser/${idSede}/${codigo_institucion}/${documento}`
    );
  }

  Eliminardocumentousuarios(datos: any): Observable<any> {
    const url = `${environment.baseUrl}EliminarDocumentos`;
    const formData = new FormData();
    formData.append('nombre_documento_user', datos.nombre);
    formData.append('descripcion_documento_user', datos.descripcion);
    formData.append('id_matricula', datos.id_matricula);
    formData.append('documento_usuario', datos.documento_usuario);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);
    return this.http.post<any>(url, formData); // NO pongas headers
  }

  ObtenerAcudientessUsuarios(documento: string): Observable<any[]> {
    return this.http.get<any[]>(
      `${environment.baseUrl}ExtraerAcudientesUsuario/${documento}`
    );
  }

  EliminarAcudientesusuarios(datos: any): Observable<any> {
    const url = `${environment.baseUrl}EliminarAcudiente`;
    const formData = new FormData();
    formData.append('id_acudiente', datos.id_acudiente);
    formData.append('documento_estudiante', datos.documento_estudiante);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);
    return this.http.post<any>(url, formData);
  }

  EditarAcudientesUsuarios(datos: any): Observable<any> {
    const url = `${environment.baseUrl}EditarAcudiente`;
    const formData = new FormData();
    formData.append('id_acudiente', datos.id_acudiente);
    formData.append('nombres', datos.nombres);
    formData.append('telefono', datos.telefono);
    formData.append('correo', datos.correo);
    formData.append('direccion', datos.direccion);
    formData.append('parentesco', datos.parentesco);
    formData.append('sexo', datos.sexo);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);
    return this.http.post<any>(url, formData);
  }

  AgregarAcudientesUsuarios(datos: any): Observable<any> {
    const url = `${environment.baseUrl}AgregarAcudiente`;
    const formData = new FormData();
    formData.append('documento_estudiante', datos.documento_estudiante);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);
    formData.append('acudientes', JSON.stringify(datos.acudientes)); // los envías como string JSON
    return this.http.post<any>(url, formData);
  }

  /*****************************Materias Academicas*****************************************/

  RegistrarMateriasAcademicas(datos: any): Observable<any> {
    const url = `${environment.baseUrl}AgregarMateriasAcademicas`;
    const formData = new FormData();
    formData.append('materia', datos.materia);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);
    return this.http.post<any>(url, formData);
  }

  ConsultarMateriasAcademicas(
    id_sede: string,
    codigo_institucion: string
  ): Observable<any[]> {
    return this.http.get<any[]>(
      `${environment.baseUrl}ConsultarMateriasAcademicas/${id_sede}/${codigo_institucion}`
    );
  }

  EliminarMateriasAcademicas(datos: any): Observable<any> {
    const url = `${environment.baseUrl}EliminarMateriasAcademicas`;
    const formData = new FormData();
    formData.append('id_materia', datos.id_materia);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);
    return this.http.post<any>(url, formData);
  }

  EditarMateriasAcademicas(datos: any): Observable<any> {
    const url = `${environment.baseUrl}EditarMateriasAcademicas`;
    const formData = new FormData();
    formData.append('id_materia', datos.id_materia);
    formData.append('nombre_materia', datos.nombre_materia);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);
    return this.http.post<any>(url, formData);
  }

  /*****************************Grados Academicas*****************************************/

  RegistrarGradosAcademicos(datos: any): Observable<any> {
    const url = `${environment.baseUrl}AgregarGradosAcademicos`;
    const formData = new FormData();
    formData.append('grado', datos.grado);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);
    return this.http.post<any>(url, formData);
  }
  ConsultarGradosAcademicos(
    id_sede: string,
    codigo_institucion: string
  ): Observable<any[]> {
    return this.http.get<any[]>(
      `${environment.baseUrl}ConsultarGradosAcademicos/${id_sede}/${codigo_institucion}`
    );
  }

  EliminarGradosAcademicos(datos: any): Observable<any> {
    const url = `${environment.baseUrl}EliminarGradosAcademicos`;
    const formData = new FormData();
    formData.append('id_grado', datos.id_grado);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);
    return this.http.post<any>(url, formData);
  }

  EditarGradosAcademicos(datos: any): Observable<any> {
    const url = `${environment.baseUrl}EditarGradosAcademicos`;
    const formData = new FormData();
    formData.append('id_grado', datos.id_grado);
    formData.append('nombre_grado', datos.nombre_grado);
    formData.append('codigo_institucion', datos.codigo_institucion);
    formData.append('id_sede', datos.id_sede);
    return this.http.post<any>(url, formData);
  }

  obtenerGruposPorGrado(id_grado: string): Observable<any[]> {
    return this.http.get<any[]>(
      `${environment.baseUrl}obtenerGruposPorInstitucionYSede/${id_grado}}`
    );
  }

  registrarGrupos(grupos: any) {
    const formData = new FormData();
    formData.append('grupos', JSON.stringify(grupos)); // 👈 aquí va grupos, no datos.nuevos_grupos
    return this.http.post(`${environment.baseUrl}RegistrarGrupos`, formData);
  }

  eliminarGrupo(grupos: any) {
    const formData = new FormData();
    formData.append('grupos', JSON.stringify(grupos)); // 👈 aquí va grupos, no datos.nuevos_grupos
    return this.http.post(`${environment.baseUrl}eliminarGrupo`, formData);
  }

  EditarGrupo(grupos: any) {
    const formData = new FormData();
    formData.append('grupos', JSON.stringify(grupos)); // 👈 aquí va grupos, no datos.nuevos_grupos
    return this.http.post(`${environment.baseUrl}EdiatrarGrupo`, formData);
  }

  registrarMateriasPorGrado(data: any) {
    const formData = new FormData();
    formData.append('datos', JSON.stringify(data));
    return this.http.post(
      `${environment.baseUrl}registrarMateriasPorGrado`,
      formData
    );
  }

  obtenerMateriasPorGrado(): Observable<any[]> {
    return this.http.get<any[]>(
      `${environment.baseUrl}extraerMateriasPorGrado`
    );
  }

  eliminarMateriaDeGrado(idRegistro: number, idMateria: string) {
    const formData = new FormData();
    formData.append('id_registro', idRegistro.toString());
    formData.append('id_materia', idMateria);
    return this.http.post(
      `${environment.baseUrl}/eliminarMateriaDeGrado`,
      formData
    );
  }

  agregarNuevamateriaporGrados(data:any) {
    const formData = new FormData();
    formData.append('id_grado', data.id_grado.toString());
    formData.append('id_materia', data.id_materia);
    return this.http.post(
      `${environment.baseUrl}/agregarNuevamateriaporGrados`,
      formData
    );
  }

    eliminarMateriasGrado(id_grado:string) {
    const formData = new FormData();
    formData.append('id_grado', id_grado.toString());
    return this.http.post(
      `${environment.baseUrl}/eliminarMateriasGrado`,
      formData
    );
  }

  /********************imagenes de portadas de las instituciones******************** */

  subirImagenesPortada(formData: FormData) {
    return this.http.post(
      `${environment.baseUrl}GuardarImagenesPortada`,
      formData
    );
  }

  ConsultarImagenesPortada(datos: any) {
    return this.http.get<any[]>(
      `${environment.baseUrl}ConsultarImagenesPortada/${datos.id_sede}/${datos.codigo_institucion}`
    );
  }

  eliminarImagenesPortadas(datos: any) {
    const formData = new FormData();
    formData.append('idPortada', datos.imagen);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}eliminarImagenesPortadas`,
      formData
    );
  }

  actualizarEstadoPortada(datos: any) {
    const formData = new FormData();
    formData.append('idPortada', datos.estado.toString());
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}editarEstadoPortadas`,
      formData
    );
  }

  /******************** periodos academicos ******************** */

  RegistrarPeriodosAcademicos(datos: any) {
    const formData = new FormData();
    formData.append('nombre_periodo', datos['nombre_periodo']);
    formData.append('fecha_inicio', datos['fecha_inicio']);
    formData.append('fecha_fin', datos['fecha_fin']);
    formData.append('codigo_institucion', datos['codigo_institucion']);
    formData.append('id_sede', datos['id_sede']);
    return this.http.post(
      `${environment.baseUrl}RegistrarPeriodosAcademicos`,
      formData
    );
  }
  ConsultarPeriodosAcademicos(datos: any) {
    return this.http.get<any[]>(
      `${environment.baseUrl}ConsultarPeriodosAcademicos/${datos.id_sede}/${datos.codigo_institucion}`
    );
  }

  actualizarperiodosacademicos(datos: any) {
    const formData = new FormData();
    formData.append('id_periodo', datos.id_periodo);
    formData.append('nombre_periodo', datos.nombre_periodo);
    formData.append('fecha_inicio', datos.fecha_inicio);
    formData.append('fecha_fin', datos.fecha_fin);
    return this.http.post(
      `${environment.baseUrl}actualizarPeriodosAcademicos`,
      formData
    );
  }

  eliminarPeriodoAcademico(datos: any) {
    const formData = new FormData();
    formData.append('id_periodo', datos.id_periodo);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(
      `${environment.baseUrl}eliminarPeriodoAcademicos`,
      formData
    );
  }
}


  
