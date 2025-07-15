import { Injectable } from '@angular/core';
import Swal from 'sweetalert2';
import { Router } from '@angular/router';

@Injectable({
  providedIn: 'root',
})
export class AlertasService {
  constructor(private router: Router) {}

  mostrarAlerta(alerta: any, callback?: () => void): void {
    switch (alerta.Alerta) {
      case 'simple':
        Swal.fire({
          title:
            '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -4px;">' +
            alerta.Titulo +
            '</h1>',
          html: alerta.Texto,
          icon: alerta.Tipo,
          confirmButtonText: 'Aceptar',
           didOpen: () => {
            const confirmBtn = document.querySelector('.swal2-confirm') as HTMLElement;
            if (confirmBtn) {
              confirmBtn.style.backgroundColor = 'var(--color-primario)';
              confirmBtn.style.color = '#fff';
          
            }
          }
        });
        break;
      
      case 'Toast':
       const Toast = Swal.mixin({
         toast: true,
         position: 'top-end',
         showConfirmButton: false,
         timer: 3000,
         timerProgressBar: true,
         didOpen: (toast) => {
           toast.onmouseenter = Swal.stopTimer;
           toast.onmouseleave = Swal.resumeTimer;
         },
       });
       Toast.fire({
         icon: alerta.Tipo,
         title: alerta.Texto,
       });
        break;

      case 'recargar':
        Swal.fire({
          title:
            '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -4px;">' +
            alerta.Titulo +
            '</h1>',
          html: alerta.Texto,
          icon: alerta.Tipo,
          confirmButtonText: 'Aceptar',
           didOpen: () => {
            const confirmBtn = document.querySelector('.swal2-confirm') as HTMLElement;
            if (confirmBtn) {
              confirmBtn.style.backgroundColor = 'var(--color-primario)';
              confirmBtn.style.color = '#fff';
          
            }
          }
        }).then((result) => {
          if (result.isConfirmed && callback) {
            callback(); // ✅ ejecuta función Angular del componente
          }
        });
        break;

      case 'redireccionar':
        this.router.navigate([alerta.URL]);
        break;

      default:
        console.warn('Tipo de alerta no reconocido:', alerta);
        break;
    }
  }
}
