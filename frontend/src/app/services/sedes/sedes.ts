import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { environment } from '../../environments/environment.prod';

@Injectable({
  providedIn: 'root'
})
export class SedesService {

  constructor(private http: HttpClient) {}

  private sedeSubject  = new BehaviorSubject<any>(null);

  public sede$ = this.sedeSubject.asObservable();

  setSede(sede: any): void {
    this.sedeSubject.next(sede);
  }

  getSede(): any {
    return this.sedeSubject.getValue();
  }

  clearSede(): void {
    this.sedeSubject.next(null);
  }

  actualizarInformacionSede(datos: any) {
      const formData = new FormData();
      formData.append('nombre_sede', datos.nombre_sede);
      formData.append('direccion', datos.direccion);
      formData.append('telefono', datos.telefono);
      formData.append('color_primario', datos.color_primario);
      formData.append('color_secundario', datos.color_secundario);
      formData.append('id_sede', datos.id_sede);
      formData.append('codigo_institucion', datos.codigo_institucion);
      return this.http.post(`${environment.baseUrl}actualizarInformacionSede`, formData);
    }
  
}
