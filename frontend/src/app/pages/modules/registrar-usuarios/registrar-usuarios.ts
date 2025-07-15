import { CommonModule } from '@angular/common';
import { Component, OnInit, ViewChild } from '@angular/core';
import { FormsModule, NgForm } from '@angular/forms'
import { AlertasService } from '../../../services/alertas/alertas';
import { SedesService } from '../../../services/sedes/sedes';
import { AplicationService,Sexo,Estado,Roles,Grados,Grupos} from '../../../services/aplication/aplication';
import { InformacionRegistroModel } from '../../../models/usuarios';
import { InputsWidget } from '../../../shared/inputs/inputs';
import { ImagenesService } from '../../../services/imagenes/imagenes';
import { ButtonSubmit } from '../../../shared/button-submit/button-submit';
import Swal from 'sweetalert2';


@Component({
  selector: 'app-registrar-usuarios',
  imports: [CommonModule, FormsModule, InputsWidget,ButtonSubmit],
  templateUrl: './registrar-usuarios.html',
  styleUrl: './registrar-usuarios.css',
})
export class RegistrarUsuarios implements OnInit {
  imagenPrevia: string | ArrayBuffer | null = null;
  infoImagen: { nombre: string; tipo: string; peso: number } | null = null;
  sede: any = null;
  sexos: Sexo[] = [];
  estados: Estado[] = [];
  roles: Roles[] = [];
  grados: Grados[] = [];
  grupos: Grupos[] = [];
  mostrarEstudiante: boolean = false;
  acudientes: any[] = [];
  documentos: {
    archivo: File;
    nombrePersonalizado: string;
  }[] = [];
    bloquearPantalla = false;
  credenciales: InformacionRegistroModel = {
    documento: '',
    nombre: '',
    correo: '',
    telefono: '',
    sexo: '',
    contrasena: '',
    confirmContrasena: '',
    estado: '',
    rol: '',
    grado: '',
    grupo: '',
    acudientes: [],
    documentos: [],
    imagen: null,
    id_sede: '',
    codigo_institucion: '',
  };

  
  constructor(
    private alertaService: AlertasService,
    private sedeService: SedesService,
    private miServicio: AplicationService,
    private imagenesService: ImagenesService
  ) {}

  ngOnInit(): void {

     if (typeof window !== 'undefined') {
          const usuario = JSON.parse(localStorage.getItem('usuario') || '{}');
        }
    
        const usuarioString = localStorage.getItem('usuario');
        if (!usuarioString) {
          window.location.href = '/';
          return;
        }
    
        const usuario = JSON.parse(usuarioString);
    
    
        



    this.sedeService.sede$.subscribe({
      next: (sedeData) => {
        this.sede = sedeData;

        this.miServicio
          .ObtenedorGradosAplication(
            this.sede?.id_sede_encriptado,
            this.sede?.codigo_institucion_encriptado
          )
          .subscribe({
            next: (grados) => {
              this.grados = grados;
            },
            error: (err) => {
              console.error('Error al cargar grados:', err);
            },
          });
      },
    });
    /****************sexos*****************/
    this.miServicio.ObtenedorSexosAplication().subscribe({
      next: (data) => {
        this.sexos = data;
      },
      error: (err) => {
        console.error('Error al cargar sexos:', err);
      },
    });

    /****************estados*****************/
    this.miServicio.ObtenedorEstadoAplication().subscribe({
      next: (data) => {
        this.estados = data;
      },
      error: (err) => {
        console.error('Error al cargar estados:', err);
      },
    });

    /****************estados*****************/
    this.miServicio.ObtenedorRolesAplication().subscribe({
      next: (data) => {
        this.roles = data;
      },
      error: (err) => {
        console.error('Error al cargar roles:', err);
      },
    });
  }

  onDragOver(event: DragEvent): void {
    event.preventDefault();
    event.stopPropagation();
  }

  onDrop(event: DragEvent): void {
    event.preventDefault();
    event.stopPropagation();
    const archivo = event.dataTransfer?.files[0];
    if (archivo) this.procesarImagen(archivo);
  }

  onFileSelected(event: any): void {
    const archivo = event.target.files[0];
    if (archivo) {
      this.credenciales.imagen = archivo;
      this.procesarImagen(archivo);
    }
  }

