import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ImagenesService } from '../../services/imagenes/imagenes';
import { InstitucionesServices } from '../../services/instituciones/instituciones';
import { Loader } from '../../shared/loader/loader';
import { ErrorMessage } from '../../shared/error-message/error-message';
import { LoginModel } from '../../models/login';
import { InputsWidget } from '../../shared/inputs/inputs';
import { ButtonSubmit } from '../../shared/button-submit/button-submit';
import { AlertasService } from '../../services/alertas/alertas';

@Component({
  selector: 'app-login',
  imports: [
    CommonModule,
    RouterModule,
    Loader,
    ErrorMessage,
    FormsModule,
    InputsWidget,
    ButtonSubmit,
  ],
  standalone: true,
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login implements OnInit {
  id_sede!: string;
  error: boolean = false;
  sede!: LoginModel;
  cargandodatos: boolean = true;
  imagenes: string[] = [];
  tieneImagenesPortada: boolean = false;
  verContrasena: boolean = false;
  credenciales = {
    usuario: '',
    contrasena: '',
    id_sede: '',
    codigo_institucion: '',
  };

  constructor(
    private route: ActivatedRoute,
    private institucionesServices: InstitucionesServices,
    private imagenesService: ImagenesService,
    private alertaService: AlertasService,
    private router: Router
  ) {}

  // obtenemos la informacion de la sede por el id
  ObtenerInformacionSede() {
    this.cargandodatos = true;
    this.institucionesServices.obtenerSedePorId(this.id_sede).subscribe({
      next: (respuesta: LoginModel) => {
        if (respuesta && respuesta.id_sede) {
          const colores = respuesta.colores_sede;
          this.sede = {
            ...respuesta,
            colores_sede: colores,
          };
          //asignamos los valores (id_sede y codigo_institucion) al modelo de credenciales
          this.credenciales.id_sede = this.sede.id_sede_encriptado as string;
          this.credenciales.codigo_institucion = this.sede
            .codigo_institucion_encriptado as string;
          this.institucionesServices.aplicarTemaSedes(colores);
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
  // obtenemos las imagenes de la portadas de la sede por el id
  ObtenerImagenesPortadas() {
    this.institucionesServices.obtenerImagenesPorSede(this.id_sede).subscribe({
      next: (data: string[]) => {
        this.imagenes = [];
        this.imagenes = data;
        this.tieneImagenesPortada = this.imagenes.length > 0;
      },
      error: (err) => {
        console.error('Error al cargar imágenes:', err);
        this.tieneImagenesPortada = false;
      },
    });
  }

  // iniciar sesion
  iniciarSesion() {
     this.institucionesServices.iniciarSesion(this.credenciales).subscribe({
      next: (respuesta: any) => {
        if (respuesta.datos) {
          localStorage.setItem('usuario', JSON.stringify(respuesta.datos)); 
        }
        this.alertaService.mostrarAlerta(respuesta);
      },
      error: (error) => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error de servidor',
          Texto: 'No se pudo procesar tu solicitud',
          Tipo: 'error',
        });
      },
    });
  }

  // generar la ruta de la imagenes de la aplicacion dinamicamente
  CargarImagenes(tipo: number, nombreArchivo: string) {
    const datos = {
      id_sede: Number(this.sede?.id_sede),
      codigo_institucion: String(this.sede?.codigo_institucion),
    };

    return this.imagenesService.generarRutaImagen(tipo, nombreArchivo, datos);
  }

  ngOnInit(): void {
   const usuarioGuardado = localStorage.getItem('usuario');

    if (usuarioGuardado) {
        this.router.navigate(['/home/inicio']); 
        return; 
    }

    this.id_sede = this.route.snapshot.paramMap.get('id')!;
    this.ObtenerInformacionSede();
    this.ObtenerImagenesPortadas();
  }
}
