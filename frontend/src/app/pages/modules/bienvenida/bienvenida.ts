import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { Buscador } from '../../../shared/buscador/buscador';
import { ImagenesService } from '../../../services/imagenes/imagenes';
import { UsuariosService } from '../../../services/usuarios/usuarios';
import { SedesService } from '../../../services/sedes/sedes';
import { ErrorMessage } from '../../../shared/error-message/error-message';
import { Profesores } from '../../../services/profesores/profesores';
import { combineLatest } from 'rxjs';

interface Tarjeta {
  nombre: string;
  imagen: string;
  ruta: string;
  docente: string | null;
  documento_profesor?: string; // ✅ <-- Agrégalo como opcional
}

@Component({
  selector: 'app-bienvenida',
  standalone: true,
  imports: [Buscador, CommonModule, RouterModule, ErrorMessage],
  templateUrl: './bienvenida.html',
  styleUrl: './bienvenida.css',
})
export class Bienvenida implements OnInit {
  sede: any = null;
  usuario: any = null;

  tarjetasVisibles: Tarjeta[] = [];
  tarjetasOriginales: Tarjeta[] = [];
  constructor(
    private imagenesService: ImagenesService,
    private sedeService: SedesService,
    private usuariosService: UsuariosService,
    private servicioProfesores: Profesores
  ) {}

  tarjetasPorRol: { [rol: number]: Tarjeta[] } = {
    6: [
      {
        nombre: 'Registro de usuarios',
        imagen: 'registrar-usuario.png',
        ruta: '/home/registrar-usuarios',
        docente: '',
      },
      {
        nombre: 'Consulta de usuarios',
        imagen: 'buscar.png',
        ruta: '/home/consultar-usuarios',
        docente: '',
      },
      {
        nombre: 'Configuracion sistema',
        imagen: 'configuracion-del-sistema.png',
        ruta: '/home/configuracion-sistema',
        docente: '',
      },
    ],
    3: [],
  };

  generarTarjetasPorProfesor(asignaciones: any): Tarjeta[] {
    const tarjetas: Tarjeta[] = [];

    if (!asignaciones || !asignaciones.materias) return tarjetas;

    asignaciones.materias.forEach((materia: any) => {
      const idMateria = materia.id_materia;
      const nombreMateria = materia.nombre_materia;
      const imagenMateria = materia.imagen_materia;

      materia.grados.forEach((grado: any) => {
        const idGrado = grado.id_grado;
        const nombreGrado = grado.nombre_grado;

        grado.grupos.forEach((grupo: any) => {
          const idGrupo = grupo.id_grupo;
          const nombreGrupo = grupo.nombre_grupo;

          tarjetas.push({
            nombre: `${nombreMateria} - ${nombreGrado} ${nombreGrupo}`,
            imagen: `${imagenMateria}`, // Usa una imagen por defecto o personalizada
            ruta: `/home/materia/${idMateria}/${idGrado}/${idGrupo}`,
            docente: '',
          });
        });
      });
    });

    return tarjetas;
  }

  generarTarjetasPorEstudiante(asignaciones: any): Tarjeta[] {
    const tarjetas: Tarjeta[] = [];

    if (!asignaciones || !asignaciones.materias) return tarjetas;

    const idGrado = asignaciones.id_grado;
    const nombreGrado = asignaciones.nombre_grado;
    const idGrupo = asignaciones.id_grupo;
    const nombreGrupo = asignaciones.nombre_grupo;

    asignaciones.materias.forEach((materia: any) => {
      const idMateria = materia.id_materia;
      const nombreMateria = materia.nombre_materia;
      const imagenMateria = materia.imagen_materia;

      tarjetas.push({
        nombre: `${nombreMateria} - ${nombreGrado} ${nombreGrupo}`,
        imagen: imagenMateria || 'materiadefault.png',
        ruta: `/home/materia/${idMateria}/${idGrado}/${idGrupo}`,
        docente: materia.docente,
        documento_profesor: materia.documento_docente, // <- asegúrate que exista este campo en el JSON que recibes
      });
    });

    return tarjetas;
  }

  ngOnInit() {
    combineLatest([
      this.sedeService.sede$,
      this.usuariosService.user$,
    ]).subscribe(([sedeData, usuarioData]) => {
      this.sede = sedeData;
      this.usuario = usuarioData;

      if (!this.usuario || !this.sede) return;

      const idRol = this.usuario.id_rol;

      if (idRol == 3) {
        // Profesor: materias dinámicas
        this.extraerMateriasAsigandasProfesorLogueado();
      } else if (idRol == 4) {
        // Estudiante: materias dinámicas
        this.extraerMateriasAsigandasEstudianteLogueado();
      } else {
        // Otros roles: tarjetas fijas predefinidas (admin, director, etc.)
        this.tarjetasVisibles = this.tarjetasPorRol[idRol] || [];
      }
    });
  }

  filtrarTajetas(termino: string) {
  const term = termino.toLowerCase();
  const idRol = this.usuario?.id_rol;

  if (!idRol) return;

  // ✅ Ahora incluye tanto profesores como estudiantes
  const tarjetasBase =
    (idRol == 3 || idRol == 4) ? this.tarjetasOriginales : this.tarjetasPorRol[idRol] || [];

  if (!termino.trim()) {
    this.tarjetasVisibles = [...tarjetasBase];
  } else {
    this.tarjetasVisibles = tarjetasBase.filter((tarjeta: Tarjeta) =>
      tarjeta.nombre.toLowerCase().includes(term)
    );
  }
}


  CargarImagenes(
    tipo: number,
    nombreArchivo: string,
    documento?: string
  ): string {
    const datos = {
      id_sede: Number(this.sede?.id_sede),
      codigo_institucion: String(this.sede?.codigo_institucion),
    };

    // Verificamos si el usuario es profesor (id_rol === 3)
    let documentoFinal = documento;

    if (this.usuario?.id_rol == 3 && !documentoFinal) {
      documentoFinal = this.usuario.documento;
    }

    // Si el tipo es 8 y no hay documento, usamos tipo 1 (imagen por defecto)
    const tipoFinal = tipo == 8 && !documentoFinal ? 1 : tipo;

    return this.imagenesService.generarRutaImagen(
      tipoFinal,
      nombreArchivo,
      datos,
      documentoFinal
    );
  }

  extraerMateriasAsigandasProfesorLogueado(): void {
    if (!this.sede || !this.usuario) return;
    if (this.usuario?.id_rol != 3) return;
    this.servicioProfesores
      .extraerMateriasAsigandasProfesorLogueado(
        this.sede?.id_sede_encriptado,
        this.sede?.codigo_institucion_encriptado,
        this.usuario?.documento
      )
      .subscribe({
        next: (data) => {
          this.tarjetasOriginales = this.generarTarjetasPorProfesor(data);
          this.tarjetasVisibles = [...this.tarjetasOriginales];
        },
        error: (error) => {
          console.error('Error en backend:', error);
        },
      });
  }

  extraerMateriasAsigandasEstudianteLogueado(): void {
    if (!this.sede || !this.usuario) return;
    if (this.usuario?.id_rol !== 4) return;

    this.servicioProfesores
      .extraerMateriasAsigandasEstudianteLogueado(
        this.sede?.id_sede_encriptado,
        this.sede?.codigo_institucion_encriptado,
        this.usuario?.documento
      )
      .subscribe({
        next: (data) => {
          this.tarjetasOriginales = this.generarTarjetasPorEstudiante(data);
          this.tarjetasVisibles = [...this.tarjetasOriginales];
        },
        error: (error) => {
          console.error('Error en backend:', error);
        },
      });
  }
}