  onFileSelectArchivo(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files) {
      for (let i = 0; i < input.files.length; i++) {
        const archivoNuevo = input.files[i];

        const yaExiste = this.documentos.some(
          (doc) => doc.archivo.name === archivoNuevo.name
        );
        if (!yaExiste) {
          this.documentos.push({
            archivo: archivoNuevo,
            nombrePersonalizado: '',
          });
        }
      }

      // ✅ Esto es CLAVE para poder volver a subir el mismo archivo después
      input.value = '';
    }
  }

  onDropArchivo(event: DragEvent) {
    event.preventDefault();
    if (event.dataTransfer?.files) {
      for (let i = 0; i < event.dataTransfer.files.length; i++) {
        const archivoNuevo = event.dataTransfer.files[i];

        const yaExiste = this.documentos.some(
          (doc) => doc.archivo.name === archivoNuevo.name
        );
        if (!yaExiste) {
          this.documentos.push({
            archivo: archivoNuevo,
            nombrePersonalizado: '',
          });
        }
      }
    }
  }

  onDragOverArchivo(event: DragEvent) {
    event.preventDefault();
  }

  eliminarDocumento(index: number) {
    this.documentos.splice(index, 1);
  }

  procesarImagen(archivo: File): void {
    const reader = new FileReader();
    reader.onload = () => {
      this.imagenPrevia = reader.result;
      this.infoImagen = {
        nombre: archivo.name,
        tipo: archivo.type,
        peso: Math.round(archivo.size / 1024),
      };
    };
    reader.readAsDataURL(archivo);
  }

  // generar la ruta de la imagenes de la aplicacion dinamicamente
  CargarImagenes(tipo: number, nombreArchivo: string) {
    return this.imagenesService.generarRutaImagen(tipo, nombreArchivo);
  }
  // generar correo usuarios automaticamente
  generarCorreo(): void {
    const nombreCompleto = this.credenciales.nombre.trim().toLowerCase();
    const documento = this.credenciales.documento.trim();

    if (!nombreCompleto || !documento || documento.length < 2) {
      this.credenciales.correo = '';
      this.credenciales.contrasena = '';
      this.credenciales.confirmContrasena = '';
      return;
    }

    const partes = nombreCompleto.split(' ').filter((p) => p !== '');
    const primerNombre = partes[0] || '';
    const primerApellido = partes[2] || partes[1] || ''; // evita errores si hay un solo nombre o apellido
    const ultimosDosDoc = documento.slice(-2);

    // Generar contraseña igual al documento automáticamente
    this.credenciales.contrasena = documento;
    this.credenciales.confirmContrasena = documento;

    // Generar correo solo si hay nombre y apellido
    if (primerNombre && primerApellido) {
      this.credenciales.correo = `${primerNombre}.${primerApellido}${ultimosDosDoc}@gmail.com`;
    } else {
      this.credenciales.correo = '';
    }
  }


  onRolSeleccionado(event: Event): void {
    const selectElement = event.target as HTMLSelectElement;
    const idRolSeleccionado = selectElement.value;
    const rolSeleccionado = this.roles.find(
      (r) => r.id_rol === idRolSeleccionado
    );
    this.mostrarEstudiante =
      rolSeleccionado?.descripcion.toLowerCase() === 'estudiante';
  }

  onGradoSeleccionado(event: Event): void {
    const selectElement = event.target as HTMLSelectElement;
    const idgradoSeleccionado = selectElement.value;
    this.miServicio
      .ObtenedorGruposGradosAplication(idgradoSeleccionado)
      .subscribe({
        next: (grupos) => {
          this.grupos = grupos;
        },
        error: (err) => {
          console.error('Error al cargar los grupos:', err);
        },
      });
  }

  crearAcudienteVacio() {
    return {
      nombres: '',
      correo: '',
      telefono: '',
      direccion: '',
      numeroDocumento: '',
      sexo: '',
      contrasena: '',
      parentesco: '',
    };
  }


  agregarAcudiente() {
    this.acudientes = [...this.acudientes, this.crearAcudienteVacio()];
  }

  trackByIndex(index: number, item: any): number {
    return index;
  }

  eliminarAcudiente(index: number) {
    this.acudientes.splice(index, 1);
  }

   validarCamposObligatorios(): boolean {
    const camposObligatorios = [
      { key: 'documento', label: 'Documento' },
      { key: 'nombre', label: 'Nombre' },
      { key: 'correo', label: 'Correo' },
      { key: 'telefono', label: 'Teléfono' },
      { key: 'sexo', label: 'Sexo' },
      { key: 'contrasena', label: 'Contraseña' },
      { key: 'confirmContrasena', label: 'Confirmación de contraseña' },
      { key: 'estado', label: 'Estado' },
      { key: 'rol', label: 'Rol' },
      { key: 'id_sede', label: 'Sede' },
      { key: 'codigo_institucion', label: 'Institución' },
    ] as const;

    for (const campo of camposObligatorios) {
      const valor =
        this.credenciales[campo.key as keyof InformacionRegistroModel];
      if (!valor || valor.toString().trim() === '') {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Campo vacío',
          Texto: `Debes completar el campo: ${campo.label}`,
          Tipo: 'warning',
        });
        return false;
      }
    }

    if (this.credenciales.contrasena !== this.credenciales.confirmContrasena) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Contraseñas no coinciden',
        Texto: 'La contraseña y su confirmación no son iguales.',
        Tipo: 'warning',
      });
      return false;
    }

    return true;
  }

  RegistrarUsuarios() {
    this.credenciales.acudientes = this.acudientes;
    this.credenciales.documentos = this.documentos;
    this.credenciales.id_sede = this.sede?.id_sede_encriptado as string;
    this.credenciales.codigo_institucion = this.sede
      ?.codigo_institucion_encriptado as string;
    if (!this.validarCamposObligatorios()) return;
     this.miServicio.RegistraUsuarios(this.credenciales).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        if(respuesta.Alerta === 'recargar'){
           this.credenciales = {
            documento: '',
            nombre: '',
            correo: '',
            telefono: '',
            sexo: '',
            contrasena: '',
            confirmContrasena: '',
            estado: '',
            rol: '',
            grado: '',
            grupo: '',
            acudientes: [],
            documentos: [],
            imagen: null,
            id_sede: '',
            codigo_institucion: '',
          };
          this.documentos = [];
          this.infoImagen = null;
          this.imagenPrevia = null; 
        }
      },error: (error) => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error de servidor',
          Texto: 'No se pudo procesar tu solicitud',
          Tipo: 'error',
        });
      },
     });
  }
}
