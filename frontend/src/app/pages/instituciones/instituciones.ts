import { Component, OnInit } from '@angular/core';
import { instituciones } from '../../models/instituciones';
import { InstitucionesServices } from '../../services/instituciones/instituciones';
import { Loader } from '../../shared/loader/loader';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { ImagenesService } from '../../services/imagenes/imagenes';
import { ErrorMessage } from '../../shared/error-message/error-message';
import { Buscador } from '../../shared/buscador/buscador';

@Component({
  selector: 'app-instituciones',
  standalone: true,
  imports: [CommonModule, RouterModule, Loader,ErrorMessage,Buscador],
  templateUrl: './instituciones.html',
  styleUrls: ['./instituciones.css'],
})
export class Instituciones implements OnInit {
  InstitucionesRegistradas: instituciones[] = [];
  institucionesFiltradas: instituciones[] = [];
  error: boolean = false;
  cargandodatos: boolean = true;

  constructor(
    private institucionesServices: InstitucionesServices,
    private imagenesService: ImagenesService
  ) {}

  // consultar las instituciones registradas en la base de datos y consumirlas
  CargarInstituciones() {
    this.cargandodatos = true; // ✅ activas el loader
    this.institucionesServices.ObtenerInstituciones().subscribe({
      next: (respuesta: any) => {
        if (respuesta && respuesta.length > 0) {
          this.InstitucionesRegistradas = respuesta;
          this.institucionesFiltradas = respuesta;
          this.cargandodatos = false;
        } else {
          this.error = true;
        }
        this.cargandodatos = false;
      },
      error: (err) => {
        this.error = true;
        this.cargandodatos = false;
      },
    });
  }
  // generar la ruta de la imagenes de la aplicacion dinamicamente
  CargarImagenes(tipo: number, nombreArchivo: string) {
    return this.imagenesService.generarRutaImagen(tipo, nombreArchivo);
  }
  // metodo para filtrar las instituciones por nombre
  filtrarInstituciones(termino: string) {
    const term = termino.toLowerCase();
    this.institucionesFiltradas = this.InstitucionesRegistradas.filter(i =>
      i.nombre_institucion.toLowerCase().includes(term)
    );
  }
  ngOnInit() {
    this.CargarInstituciones();
  }
}
