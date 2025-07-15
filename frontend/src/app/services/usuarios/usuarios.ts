import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders  } from '@angular/common/http';
import { BehaviorSubject, Observable } from 'rxjs';
import { UsuarioModel } from '../../models/usuarios';
import { environment } from '../../environments/environment.prod';
@Injectable({
  providedIn: 'root',
})
export class UsuariosService {
  constructor(private http: HttpClient) {}

  private usuarioSubject = new BehaviorSubject<any>(null);

  public user$ = this.usuarioSubject.asObservable();

  setUsuario(usuario: any): void {
    this.usuarioSubject.next(usuario);
  }

  public actualizarUsuarioLocal(data: any) {
    this.usuarioSubject.next(data);
  }

  getUsuario(): any {
    return this.usuarioSubject.getValue();
  }

  clearUsuario(): void {
    this.usuarioSubject.next(null);
  }

  obtenerInformacionUsuario(documento: string): Observable<UsuarioModel> {
    return this.http.get<UsuarioModel>(
      `${environment.baseUrl}informacionUsuario/${documento}`
    );
  }

  subirImagenPerfil(datos: FormData): Observable<any> {
    return this.http.post<any>(
      `${environment.baseUrl}subirImagenPerfil`,
      datos
    );
  }

 

  actualizarPerfil(datos: any) {
    const formData = new FormData();
    formData.append('nombres', datos.nombres);
    formData.append('correo', datos.correo);
    formData.append('telefono', datos.telefono);
    formData.append('documento', datos.documento);
    formData.append('password', datos.password);
    formData.append('confirmPassword', datos.confirmPassword);
    formData.append('id_sede', datos.id_sede);
    formData.append('codigo_institucion', datos.codigo_institucion);
    return this.http.post(`${environment.baseUrl}actualizarPerfil`, formData);
  }
}
