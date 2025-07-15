import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { environment } from '../../environments/environment';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class Profesores {
  constructor(private http: HttpClient) {}

  ConsulrtarProfesores(
    id_sede: string,
    codigo_institucion: string
  ): Observable<any[]> {
    return this.http.get<any[]>(
      `${environment.baseUrl}extraerProfesoresAcademicos/${id_sede}/${codigo_institucion}`
    );
  }

  guardarAsignacionesDocente(asignaciones: any[]) {
    const formData = new FormData();
    formData.append('asignaciones', JSON.stringify(asignaciones));
    return this.http.post(
      `${environment.baseUrl}/guardarAsignacionesDocente`,
      formData
    );
  }

  extraerMateriasAsigandasProfesores(
    id_sede: string,
    codigo_institucion: string
  ): Observable<any[]> {
    return this.http.get<any[]>(
      `${environment.baseUrl}extraerMateriasAsigandasProfesores/${id_sede}/${codigo_institucion}`
    );
  }

  extraerMateriasAsigandasProfesorLogueado(
    id_sede: string,
    codigo_institucion: string,
    documento_docente: string
  ): Observable<any[]> {
    return this.http.get<any[]>(
      `${environment.baseUrl}extraerMateriasAsigandasProfesorLogueado/${id_sede}/${codigo_institucion}/${documento_docente}`
    );
  }

   extraerMateriasAsigandasEstudianteLogueado(
    id_sede: string,
    codigo_institucion: string,
    documento_docente: string
  ): Observable<any[]> {
    return this.http.get<any[]>(
      `${environment.baseUrl}extraerMateriasAsigandasEstudianteLogueado/${id_sede}/${codigo_institucion}/${documento_docente}`
    );
  }


  eliminargradosAsignadosProfesores(datos: any) {
    const formData = new FormData();
    formData.append('id_sede', datos.id_sede_encriptado);
    formData.append('codigo_institucion', datos.codigo_institucion_encriptado);
    formData.append('documento_docente', datos.documento_docente);
    formData.append('id_grado', datos.id_grado);
    return this.http.post(
      `${environment.baseUrl}eliminargradosAsignadosProfesores`,
      formData
    );
  }

  eliminargruposAsignadosProfesores(datos: any) {
    const formData = new FormData();
    formData.append('id_sede', datos.id_sede_encriptado);
    formData.append('codigo_institucion', datos.codigo_institucion_encriptado);
    formData.append('documento_docente', datos.documento_docente);
    formData.append('id_grupo', datos.id_grupo);
    return this.http.post(
      `${environment.baseUrl}eliminargruposAsignadosProfesores`,
      formData
    );
  }

  eliminarMateriasAsignadosProfesores(datos: any) {
    const formData = new FormData();
    formData.append('id_sede', datos.id_sede_encriptado);
    formData.append('codigo_institucion', datos.codigo_institucion_encriptado);
    formData.append('documento_docente', datos.documento_docente);
    formData.append('id_materia', datos.id_materia);
    return this.http.post(
      `${environment.baseUrl}eliminarMateriasAsignadosProfesores`,
      formData
    );
  }

  extraerInformacionMateriaSelecionada(datos: any) {
    const formData = new FormData();
    formData.append('id_materia', datos.id_materia);
    formData.append('id_grado', datos.id_grado);
    formData.append('id_grupo', datos.id_grupo);
    formData.append('documento_profesor', datos.documento_profesor);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);

    return this.http.post(
      `${environment.baseUrl}extraerInformacionMateriaSelecionada`,
      formData
    );
  }
}
