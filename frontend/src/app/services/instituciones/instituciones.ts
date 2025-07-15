import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Inject, Injectable, PLATFORM_ID } from '@angular/core';
import { Observable } from 'rxjs';
import { instituciones } from '../../models/instituciones';
import { SedeModel } from '../../models/sedes';
import { LoginModel } from '../../models/login';
import { environment } from '../../environments/environment.prod';
import { isPlatformBrowser } from '@angular/common';

@Injectable({
  providedIn: 'root',
})
export class InstitucionesServices {
  constructor(private http: HttpClient, @Inject(PLATFORM_ID) private platformId: Object) {}

   aplicarTemaSedes(colores: any): void {
    if (isPlatformBrowser(this.platformId)) {
      document.documentElement.style.setProperty('--color-primario', colores.primario);
      document.documentElement.style.setProperty('--color-secundario', colores.secundario);
    }
  }

  ObtenerInstituciones(): Observable<instituciones> {
    return this.http.get<instituciones>(`${environment.baseUrl}/instituciones`);
  }

  obtenerSedesPorInstitucion(id: string): Observable<SedeModel[]> {
    return this.http.get<SedeModel[]>(`${environment.baseUrl}/sedes/${id}`);
  }

  obtenerSedePorId(id: string): Observable<LoginModel> {
    return this.http.get<LoginModel>(`${environment.baseUrl}login/${id}`);
  }

  obtenerImagenesPorSede(id: string): Observable<string[]> {
    return this.http.get<string[]>(
      `${environment.baseUrl}imagenes-portada/${id}`
    );
  }


  iniciarSesion(credenciales: any): Observable<any> {
    const headers = new HttpHeaders({ 'Content-Type': 'application/json' });
    const url = `${environment.baseUrl}inicio_session`;
    return this.http.post<any>(url, credenciales, { headers });
  }
}
