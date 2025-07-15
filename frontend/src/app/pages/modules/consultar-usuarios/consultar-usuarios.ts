import { AfterViewInit, Component, ElementRef, OnInit, QueryList, ViewChild, ViewChildren } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, NgForm } from '@angular/forms';
import { AlertasService } from '../../../services/alertas/alertas';
import { SedesService } from '../../../services/sedes/sedes';
import { AplicationService,Sexo,Estado,Roles,Grados,Grupos} from '../../../services/aplication/aplication';
import { InputsWidget } from '../../../shared/inputs/inputs';
import { ImagenesService } from '../../../services/imagenes/imagenes';
import { ButtonSubmit } from '../../../shared/button-submit/button-submit';
import { Buscador } from '../../../shared/buscador/buscador';
import * as XLSX from 'xlsx';
import * as FileSaver from 'file-saver';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import Swal from 'sweetalert2';
import { environment } from '../../../environments/environment.prod';
declare var bootstrap: any;

@Component({
  selector: 'app-consultar-usuarios',
  imports: [CommonModule, FormsModule, InputsWidget, ButtonSubmit,Buscador],
  templateUrl: './consultar-usuarios.html',
  styleUrl: './consultar-usuarios.css',
})
export class ConsultarUsuarios implements OnInit, AfterViewInit {
  @ViewChildren('collapseRef') collapses!: QueryList<ElementRef>;
  collapseInstances: { [id: string]: any } = {};
  usuarios: any[] = [];
  usuariosPaginados: any[] = [];
  sede: any = null;
  sexos: Sexo[] = [];
  estados: Estado[] = [];
  grados: Grados[] = [];
  grupos: Grupos[] = [];
  roles: Roles[] = [];
  DocumentoUser!: string;
  documentosUsuario: any[] = [];
  acudientesUsuario: any[] = [];
  usuarioSeleccionado: any = {
    documentos: '',
  };
  filtroGlobal: string = '';
  paginaActual: number = 1;
  imagenPrevia: string | ArrayBuffer | null = null;
  infoImagen: { nombre: string; tipo: string; peso: number } | null = null;
  acudientes: any[] = [];
  documentos: {
    archivo: File;
    nombrePersonalizado: string;
  }[] = [];

  private _usuariosPorPagina: number = 10;

  bloquearPantalla = false;

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


    

    this.DocumentoUser = usuario.numero_documento;

