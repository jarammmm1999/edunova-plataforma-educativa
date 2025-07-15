import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, RouterModule } from '@angular/router';
import { CommonModule } from '@angular/common';
import { ImagenesService } from '../../services/imagenes/imagenes';
import { InstitucionesServices } from '../../services/instituciones/instituciones';
import { SedeModel } from '../../models/sedes';
import { Loader } from '../../shared/loader/loader';
import { ErrorMessage } from '../../shared/error-message/error-message';
import { Buscador } from '../../shared/buscador/buscador';
@Component({
  selector: 'app-sede',
  imports: [CommonModule, RouterModule, Loader, ErrorMessage, Buscador],
  templateUrl: './sede.html',
  styleUrl: './sede.css',
})
export class Sede implements OnInit {
  IdInstitucion!: string;
  error: boolean = false;
  cargandodatos: boolean = true;
  sedesRegistradas: SedeModel[] = [];
  SedesFiltradas: SedeModel[] = [];
  constructor(
    private route: ActivatedRoute,
    private institucionesServices: InstitucionesServices,
    private imagenesService: ImagenesService
  ) {}

  // consultar las sedes registradas en la base de datos y consumirlas
  CargarSedes() {
    this.cargandodatos = true; // ✅ activas el loader
    this.institucionesServices
      .obtenerSedesPorInstitucion(this.IdInstitucion)
      .subscribe({
        next: (respuesta: any) => {
          if (respuesta && respuesta.length > 0) {
            const sedesParseadas = respuesta.map((sede: any) => {
              let colores = { primario: '#ffffff', secundario: '#000000' }; // por defecto

              try {
                colores = JSON.parse(sede.colores_sede);
                document.documentElement.style.setProperty('--color-primario', colores.primario);
                document.documentElement.style.setProperty('--color-secundario', colores.secundario);
              } catch (e) {
                console.warn(
                  'colores_sede no es un JSON válido para la sede:',sede.id_sede
                );
              }

              return {
                ...sede,
                colores_sede: colores,
              };
            });
            this.sedesRegistradas = sedesParseadas;
            this.SedesFiltradas = sedesParseadas;
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
  // metodo para filtrar las sedes por nombre
  filtrarSedes(termino: string) {
    const term = termino.toLowerCase();
    this.SedesFiltradas = this.sedesRegistradas.filter((i) =>
      i.nombre_sede.toLowerCase().includes(term)
    );
  }

  ngOnInit(): void {
    this.IdInstitucion = this.route.snapshot.paramMap.get('id')!;
    this.CargarSedes();
  }
}
