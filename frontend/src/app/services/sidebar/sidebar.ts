import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class SidebarService {

  constructor() { }

  private colapsado = new BehaviorSubject<boolean>(false);
  colapsado$ = this.colapsado.asObservable();

  toggleSidebar() {
    this.colapsado.next(!this.colapsado.value);
  }

  setEstado(colapsado: boolean) {
    this.colapsado.next(colapsado);
  }
}