    this.sedeService.sede$.subscribe({
      next: (sedeData) => {
        this.sede = sedeData;
        /****************grados sede*****************/
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

        if (
          this.sede?.id_sede_encriptado &&
          this.sede?.codigo_institucion_encriptado
        ) {
          this.consultarUsriosSede(
            this.sede.id_sede_encriptado,
            this.sede.codigo_institucion_encriptado
          );
        }
      },
      error: (err) => console.error('Error al obtener la sede:', err),
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
  ngAfterViewInit(): void {}

  get usuariosPorPagina(): number {
    return this._usuariosPorPagina;
  }

  set usuariosPorPagina(valor: number) {
    const nuevoValor = Number(valor);
    this._usuariosPorPagina = nuevoValor > 0 ? nuevoValor : 1;
    this.paginaActual = 1;
    this.actualizarPaginacion();
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
      this.usuarioSeleccionado.imagen = archivo;
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

  toggleCollapse(id: string): void {
    const instance = this.collapseInstances[id];
    if (instance) {
      instance.toggle();
    }
  }

  inicializarColapsables(): void {
    setTimeout(() => {
      this.collapses.forEach((el: ElementRef) => {
        const idAttr = el.nativeElement.id;
        const id = idAttr.replace('collapseAcudiente', '');
        this.collapseInstances[id] = new bootstrap.Collapse(el.nativeElement, {
          toggle: false,
        });
      });
      console.log('✅ Colapsables listos:', this.collapseInstances);
    }, 0);
  }

  consultarUsriosSede(id_sede: string, codigo_institucion: string): void {
    this.miServicio
      .ObtenederUsuariosSede(id_sede, codigo_institucion, this.DocumentoUser)
      .subscribe({
        next: (data) => {
          this.usuarios = Array.isArray(data) ? data : [];
          this.actualizarPaginacion();
        },
        error: (err) => {
          this.usuarios = [];
          console.error('Error al obtener usuarios:', err);
        },
      });
  }

  usuariosFiltrados(): any[] {
    const texto = this.filtroGlobal.toLowerCase();
    const filtrados = this.usuarios.filter((usuario) =>
      Object.values(usuario).some((valor) =>
        valor?.toString().toLowerCase().includes(texto)
      )
    );

    const inicio = (this.paginaActual - 1) * this.usuariosPorPagina;
    const fin = inicio + this.usuariosPorPagina;

    return filtrados.slice(inicio, fin);
  }

  // 👉 Paginación lógica
  get totalPaginas(): number {
    return Math.ceil(this.usuarios.length / this.usuariosPorPagina);
  }

  get totalPaginasArray(): number[] {
    return Array.from({ length: this.totalPaginas }, (_, i) => i + 1);
  }

  irAPagina(pagina: number): void {
    this.paginaActual = pagina;
    this.actualizarPaginacion();
  }

  paginaAnterior(): void {
    if (this.paginaActual > 1) {
      this.paginaActual--;
      this.actualizarPaginacion();
    }
  }

  paginaSiguiente(): void {
    if (this.paginaActual < this.totalPaginas) {
      this.paginaActual++;
      this.actualizarPaginacion();
    }
  }

  actualizarPaginacion(): void {
    const inicio = (this.paginaActual - 1) * this.usuariosPorPagina;
    const fin = inicio + this.usuariosPorPagina;
    this.usuariosPaginados = this.usuarios.slice(inicio, fin);

    // En caso de que cambie la cantidad y la página ya no exista
    if (this.paginaActual > this.totalPaginas) {
      this.paginaActual = this.totalPaginas || 1;
      this.actualizarPaginacion();
    }
  }

  exportarExcel(): void {
    const data = this.usuarios.map((u) => ({
      Documento: u.numero_documento,
      Nombre: u.nombre_usuario,
      Correo: u.correo_usuario,
      Teléfono: u.telefono_usuario,
      Rol: u.rol,
      Grado: u.grado || '-',
      Grupo: u.grupo || '-',
      Estado: u.estado_desecriptado == '1' ? 'Activo' : 'Inactivo',
    }));

    const ws: XLSX.WorkSheet = XLSX.utils.json_to_sheet(data);
    const wb: XLSX.WorkBook = {
      Sheets: { Usuarios: ws },
      SheetNames: ['Usuarios'],
    };
    const excelBuffer: any = XLSX.write(wb, {
      bookType: 'xlsx',
      type: 'array',
    });
    FileSaver.saveAs(
      new Blob([excelBuffer], { type: 'application/octet-stream' }),
      'usuarios.xlsx'
    );
  }

  exportarPDF(): void {
    const doc = new jsPDF();
    const encabezado = [
      [
        'Documento',
        'Nombre',
        'Correo',
        'Teléfono',
        'Rol',
        'Grado',
        'Grupo',
        'Estado',
      ],
    ];
    const filas = this.usuarios.map((u) => [
      u.numero_documento,
      u.nombre_usuario,
      u.correo_usuario,
      u.telefono_usuario,
      u.rol,
      u.grado || '-',
      u.grupo || '-',
      u.estado_desecriptado == '1' ? 'Activo' : 'Inactivo',
    ]);

    autoTable(doc, {
      head: encabezado,
      body: filas,
      styles: { fontSize: 8 },
    });

    doc.save('usuarios.pdf');
  }

  // generar la ruta de la imagenes de la aplicacion dinamicamente
  CargarImagenes(tipo: number, nombreArchivo: string) {
     const datos = {
      id_sede: Number(this.sede?.id_sede),
      codigo_institucion: String(this.sede?.codigo_institucion),
    };

    return this.imagenesService.generarRutaImagen(tipo, nombreArchivo, datos);
  }

  editarUsuario(usuario: any): void {
    this.usuarioSeleccionado = { ...usuario };
    this.ConsultarDocumentosUsarios(this.usuarioSeleccionado.id_matricula); //enviamos los datos a la otra funcion
    this.ConsultarAcudientessUsarios(
      this.usuarioSeleccionado.documento_encription
    );
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

  guardarCambios(): void {
    this.usuarioSeleccionado.id_sede = this.sede?.id_sede_encriptado as string;
    this.usuarioSeleccionado.codigo_institucion = this.sede
      ?.codigo_institucion_encriptado as string;
    this.miServicio.ActualizarUsuario(this.usuarioSeleccionado).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.consultarUsriosSede(
          this.sede.id_sede_encriptado,
          this.sede.codigo_institucion_encriptado
        );
        this.actualizarPaginacion();
        this.infoImagen = null;
      },
      error: (error) => {
        console.error('❌ Error completo del backend:', error);
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error de servidor',
          Texto: 'No se pudo procesar tu solicitud',
          Tipo: 'error',
        });
      },
    });
  }

  ActualizarGradosUsuarios(): void {
    this.usuarioSeleccionado.id_sede = this.sede?.id_sede_encriptado as string;
    this.usuarioSeleccionado.codigo_institucion = this.sede
      ?.codigo_institucion_encriptado as string;
    this.miServicio.ActualizarGradoUsuario(this.usuarioSeleccionado).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.consultarUsriosSede(
          this.sede.id_sede_encriptado,
          this.sede.codigo_institucion_encriptado
        );
        this.actualizarPaginacion();
      },
      error: (error) => {
        console.error('❌ Error completo del backend:', error);
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error de servidor',
          Texto: 'No se pudo procesar tu solicitud',
          Tipo: 'error',
        });
      },
    });
  }

  ActualizarDocumentosUsuarios(): void {
    this.usuarioSeleccionado.documentos = this.documentos;
    this.usuarioSeleccionado.id_sede = this.sede?.id_sede_encriptado as string;
    this.usuarioSeleccionado.codigo_institucion = this.sede
      ?.codigo_institucion_encriptado as string;
    this.miServicio
      .CargarDocumentosUsuarios(this.usuarioSeleccionado)
      .subscribe({
        next: (respuesta: any) => {
          this.alertaService.mostrarAlerta(respuesta);
          this.actualizarPaginacion();
          this.documentos = [];
          this.ConsultarDocumentosUsarios(
            this.usuarioSeleccionado.id_matricula
          );
        },
        error: (error) => {
          console.error('❌ Error completo del backend:', error);
          this.alertaService.mostrarAlerta({
            Alerta: 'simple',
            Titulo: 'Error de servidor',
            Texto: 'No se pudo procesar tu solicitud',
            Tipo: 'error',
          });
        },
      });
  }

  eliminarUsuario(usuario: any): void {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text:
        '¿Estás seguro de  desactivar al usuario: ' +
        usuario.nombre_usuario +
        ' ?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, Desactivar!',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        this.miServicio
          .eliminarUsuario(
            this.sede.id_sede_encriptado,
            this.sede.codigo_institucion_encriptado,
            usuario.documento_encription
          )
          .subscribe({
            next: (respuesta: any) => {
              this.alertaService.mostrarAlerta(respuesta);
              this.consultarUsriosSede(
                this.sede.id_sede_encriptado,
                this.sede.codigo_institucion_encriptado
              );
              this.actualizarPaginacion();
            },
            error: (error) => {
              console.error('❌ Error completo del backend:', error);
              this.alertaService.mostrarAlerta({
                Alerta: 'simple',
                Titulo: 'Error de servidor',
                Texto: 'No se pudo procesar tu solicitud',
                Tipo: 'error',
              });
            },
          });
      }
    });
  }

  ConsultarDocumentosUsarios(idMatriculaEncriptado: string): void {
    this.miServicio.ObtenerDocumentosUsuarios(idMatriculaEncriptado).subscribe({
      next: (respuesta) => {
        this.documentosUsuario = respuesta;
      },
      error: (error) => {
        console.error('❌ Error al obtener documentos:', error);
      },
    });
  }
  mostrar_documentos_usuario(
    DocumentoUser: string,
    NameDocumento: string
  ): string {
    return `${environment.baseUrlImagenes}${this.sede.codigo_institucion}/sedes/${this.sede.id_sede}/documentos/${DocumentoUser}/${NameDocumento}`;
  }

  eliminar_documentos_usuario(
    NombreDocumento: string,
    descripcion: string,
    idMatriculaEncriptado: string,
    DocumentoUser: string
  ): void {
    const datosDocumento = {
      nombre: NombreDocumento,
      descripcion: descripcion,
      id_matricula: idMatriculaEncriptado,
      documento_usuario: DocumentoUser,
      codigo_institucion: this.sede.codigo_institucion,
      id_sede: this.sede.id_sede,
    };

    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text:
        '¿Estás seguro de eliminar el documento: ' +
        datosDocumento.descripcion +
        ' ?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar!',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        this.miServicio.Eliminardocumentousuarios(datosDocumento).subscribe({
          next: (respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.ConsultarDocumentosUsarios(datosDocumento.id_matricula);
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

  RegistrarAcudientes(documento_estudiante_encriptado: string): void {
    const datosAcudientes = {
      acudientes: this.acudientes,
      documento_estudiante: documento_estudiante_encriptado,
      codigo_institucion: this.sede.codigo_institucion,
      id_sede: this.sede.id_sede,
    };
    this.miServicio.AgregarAcudientesUsuarios(datosAcudientes).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.ConsultarAcudientessUsarios(documento_estudiante_encriptado);
        this.acudientes = [];
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

  ConsultarAcudientessUsarios(documento_encription: string): void {
    this.miServicio.ObtenerAcudientessUsuarios(documento_encription).subscribe({
      next: (respuesta) => {
        this.acudientesUsuario = respuesta;

        // Espera a que Angular termine de renderizar el DOM antes de inicializar los collapse
        this.inicializarColapsables();
      },
      error: (error) => {
        console.error('❌ Error al obtener los acudientes:', error);
      },
    });
  }

  encodeDireccion(direccion: string): string {
    return encodeURIComponent(direccion);
  }

  eliminar_acudiente_usuario(data: any): void {
    const datosAcudientes = {
      id_acudiente: data.id_acudiente,
      documento_estudiante: data.documento_estudiante_encriptado,
      codigo_institucion: this.sede.codigo_institucion,
      id_sede: this.sede.id_sede,
    };

    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text: '¿Estás seguro de eliminar al acudiente: ' + data.nombres + ' ?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar!',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        this.miServicio.EliminarAcudientesusuarios(datosAcudientes).subscribe({
          next: (respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.ConsultarAcudientessUsarios(
              data.documento_estudiante_encriptado
            );
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
    });
  }

  actualizarAcudiente(data: any): void {
    const datosAcudientes = {
      id_acudiente: data.id_acudiente,
      nombres: data.nombres,
      telefono: data.telefono,
      correo: data.correo,
      direccion: data.direccion,
      parentesco: data.parentesco,
      sexo: data.sexo,
      codigo_institucion: this.sede.codigo_institucion,
      id_sede: this.sede.id_sede,
    };

    this.miServicio.EditarAcudientesUsuarios(datosAcudientes).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.ConsultarAcudientessUsarios(data.documento_estudiante_encriptado);
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
}
