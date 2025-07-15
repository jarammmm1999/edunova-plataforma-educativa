import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { Buscador } from '../../../shared/buscador/buscador';
import { ImagenesService } from '../../../services/imagenes/imagenes';
import { UsuariosService,  } from '../../../services/usuarios/usuarios';
import { SedesService } from '../../../services/sedes/sedes';
import { ErrorMessage } from '../../../shared/error-message/error-message';

interface Tarjeta {
  nombre: string;
  imagen: string;
  ruta: string;
}

@Component({
  selector: 'app-configuracion-sistema',
  imports: [Buscador, CommonModule, RouterModule, ErrorMessage],
  templateUrl: './configuracion-sistema.html',
  styleUrl: './configuracion-sistema.css',
})
export class ConfiguracionSistema implements OnInit {
  sede: any = null;
  usuario: any = null;
  tarjetasVisibles: Tarjeta[] = [];

  constructor(
    private imagenesService: ImagenesService,
    private sedeService: SedesService,
    private usuariosService: UsuariosService
  ) {}

  tarjetasPorRol: { [rol: number]: Tarjeta[] } = {
    6: [
      {
        nombre: 'Materias Académicas',
        imagen: 'cursos.png',
        ruta: '/home/materias-academicas',
      },
      {
        nombre: 'Grados Académicos',
        imagen: 'academico.png',
        ruta: '/home/grados-academicos',
      },
      {
        nombre: 'Portada Sedes',
        imagen: 'imagen.png',
        ruta: '/home/portada',
      },
      {
        nombre: 'Periodos Académicos',
        imagen: 'calendario.png',
        ruta: '/home/periodos-academicos',
      },
      {
        nombre: '+ Materias profesores',
        imagen: 'profesor.png',
        ruta: '/home/asignar-materias-profesores',
      },
       {
        nombre: 'Sede',
        imagen: 'colegio.png',
        ruta: '/home/informacion-sede',
      },
    ],
  };

  ngOnInit(): void {
    this.sedeService.sede$.subscribe({
      next: (sedeData) => {
        this.sede = sedeData;
      },
    });
    this.usuariosService.user$.subscribe({
      next: (usuariodeData) => {
        if (usuariodeData) {
          this.usuario = usuariodeData;
          const idRol = this.usuario.id_rol;
          this.tarjetasVisibles = this.tarjetasPorRol[idRol] || [];
        }
      },
    });
  }

  filtrarConfiguracionSistema(termino: string) {
    const term = termino.toLowerCase();
    const idRol = this.usuario?.id_rol;

    if (!idRol) return;

    if (!termino.trim()) {
      this.tarjetasVisibles = [...this.tarjetasPorRol[idRol]];
    } else {
      this.tarjetasVisibles = this.tarjetasPorRol[idRol].filter(
        (tarjeta: Tarjeta) => tarjeta.nombre.toLowerCase().includes(term)
      );
    }
  }

  CargarImagenes(tipo: number, nombreArchivo: string): string {
    return this.imagenesService.generarRutaImagen(tipo, nombreArchivo);
  }
}
