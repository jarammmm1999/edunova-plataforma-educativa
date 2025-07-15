import { Component, OnInit,Inject, PLATFORM_ID } from '@angular/core';
import { Header } from '../pages/inc/header/header';
import { Sidebar } from '../pages/inc/sidebar/sidebar';
import { CommonModule, isPlatformBrowser  } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { InstitucionesServices } from '../services/instituciones/instituciones';
import { SedesService } from '../services/sedes/sedes';
import { UsuariosService } from '../services/usuarios/usuarios';
import { UsuarioModel } from '../models/usuarios';

@Component({
  selector: 'app-layout',
  imports: [Header, Sidebar, CommonModule, FormsModule, RouterModule],
  templateUrl: './layout.html',
  styleUrl: './layout.css',
})
export class LayoutComponent implements OnInit {
  usuario!: UsuarioModel;

  constructor(
    private institucionesServices: InstitucionesServices,
    private sedeService: SedesService,
    private usuariosService: UsuariosService,
    @Inject(PLATFORM_ID) private platformId: Object
  ) {}

  ngOnInit(): void {
    // Solo ejecuta esto si estás en el navegador
    if (isPlatformBrowser(this.platformId)) {
      const usuarioString = localStorage.getItem('usuario');
      if (!usuarioString) return;

      const usuario = JSON.parse(usuarioString);
      if (usuario?.id_sede) {
        this.institucionesServices.obtenerSedePorId(usuario.id_sede).subscribe({
          next: (respuesta) => {
            const colores = respuesta.colores_sede;
            this.sedeService.setSede(respuesta);
            this.institucionesServices.aplicarTemaSedes(colores);
          },
          error: (err) => console.error('Error cargando sede', err)
        });

        this.usuariosService.obtenerInformacionUsuario(usuario.numero_documento).subscribe({
          next: (respuesta) => {
            this.usuario = respuesta;
            this.usuariosService.setUsuario(respuesta);
          },
          error: (err) => console.error('Error al cargar la información del usuario', err)
        });
      }
    }
  }
}
