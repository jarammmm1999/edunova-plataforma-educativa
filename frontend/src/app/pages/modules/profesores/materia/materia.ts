import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ImagenesService } from '../../../../services/imagenes/imagenes';
import { AlertasService } from '../../../../services/alertas/alertas';
import { combineLatest, interval, filter as rxFilter, Subscription } from 'rxjs';
import { UsuariosService } from '../../../../services/usuarios/usuarios';
import { Profesores } from '../../../../services/profesores/profesores';
import { SedesService } from '../../../../services/sedes/sedes';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { InputsWidget } from '../../../../shared/inputs/inputs';
import { ButtonSubmit } from '../../../../shared/button-submit/button-submit';
import { MateriaService } from '../../../../services/materia/materia';
import { DragDropModule } from '@angular/cdk/drag-drop';
import {CdkDragDrop,moveItemInArray,CdkDrag,CdkDropList,} from '@angular/cdk/drag-drop';
import {DomSanitizer,SafeHtml, SafeResourceUrl,} from '@angular/platform-browser';
import Swal from 'sweetalert2';
import { EditorText } from '../../../../shared/editor-text/editor-text';
import { ErrorMessage } from '../../../../shared/error-message/error-message';
import { Estudiantes } from '../../../../services/estudiantes/estudiantes';
import { Comentarios } from '../../../../shared/comentarios/comentarios';
import { SocketService } from '../../../../services/socket/socket';
declare var bootstrap: any;
@Component({
  selector: 'app-materia',
  imports: [
    CommonModule,
    FormsModule,
    InputsWidget,
    ButtonSubmit,
    DragDropModule,
    CdkDrag,
    CdkDropList,
    EditorText,
    ErrorMessage,
    Comentarios,
  ],
  templateUrl: './materia.html',
  styleUrl: './materia.css',
})
export class Materia implements OnInit {
  id_materia!: string;
  id_grado!: string;
  id_grupo!: string;
  imagenPortadaMateria!: string;
  usuario: any = null;
  sede: any = null;
  titulo: string = '';
  nombreMateria: string = '';
  nombreGrado: string = '';
  nombreGrupo: string = '';
  temasEducativos: any[] = [];
  contenidoTemaSeleccionado: any[] = [];
  nuevoTema = {
    titulo_tema: '',
    descripcion: '',
  };
  nuevoTexto = {
    titulo: '',
    contenido: '',
  };
  archivosAgrupados: any[] = [];
  mostrarArchivos: boolean = false;
  temaSeleccionado: any = null;
  indiceActivo: number = 0;
  archivoImagen: File | null = null;
  imagenPrevisualizada: string | null = null;
  imagenDesdeUrl: string = '';
  contenidoEditando: any = {};
  contenidoEditandoTareas: any = {};
  listaArchivos: any;
  contenidoForo: string = '';
  imgPreview: string | ArrayBuffer | null = null;
  documento_profesor: string = '';
  mostrarEditor = true;

  constructor(
    private route: ActivatedRoute,
    private imagenesService: ImagenesService,
    private alertaService: AlertasService,
    private usuariosService: UsuariosService,
    private servicioProfesores: Profesores,
    private servicioEstudiantes: Estudiantes,
    private sedeService: SedesService,
    private materiaService: MateriaService,
    private sanitizer: DomSanitizer,
    private socketService: SocketService
  ) {}

  ngOnInit(): void {
    combineLatest([
      this.usuariosService.user$,
      this.sedeService.sede$,
      this.route.paramMap,
    ])
      .pipe(
        rxFilter(([usuario, sede, params]) => !!usuario && !!sede && !!params)
      )
      .subscribe(([usuario, sede, params]) => {
        this.usuario = usuario;
        this.sede = sede;

        this.id_materia = params.get('idMateria') || '';
        this.id_grado = params.get('idGrado') || '';
        this.id_grupo = params.get('idGrupo') || '';

        if (this.usuario.id_rol == 3) {
          this.consultarInfromacionMaterias(
            this.id_materia,
            this.id_grado,
            this.id_grupo
          );
          this.ConsultarTemasEducativos();
          this.consultar_contenido_tema_seleccionado();
        } else if (this.usuario.id_rol == 4) {
          this.extraerInformacionMateriaSelecionadaEstudiantes(
            this.id_materia,
            this.id_grado,
            this.id_grupo
          );
          this.ConsultarTemasEducativosEstudiantes();
        }

        this.socketService.recibirEvento((mensaje) => {
          const esMismaMateria =
            mensaje.id_materia === this.id_materia &&
            mensaje.id_grado === this.id_grado &&
            mensaje.id_grupo === this.id_grupo;

          const tiposActualizarDiscusion = new Set([
            'discusion_eliminada',
            'comentario_creado',
            'discusion_creada',
            'NuevoForoRegistrado',
            'discusion_actualizada',
          ]);

          const tiposActualizarTemas = new Set([
            'NuevoTemaRegistrado',
            'temas_reordenados',
            'contenido_reordenados',
            'NombreTemaActualizado',
            'NuevoTextoRegistrado',
            'TextoActualizado',
            'TextoEliminado',
            'NuevaImagenRegistrada',
            'ImagenEliminada',
            'ArchivosRegistrados',
            'ArchivoEliminado',
            'NuevaTareaRegistrada',
            'TareaEditada',
            'TareaEliminada',
            'NuevaNotaRegistrada',
            'TallerEliminado',
            'VideoEliminado',
            'NuevoVideoRegistrado',
            'ActualizarEstadoTema',
          ]);

          if (esMismaMateria && tiposActualizarDiscusion.has(mensaje.tipo)) {
            this.ObtenerDiscucionesFrosos();
          }

          if (tiposActualizarTemas.has(mensaje.tipo)) {
            this.ConsultarTemasEducativos();
            this.ConsultarTemasEducativosEstudiantes();
          }

          if (
            mensaje.tipo === 'TareaEditada' &&
            mensaje.tipo === 'TareaEntregada' &&
            mensaje.tipo === 'TareaEntregadaEliminada' &&
            esMismaMateria
          ) {
            this.consultarInformaTareasEducativas(mensaje.id_contenido);
            this.consultarEntregasTareasEstudiantes(mensaje.id_tarea);
            this.consultar_contenido_tema_seleccionado();
          }

          if (
            mensaje.tipo === 'ArchivoTallerEliminado' ||
            (mensaje.tipo === 'TallerEditado' && esMismaMateria)
          ) {
            this.consultarInformaTalleresEducativas(mensaje.id_contenido);
            this.consultar_contenido_tema_seleccionado();
          }
        });
      });
  }

  ngOnDestroy(): void {
    this.timerSub?.unsubscribe();
  }

  /******************************************estudiantes**************************************************** */

  //consultar informacion de la materia seleccionada

  extraerInformacionMateriaSelecionadaEstudiantes(
    idMateria: string,
    idGrado: string,
    idGrupo: string
  ) {
    const datos = {
      id_materia: idMateria,
      id_grado: idGrado,
      id_grupo: idGrupo,
      documento_estudiantes: this.usuario?.documento,
      codigo_institucion: this.sede?.codigo_institucion_encriptado,
      id_sede: this.sede?.id_sede_encriptado,
    };

    this.servicioEstudiantes
      .extraerInformacionMateriaSelecionadaEstudiantes(datos)
      .subscribe({
        next: (data: any) => {
          this.nombreMateria = data.nombre_materia || '';
          this.nombreGrado = data.nombre_grado || '';
          this.nombreGrupo = data.nombre_grupo || '';
          // Opcional: también podrías actualizar el título aquí
          this.titulo = `${this.nombreMateria} - ${this.nombreGrado} ${this.nombreGrupo}`;
        },
        error: (error) => {
          console.error('[Error Materia]:', error);
        },
      });
  }

  ConsultarTemasEducativosEstudiantes() {
    const datos_tema = {
      id_materia: this.id_materia,
      id_grado: this.id_grado,
      id_grupo: this.id_grupo,
      documento_estudiante: this.usuario?.documento,
      codigo_institucion: this.sede?.codigo_institucion_encriptado,
      id_sede: this.sede?.id_sede_encriptado,
    };

    this.servicioEstudiantes
      .ConsultarTemasEducativosEstudiantes(datos_tema)
      .subscribe({
        next: (respuesta: any) => {
          // Ordenar según el campo 'orden'
          this.temasEducativos = respuesta.sort(
            (a: any, b: any) => a.orden - b.orden
          );

          this.indiceActivo = 0;
          this.temaSeleccionado = this.temasEducativos[0] || null;

          if (this.temaSeleccionado) {
            this.consultar_contenido_tema_seleccionado();
          }
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

  /******************************************profesores**************************************************** */

  actualizarAvatar(event: Event): void {
    const archivo = (event.target as HTMLInputElement).files?.[0];
    if (!archivo) return;

    const lector = new FileReader();
    lector.onload = () => {
      this.imgPreview = lector.result;
    };
    lector.readAsDataURL(archivo);

    const formData = new FormData();
    formData.append('imagen', archivo);
    formData.append('documento', this.usuario.documento);
    formData.append(
      'codigo_institucion',
      this.sede.codigo_institucion_encriptado
    );
    formData.append('id_sede', this.sede.id_sede_encriptado);
    formData.append('id_materia', this.id_materia);
    formData.append('id_grado', this.id_grado);
    formData.append('id_grupo', this.id_grupo);

    this.materiaService.SubirImagenesMaterias(formData).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.consultarInfromacionMaterias(
          this.id_materia,
          this.id_grado,
          this.id_grupo
        );
      },
      error: (err) => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error de servidor',
          Texto: 'No se pudo procesar tu solicitud',
          Tipo: 'error',
        });
      },
    });
  }
  // Consultar la información de la materia
  consultarInfromacionMaterias(
    idMateria: string,
    idGrado: string,
    idGrupo: string
  ) {
    const datos = {
      id_materia: idMateria,
      id_grado: idGrado,
      id_grupo: idGrupo,
      documento_profesor: this.usuario?.documento,
      codigo_institucion: this.sede?.codigo_institucion_encriptado,
      id_sede: this.sede?.id_sede_encriptado,
    };

    this.servicioProfesores
      .extraerInformacionMateriaSelecionada(datos)
      .subscribe({
        next: (data: any) => {
          this.nombreMateria = data.nombre_materia || '';
          this.nombreGrado = data.nombre_grado || '';
          this.nombreGrupo = data.nombre_grupo || '';
          this.imagenPortadaMateria = data.imagen_materia || '';

          // Opcional: también podrías actualizar el título aquí
          this.titulo = `${this.nombreMateria} - ${this.nombreGrado} ${this.nombreGrupo}`;
        },
        error: (error) => {
          console.error('[Error Materia]:', error);
        },
      });
  }
  // registrar temas educativos
  guardarTema() {
    if (!this.nuevoTema.titulo_tema.trim()) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'campos vacios',
        Texto: 'por favor ingrese un titulo de tema para continuar',
        Tipo: 'warning',
      });
      return;
    }

    const datos_tema = {
      titulo_tema: this.nuevoTema.titulo_tema,
      descripcion_tema: this.nuevoTema.descripcion,
      id_materia: this.id_materia,
      id_grado: this.id_grado,
      id_grupo: this.id_grupo,
      documento_profesor: this.usuario?.documento,
      codigo_institucion: this.sede?.codigo_institucion_encriptado,
      id_sede: this.sede?.id_sede_encriptado,
    };
    this.materiaService.CreartemasEducativos(datos_tema).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.ConsultarTemasEducativos();
        this.nuevoTema.titulo_tema = '';
        this.nuevoTema.descripcion = '';

        // ✅ Notificar a los demás usuarios que hay un nuevo tema
        this.socketService.enviarEvento({
          tipo: 'NuevoTemaRegistrado',
          id_tema: respuesta.id_tema_insertado,
        });
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
  //consultar temas educativos
  ConsultarTemasEducativos(idTemaAFijar?: number) {
    const datos_tema = {
      id_materia: this.id_materia,
      id_grado: this.id_grado,
      id_grupo: this.id_grupo,
      documento_profesor: this.usuario?.documento,
      codigo_institucion: this.sede?.codigo_institucion_encriptado,
      id_sede: this.sede?.id_sede_encriptado,
    };

    this.materiaService.ConsultarTemasEducativos(datos_tema).subscribe({
      next: (respuesta: any) => {
        this.temasEducativos = respuesta.sort(
          (a: any, b: any) => a.orden - b.orden
        );

        if (idTemaAFijar) {
          const nuevoIndice = this.temasEducativos.findIndex(
            (t) => t.id_tema === idTemaAFijar
          );

          if (nuevoIndice !== -1) {
            this.indiceActivo = nuevoIndice;
            this.temaSeleccionado = this.temasEducativos[nuevoIndice];
          } else {
            // Si no lo encuentra, seleccionar el primero
            this.indiceActivo = 0;
            this.temaSeleccionado = this.temasEducativos[0] || null;
          }
        } else {
          // Comportamiento por defecto
          this.indiceActivo = 0;
          this.temaSeleccionado = this.temasEducativos[0] || null;
        }

        if (this.temaSeleccionado) {
          this.consultar_contenido_tema_seleccionado();
        }
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error de servidor',
          Texto: 'No se pudo procesar tu solicitud',
          Tipo: 'error',
        });
      },
    });
  }

  // seleccionar tema educativo
  seleccionarTema(index: number) {
    this.indiceActivo = index;
    this.temaSeleccionado = this.temasEducativos[index];

    if (
      this.usuario?.id_rol === 3 &&
      (this.temaSeleccionado.estado === 'activo' ||
        this.temaSeleccionado.estado === 'inactivo')
    ) {
      // Profesor puede ver temas activos o inactivos
      this.consultar_contenido_tema_seleccionado();
    } else if (
      this.usuario?.id_rol === 4 &&
      this.temaSeleccionado.estado === 'activo'
    ) {
      // Estudiante solo puede ver temas activos
      this.consultar_contenido_tema_seleccionado();
    } else if (
      this.usuario?.id_rol === 4 &&
      this.temaSeleccionado.estado === 'inactivo'
    ) {
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.onmouseenter = Swal.stopTimer;
          toast.onmouseleave = Swal.resumeTimer;
        },
      });

      Toast.fire({
        icon: 'error',
        title:
          'No tienes permiso para ver este tema ya que se encuentra bloqueado.',
      });
    }
  }

  moverTema(event: CdkDragDrop<any[]>) {
    // 🧩 Guardar el ID del tema actualmente seleccionado
    const idTemaSeleccionado = this.temaSeleccionado?.id_tema;

    // 🔄 Reordenar visualmente los temas
    moveItemInArray(
      this.temasEducativos,
      event.previousIndex,
      event.currentIndex
    );

    // 🧠 Buscar nueva posición del tema seleccionado
    const nuevoIndice = this.temasEducativos.findIndex(
      (tema) => tema.id_tema === idTemaSeleccionado
    );

    if (nuevoIndice !== -1) {
      this.indiceActivo = nuevoIndice;
      this.temaSeleccionado = this.temasEducativos[nuevoIndice];
    } else {
      // Seguridad por si no lo encuentra
      this.indiceActivo = 0;
      this.temaSeleccionado = this.temasEducativos[0];
    }

    // 📦 Preparar nuevo orden
    const nuevosOrdenes = this.temasEducativos.map((tema, index) => ({
      id_tema: tema.id_tema,
      nuevo_orden: index + 1,
    }));

    // 🚀 Enviar al backend
    this.materiaService.actualizarOrdenTemas(nuevosOrdenes).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.socketService.enviarEvento({
          tipo: 'temas_reordenados',
          timestamp: Date.now(),
        });
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error al guardar orden',
          Texto: 'No se pudo actualizar el orden en la base de datos.',
          Tipo: 'error',
        });
      },
    });
  }

  moverContenido(event: CdkDragDrop<any[]>) {
    moveItemInArray(
      this.contenidoTemaSeleccionado,
      event.previousIndex,
      event.currentIndex
    );

    // Puedes enviar el nuevo orden al backend si deseas
    const nuevoOrden = this.contenidoTemaSeleccionado.map(
      (contenido, index) => ({
        id_contenido: contenido.id_contenido,
        nuevo_orden: index + 1,
      })
    );

    this.materiaService.actualizarContenidoTemas(nuevoOrden).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        // 🛰️ Notificar a otros clientes por WebSocket
        this.socketService.enviarEvento({
          tipo: 'contenido_reordenados',
          timestamp: Date.now(),
        });
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error al guardar orden',
          Texto: 'No se pudo actualizar el orden en la base de datos',
          Tipo: 'error',
        });
      },
    });
  }

  getIconoArchivo(nombre: string): string {
    const ext = nombre.split('.').pop()?.toLowerCase();
    switch (ext) {
      case 'pdf':
        return 'fa-solid fa-file-pdf text-danger';
      case 'doc':
      case 'docx':
        return 'fa-solid fa-file-word text-primary';
      case 'xls':
      case 'xlsx':
        return 'fa-solid fa-file-excel text-success';
      case 'ppt':
      case 'pptx':
        return 'fa-solid fa-file-powerpoint text-warning';
      case 'zip':
      case 'rar':
        return 'fa-solid fa-file-zipper text-secondary';
      case 'jpg':
      case 'jpeg':
      case 'png':
        return 'fa-solid fa-file-image text-info';
      default:
        return 'fa-solid fa-file-lines text-muted';
    }
  }

  consultar_contenido_tema_seleccionado() {
    // Limpiar contenido actual antes de cargar
    this.contenidoTemaSeleccionado = [];
    this.archivosAgrupados = [];

    // Validar que haya un tema seleccionado con id válido
    if (!this.temaSeleccionado || !this.temaSeleccionado.id_tema) {
      console.warn('[Advertencia] No hay tema seleccionado válido');
      return;
    }

    const datos = {
      documento_profesor: this.usuario?.documento,
      id_tema: this.temaSeleccionado.id_tema,
    };

    this.materiaService.consultar_contenido_tema_seleccionado(datos).subscribe({
      next: (respuesta: any) => {
        this.documento_profesor = respuesta[0].creado_por;
        // 🔵 Primero separamos los archivos
        this.archivosAgrupados = respuesta.filter(
          (contenido: any) =>
            contenido.id_tema === this.temaSeleccionado.id_tema &&
            contenido.tipo_contenido === 'archivo'
        );

        // 🟣 Luego los demás contenidos que NO son archivos
        this.contenidoTemaSeleccionado = respuesta.filter(
          (contenido: any) =>
            contenido.id_tema === this.temaSeleccionado.id_tema &&
            contenido.tipo_contenido !== 'archivo'
        );
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error al consultar contenido',
          Texto: 'No se pudo consultar el contenido del tema seleccionado',
          Tipo: 'error',
        });
      },
    });
  }

  /*************************** modales para agregar contenido educativo******************************************** */

  abrirModalTexto() {
    this.nuevoTexto = { titulo: '', contenido: '' };
    const modal = new bootstrap.Modal(
      document.getElementById('modalAgregarTexto')!
    );
    modal.show();
  }

  abrirModalEditarTexto(contenido: any) {
    // Crear una copia del objeto para no afectar directamente el original
    this.contenidoEditando = { ...contenido };

    // Mostrar el modal
    const modal = new bootstrap.Modal(
      document.getElementById('modalEditarTexto')
    );
    modal.show();
  }

  abrirModalImagen() {
    this.nuevoTexto = { titulo: '', contenido: '' };
    const modal = new bootstrap.Modal(
      document.getElementById('modalAgregarImagen')!
    );
    modal.show();
  }

  abrirModalArchivo() {
    this.nuevoTexto = { titulo: '', contenido: '' };
    const modal = new bootstrap.Modal(
      document.getElementById('modalAgregararchivos')!
    );
    modal.show();
  }

  abrirModalVideos() {
    this.nuevoTexto = { titulo: '', contenido: '' };
    const modal = new bootstrap.Modal(
      document.getElementById('modalAgregarVideos')!
    );
    modal.show();
  }

  abrirModalforos() {
    this.nuevoTexto = { titulo: '', contenido: '' };
    const modal = new bootstrap.Modal(
      document.getElementById('modalAgregarforo')!
    );
    modal.show();
  }

  // generar la ruta de la imagenes de la aplicacion dinamicamente
  CargarImagenes(tipo: number, nombreArchivo: string) {
    const datos = {
      id_sede: Number(this.sede?.id_sede),
      codigo_institucion: String(this.sede?.codigo_institucion),
    };

    return this.imagenesService.generarRutaImagen(
      tipo,
      nombreArchivo,
      datos,
      this.documento_profesor,
      this.temaSeleccionado.id_tema
    );
  }

  /*************************** temas educativos******************************************** */

  ActualizarNombreTema() {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -4px;">¿Deseas actualizar este Tema? </h1>',
      input: 'text',
      icon: 'question',
      inputValue: this.temaSeleccionado.titulo_tema || '',
      inputAttributes: {
        autocapitalize: 'off',
      },
      showCancelButton: true,
      confirmButtonText: '!Si, actualizar!',
      customClass: {
        confirmButton: 'btn-confirmar',
        title: 'titulo-alerta',
      },
      showLoaderOnConfirm: true,
      preConfirm: (nuevoNombre) => {
        if (!nuevoNombre.trim()) {
          Swal.showValidationMessage(
            'El nombre del tema  no puede estar vacío'
          );
          return false;
        }

        const datostemas = {
          id_tema: this.temaSeleccionado.id_tema,
          codigo_institucion: this.sede?.codigo_institucion_encriptado,
          id_sede: this.sede?.id_sede_encriptado,
          nombre_tema: nuevoNombre.trim(),
        };

        return this.materiaService
          .ActualizarNombreTema(datostemas)
          .toPromise()
          .then((respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.ConsultarTemasEducativos();

            // 🔄 Enviar mensaje a todos los demás clientes vía WebSocket
            this.socketService.enviarEvento({
              tipo: 'NombreTemaActualizado',
              id_tema: respuesta.id_tema,
            });
          })
          .catch(() => {
            Swal.showValidationMessage('Error al actualizar Tema');
          });
      },
      allowOutsideClick: () => !Swal.isLoading(),
    });
  }

  Activar_desactivar_temas_educativos() {
    const idTemaActual = this.temaSeleccionado.id_tema;
    this.temaSeleccionado.estado =
      this.temaSeleccionado.estado === 'activo' ? 'inactivo' : 'activo';
    const datostemas = {
      id_tema: this.temaSeleccionado.id_tema,
      codigo_institucion: this.sede?.codigo_institucion_encriptado,
      id_sede: this.sede?.id_sede_encriptado,
      estado: this.temaSeleccionado.estado,
    };
    this.materiaService.ActualizarEstadoTema(datostemas).subscribe(
      (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.ConsultarTemasEducativos(idTemaActual); // ✅ conservar selección
        this.socketService.enviarEvento({
          tipo: 'ActualizarEstadoTema',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          timestamp: Date.now(),
        });
      },
      (err) => {
        Swal.showValidationMessage('Error al actualizar el estado del tema');
      }
    );
  }

  eliminar_temas_educativos() {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text:
        '¿Deseas eliminar el tema: ' + this.temaSeleccionado.titulo_tema + ' ?',
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
        const datostemas = {
          id_tema: this.temaSeleccionado.id_tema,
          codigo_institucion: this.sede?.codigo_institucion_encriptado,
          id_sede: this.sede?.id_sede_encriptado,
        };
      }
    });
  }

  /*************************** contenido educativo educativos******************************************** */

  /*************************** textos educativos******************************************** */
  guardarTexto() {
    if (!this.nuevoTexto.contenido.trim()) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'campos vacios',
        Texto: 'por favor ingrese un contenido para poder continuar',
        Tipo: 'warning',
      });
      return;
    }
    const datos_texto = {
      titulo_texto: this.nuevoTexto.titulo,
      contenido: this.nuevoTexto.contenido,
      id_tema: this.temaSeleccionado.id_tema,
      tipo_contenido: 'texto',
      documento_profesor: this.usuario?.documento,
    };

    this.materiaService.CreartextosEducativos(datos_texto).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.consultar_contenido_tema_seleccionado();
        this.nuevoTexto.titulo = '';
        this.nuevoTexto.contenido = '';
        this.socketService.enviarEvento({
          tipo: 'NuevoTextoRegistrado',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          timestamp: Date.now(),
        });
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

  ActualizarInformacionTextos() {
    // Buscar el índice original
    const index = this.contenidoTemaSeleccionado.findIndex(
      (c) => c.id === this.contenidoEditando.id
    );
    if (index !== -1) {
      this.contenidoTemaSeleccionado[index] = { ...this.contenidoEditando };
    }

    this.materiaService
      .ActualizartextosEducativos(this.contenidoEditando)
      .subscribe({
        next: (respuesta) => {
          this.alertaService.mostrarAlerta(respuesta);
          this.consultar_contenido_tema_seleccionado();
          this.socketService.enviarEvento({
            tipo: 'TextoActualizado',
            id_materia: this.id_materia,
            id_grado: this.id_grado,
            id_grupo: this.id_grupo,
            timestamp: Date.now(),
          });
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

  eliminarTexto(contenido: any) {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text: '¿Deseas eliminar el texto: ' + contenido.titulo + ' ?',
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
        this.materiaService.EliminartextosEducativos(contenido).subscribe({
          next: (respuesta) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.consultar_contenido_tema_seleccionado();
            this.socketService.enviarEvento({
              tipo: 'TextoEliminado',
              id_materia: this.id_materia,
              id_grado: this.id_grado,
              id_grupo: this.id_grupo,
              timestamp: Date.now(),
            });
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

  /*************************** imagenes educativas******************************************** */

  onFileSelected(event: any) {
    const file: File = event.target.files[0];
    if (file) {
      this.archivoImagen = file;

      const reader = new FileReader();
      reader.onload = (e: any) => {
        this.imagenPrevisualizada = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  }

  onDragOver(event: DragEvent) {
    event.preventDefault();
  }

  onDrop(event: DragEvent) {
    event.preventDefault();
    if (event.dataTransfer?.files.length) {
      const file = event.dataTransfer.files[0];
      this.archivoImagen = file;

      const reader = new FileReader();
      reader.onload = (e: any) => {
        this.imagenPrevisualizada = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  }

  previsualizarDesdeUrl() {
    this.archivoImagen = null;
    this.imagenPrevisualizada = this.imagenDesdeUrl;
  }

  guardarImagen() {
    const imagenDesdeArchivo = this.archivoImagen;
    const imagenDesdeUrl = this.imagenDesdeUrl?.trim();
    const imagenPrevisualizada = this.imagenPrevisualizada;

    console.log('archivoImagen:', imagenDesdeArchivo);
    console.log('imagenDesdeUrl:', imagenDesdeUrl);

    // Validación 1: ambos vacíos
    if (!imagenDesdeArchivo && !imagenDesdeUrl) {
      Swal.fire({
        icon: 'warning',
        title: 'Faltan datos',
        text: 'Debes subir una imagen o pegar el enlace de una imagen.',
      });
      return;
    }

    // Validación 2: ambos con datos
    if (imagenDesdeArchivo && imagenDesdeUrl) {
      Swal.fire({
        icon: 'error',
        title: 'Acción inválida',
        text: 'Solo puedes subir una imagen o pegar un enlace, no ambos al mismo tiempo.',
      });
      return;
    }

    // Validación 3: URL inválida
    if (
      imagenDesdeUrl &&
      !/\.(jpeg|jpg|png|gif|bmp|webp)(\?.*)?$/i.test(imagenDesdeUrl)
    ) {
      Swal.fire({
        icon: 'error',
        title: 'URL inválida',
        text: 'El enlace no parece ser una imagen válida.',
      });
      return;
    }

    // Enviar al backend
    const datos = {
      tipo: imagenDesdeArchivo ? 'archivo' : 'url',
      contenido: imagenDesdeArchivo || imagenDesdeUrl,
      id_tema: this.temaSeleccionado.id_tema,
      tipo_contenido: 'imagen',
      documento_profesor: this.usuario?.documento,
      codigo_institucion: this.sede?.codigo_institucion_encriptado,
      id_sede: this.sede?.id_sede_encriptado,
    };

    this.materiaService.CargarImagenesTemas(datos).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.consultar_contenido_tema_seleccionado();
        this.archivoImagen = null;
        this.imagenPrevisualizada = null;
        this.imagenDesdeUrl = '';
        // 🛰️ Notificar a otros clientes que se ha registrado una nueva imagen
        this.socketService.enviarEvento({
          tipo: 'NuevaImagenRegistrada',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          timestamp: Date.now(),
        });
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

  eliminarImagenes(contenido: any) {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text: '¿Deseas eliminar la imagen ?',
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
        const datos = {
          ...contenido,
          id_tema: this.temaSeleccionado.id_tema,
          codigo_institucion: this.sede?.codigo_institucion_encriptado,
          id_sede: this.sede?.id_sede_encriptado,
        };

        this.materiaService.EliminarImagenesEducativas(datos).subscribe({
          next: (respuesta) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.consultar_contenido_tema_seleccionado();
            this.socketService.enviarEvento({
              tipo: 'ImagenEliminada',
              id_materia: this.id_materia,
              id_grado: this.id_grado,
              id_grupo: this.id_grupo,
              timestamp: Date.now(),
            });
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

  /*************************** archivos educativos******************************************** */

  archivosEducativos: any[] = [];

  onFileSelectedArchivo(event: any) {
    const files = event.target.files;
    if (files) this.procesarArchivosArchivo(files);
  }

  onDragOverArchivo(event: DragEvent) {
    event.preventDefault();
  }

  onDropArchivo(event: DragEvent) {
    event.preventDefault();
    const files = event.dataTransfer?.files;
    if (files) this.procesarArchivosArchivo(files);
  }

  procesarArchivosArchivo(fileList: FileList) {
    Array.from(fileList).forEach((file) => {
      const reader = new FileReader();
      reader.onload = (e: any) => {
        this.archivosEducativos.push({
          file,
          name: file.name,
          type: file.type,
          preview: e.target.result,
        });
      };
      reader.readAsDataURL(file);
    });
  }

  eliminarArchivoEducativo(index: number) {
    this.archivosEducativos.splice(index, 1);
  }

  esImagenArchivo(archivo: any): boolean {
    return archivo.type.startsWith('image/');
  }

  obtenerIconoArchivo(nombre: string): string {
    if (nombre.endsWith('.pdf')) return 'fas fa-file-pdf text-danger';
    if (nombre.endsWith('.doc') || nombre.endsWith('.docx'))
      return 'fas fa-file-word text-primary';
    if (nombre.endsWith('.xls') || nombre.endsWith('.xlsx'))
      return 'fas fa-file-excel text-success';
    return 'fas fa-file-alt text-secondary';
  }

  enviarArchivos() {
    const formData = new FormData();

    // Agregar archivos al FormData

    this.archivosEducativos.forEach((a) => {
      formData.append('archivos[]', a.file);
    });

    // Agregar metadatos al FormData
    formData.append('id_tema', this.temaSeleccionado.id_tema);
    formData.append('tipo_contenido', 'archivo');
    formData.append('documento_profesor', this.usuario?.documento);
    formData.append(
      'codigo_institucion',
      this.sede?.codigo_institucion_encriptado
    );
    formData.append('id_sede', this.sede?.id_sede_encriptado);

    // Enviar al servicio
    this.materiaService.CrearArchivosEducativos(formData).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.consultar_contenido_tema_seleccionado();
        this.tipoArchivoSeleccionado = '';
        this.archivosEducativos = []; // Limpiar archivos
        this.socketService.enviarEvento({
          tipo: 'ArchivosRegistrados',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          timestamp: Date.now(),
        });
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

  EliminarArchivosTemasEducativos(contenido: any) {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text: '¿Deseas eliminar el archivo: ' + contenido.url_archivo + ' ?',
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
        const datos = {
          ...contenido,
          id_tema: this.temaSeleccionado.id_tema,
          codigo_institucion: this.sede?.codigo_institucion_encriptado,
          id_sede: this.sede?.id_sede_encriptado,
        };

        this.materiaService.EliminarArchivosEducativas(datos).subscribe({
          next: (respuesta) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.consultar_contenido_tema_seleccionado();
            this.socketService.enviarEvento({
              tipo: 'ArchivoEliminado',
              id_materia: this.id_materia,
              id_grado: this.id_grado,
              id_grupo: this.id_grupo,
              timestamp: Date.now(),
            });
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
  /*************************** videos educativos******************************************** */

  videoUrl: string = '';

  getYoutubeEmbedUrl(url: string): SafeResourceUrl | null {
    const videoId = this.getYoutubeVideoId(url);
    if (videoId) {
      const embedUrl = `https://www.youtube.com/embed/${videoId}`;
      return this.sanitizer.bypassSecurityTrustResourceUrl(embedUrl);
    }
    return null;
  }

  getYoutubeVideoId(url: string): string | null {
    if (!url) return null;

    // 1. Si viene con "v=" como en watch?v=abc123
    const vParamMatch = url.match(/[?&]v=([^&#]+)/);
    if (vParamMatch) return vParamMatch[1];

    // 2. Si es formato corto youtu.be/abc123
    const shortUrlMatch = url.match(/youtu\.be\/([^&#]+)/);
    if (shortUrlMatch) return shortUrlMatch[1];

    // 3. Si es formato embed
    const embedMatch = url.match(/youtube\.com\/embed\/([^&#]+)/);
    if (embedMatch) return embedMatch[1];

    return null;
  }

  guardarVideos() {
    // Validar que la URL no esté vacía ni inválida
    const videoId = this.getYoutubeVideoId(this.videoUrl);
    if (!this.videoUrl || !videoId) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Enlace no válido',
        Texto:
          'Por favor ingresa un enlace de YouTube válido antes de guardar.',
        Tipo: 'warning',
      });
      return;
    }

    // Preparar datos para enviar al backend
    const datos = {
      video: this.videoUrl,
      id_tema: this.temaSeleccionado.id_tema,
      tipo_contenido: 'video',
      documento_profesor: this.usuario?.documento,
      codigo_institucion: this.sede?.codigo_institucion_encriptado,
      id_sede: this.sede?.id_sede_encriptado,
    };

    // Realizar la petición
    this.materiaService.CargarVideosTemas(datos).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.consultar_contenido_tema_seleccionado();
        this.videoUrl = ''; // Limpiar el campo de entrada

        this.socketService.enviarEvento({
          tipo: 'NuevoVideoRegistrado',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          timestamp: Date.now(),
        });
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error de servidor',
          Texto: 'No se pudo procesar tu solicitud',
          Tipo: 'error',
        });
      },
    });
  }

  eliminarVideos(contenido: any) {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text: '¿Deseas eliminar el Video ?',
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
        this.materiaService.EliminartextosEducativos(contenido).subscribe({
          // lo masndamos al servicio eliminar texto, porque tambien nos sirve para eliminar videos
          next: (respuesta) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.consultar_contenido_tema_seleccionado();

            this.socketService.enviarEvento({
              tipo: 'VideoEliminado',
              id_materia: this.id_materia,
              id_grado: this.id_grado,
              id_grupo: this.id_grupo,
              timestamp: Date.now(),
            });
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

  /*************************** tareas  educativos******************************************** */

  entregaSeleccionada: string = ''; // 'texto' o 'archivo'
  tipoArchivoSeleccionado: string = ''; // valor final a enviar
  esGrupal: number = 0; // Por defecto individual

  nuevaTarea = {
    // se ultiliza tanto para crear tareas como talleres
    titulo: '',
    contenido: '',
    fecha_entrega: '',
    fecha_inicio: '',
    fecha_limite_entrega: '',
    recordarme_calificar: '',
  };

  seleccionarEntrega(valor: string): void {
    this.entregaSeleccionada = valor;
    if (valor !== 'archivo') {
      this.tipoArchivoSeleccionado = ''; // Reiniciar si no es archivo
    }
  }

  tiposEntrega = [
    { nombre: 'Texto', valor: 'texto', icono: 'fa-solid fa-pen-nib' },
    { nombre: 'Archivo', valor: 'archivo', icono: 'fa-solid fa-paperclip' },
  ];

  tiposArchivosPermitidos = [
    {
      tipo: 'PDF',
      icono: 'fa-file-pdf',
      extension: 'pdf',
      colorIcono: '#e74c3c',
    },
    {
      tipo: 'Word (DOCX)',
      icono: 'fa-file-word',
      extension: 'docx',
      colorIcono: '#2a5699',
    },
    {
      tipo: 'Excel (XLSX)',
      icono: 'fa-file-excel',
      extension: 'xlsx',
      colorIcono: '#207245',
    },
    {
      tipo: 'PowerPoint (PPTX)',
      icono: 'fa-file-powerpoint',
      extension: 'pptx',
      colorIcono: '#d35400',
    },
    {
      tipo: 'Imagen JPG',
      icono: 'fa-file-image',
      extension: 'jpg',
      colorIcono: '#c0392b',
    },
    {
      tipo: 'Imagen PNG',
      icono: 'fa-file-image',
      extension: 'png',
      colorIcono: '#2980b9',
    },
    {
      tipo: 'Archivo comprimido (ZIP)',
      icono: 'fa-file-zipper',
      extension: 'zip',
      colorIcono: '#8e44ad',
    },
    {
      tipo: 'Archivo comprimido (RAR)',
      icono: 'fa-file-zipper',
      extension: 'rar',
      colorIcono: '#8e44ad',
    },
    {
      tipo: 'Archivo de texto (TXT)',
      icono: 'fa-file-lines',
      extension: 'txt',
      colorIcono: '#34495e',
    },
  ];

  guardarTareas() {
    const camposRequeridos = [
      { campo: this.nuevaTarea.titulo, nombre: 'el título de la tarea' },
      { campo: this.nuevaTarea.contenido, nombre: 'el contenido' },
      { campo: this.nuevaTarea.fecha_inicio, nombre: 'la fecha de inicio' },
      { campo: this.nuevaTarea.fecha_entrega, nombre: 'la fecha de entrega' },
      {
        campo: this.nuevaTarea.fecha_limite_entrega,
        nombre: 'la fecha límite de entrega',
      },
      {
        campo: this.nuevaTarea.recordarme_calificar,
        nombre: 'el recordatorio para calificar',
      },
      { campo: this.entregaSeleccionada, nombre: 'el tipo de entrega' },
    ];

    for (const item of camposRequeridos) {
      if (!item.campo || item.campo.toString().trim() === '') {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Campos vacíos',
          Texto: `Por favor ingrese ${item.nombre} para continuar.`,
          Tipo: 'warning',
        });
        return;
      }
    }

    if (
      this.entregaSeleccionada === 'archivo' &&
      !this.tipoArchivoSeleccionado
    ) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Tipo de archivo no seleccionado',
        Texto: 'Por favor seleccione un tipo de archivo para continuar.',
        Tipo: 'warning',
      });
      return;
    }

    if (this.nuevaTarea.fecha_entrega < this.nuevaTarea.fecha_inicio) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Fecha de entrega inválida',
        Texto:
          'La fecha de entrega no puede ser anterior a la fecha de inicio.',
        Tipo: 'warning',
      });
      return;
    }

    if (this.nuevaTarea.fecha_limite_entrega < this.nuevaTarea.fecha_entrega) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Fecha límite inválida',
        Texto:
          'La fecha límite de entrega no puede ser anterior a la fecha de entrega.',
        Tipo: 'warning',
      });
      return;
    }
    if (this.nuevaTarea.fecha_limite_entrega < this.nuevaTarea.fecha_inicio) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Fecha límite inválida',
        Texto:
          'La fecha límite de entrega no puede ser anterior a la fecha de inicio.',
        Tipo: 'warning',
      });
      return;
    }

    // crear formData para enviar archivos si es necesario
    const formData = new FormData();

    // Agregar archivos al FormData
    this.archivosEducativos.forEach((a) => {
      formData.append('archivos[]', a.file);
    });

    formData.append('titulo_tarea', this.nuevaTarea.titulo);
    formData.append('contenido_tarea', this.nuevaTarea.contenido);
    formData.append('fecha_entrega', this.nuevaTarea.fecha_entrega);
    formData.append('fecha_inicio', this.nuevaTarea.fecha_inicio);
    formData.append(
      'fecha_limite_entrega',
      this.nuevaTarea.fecha_limite_entrega
    );
    formData.append(
      'recordarme_calificar',
      this.nuevaTarea.recordarme_calificar
    );
    formData.append('tipo_entrega', this.entregaSeleccionada);
    formData.append('tipo_archivo_seleccionado', this.tipoArchivoSeleccionado);
    formData.append('es_grupal', this.esGrupal.toString());
    formData.append('id_tema', this.temaSeleccionado.id_tema);
    formData.append('documento_profesor', this.usuario?.documento);
    formData.append(
      'codigo_institucion',
      this.sede?.codigo_institucion_encriptado
    );
    formData.append('id_sede', this.sede?.id_sede_encriptado);
    formData.append('id_grado', this.id_grado);
    formData.append('id_grupo', this.id_grupo);
    formData.append('id_materia', this.id_materia);
    formData.append('tipo_contenido', 'tarea');

    this.materiaService.CrearTareasEducativas(formData).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.consultar_contenido_tema_seleccionado();
        // Limpiar campos después de guardar
        this.nuevaTarea = {
          titulo: '',
          contenido: '',
          fecha_entrega: '',
          fecha_inicio: '',
          fecha_limite_entrega: '',
          recordarme_calificar: '',
        };
        this.entregaSeleccionada = '';
        this.tipoArchivoSeleccionado = '';
        this.archivosEducativos = []; // Limpiar archivos

        this.socketService.enviarEvento({
          tipo: 'NuevaTareaRegistrada',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          timestamp: Date.now(),
        });
      },
      error: (error) => {
        if (error.error && error.error.Titulo) {
          this.alertaService.mostrarAlerta(error.error);
        } else {
          this.alertaService.mostrarAlerta({
            Alerta: 'simple',
            Titulo: 'Error de servidor',
            Texto: 'No se pudo procesar tu solicitud',
            Tipo: 'error',
          });
        }
      },
    });
  }

  editarTarea(contenido: any) {
    this.contenidoEditando = { ...contenido };

    const offcanvasElement = document.getElementById(
      'offcanvasBottomeditarTareas'
    );
    if (offcanvasElement) {
      const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
      offcanvas.show();
    }
    this.consultarInformaTareasEducativas(contenido.id_contenido);
  }

  consultarInformaTareasEducativas(id_contenido: number) {
    const datos = {
      id_contenido: id_contenido,
      id_tema: this.temaSeleccionado.id_tema,
      codigo_institucion: this.sede?.codigo_institucion_encriptado,
      id_sede: this.sede?.id_sede_encriptado,
    };

    this.materiaService.ConsultarInformacionTareasEducativas(datos).subscribe({
      next: (respuesta: any) => {
        this.contenidoEditandoTareas = { ...respuesta };
        this.listaArchivos = JSON.parse(respuesta.archivos_adjuntos || '[]');
        // ✅ Iniciar cuenta regresiva al recibir la fecha
        this.iniciarCuentaRegresiva(this.contenidoEditandoTareas.fecha_entrega);
        this.consultarEntregasTareasEstudiantes(
          this.contenidoEditandoTareas.id_tarea
        ); // consultar si el usuario tiene entregas registradas
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error al consultar tarea',
          Texto: 'No se pudo consultar la información de la tarea',
          Tipo: 'error',
        });
      },
    });
  }

  EliminarArchivoTareaRegistrada(
    nombre: string,
    id_tarea: number,
    nombre_tarea: string,
    id_contenido: number
  ) {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text:
        '¿Deseas eliminar el archivo ' +
        nombre +
        ' de la tarea ' +
        nombre_tarea +
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
        const datos = {
          nombre_archivo: nombre,
          id_tarea: id_tarea,
          documento_profesor: this.usuario?.documento,
          id_tema: this.temaSeleccionado.id_tema,
          codigo_institucion: this.sede?.codigo_institucion_encriptado,
          id_sede: this.sede?.id_sede_encriptado,
        };

        this.materiaService
          .eliminar_archivos_tareas_registrados(datos)
          .subscribe({
            next: (respuesta) => {
              this.alertaService.mostrarAlerta(respuesta);
              this.consultarInformaTareasEducativas(id_contenido);
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

  EditarTareaRegistrada(id_contenido: number, id_tarea: number) {
    const camposRequeridos = [
      {
        campo: this.contenidoEditandoTareas.titulo_tarea,
        nombre: 'el título de la tarea',
      },
      {
        campo: this.contenidoEditandoTareas.descripcion,
        nombre: 'el contenido',
      },
      {
        campo: this.contenidoEditandoTareas.fecha_inicio,
        nombre: 'la fecha de inicio',
      },
      {
        campo: this.contenidoEditandoTareas.fecha_entrega,
        nombre: 'la fecha de entrega',
      },
      {
        campo: this.contenidoEditandoTareas.fecha_limite_entrega,
        nombre: 'la fecha límite de entrega',
      },
      {
        campo: this.contenidoEditandoTareas.recordarme_calificar,
        nombre: 'el recordatorio para calificar',
      },
      { campo: this.entregaSeleccionada, nombre: 'el tipo de entrega' },
    ];

    for (const item of camposRequeridos) {
      if (!item.campo || item.campo.toString().trim() === '') {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Campos vacíos',
          Texto: `Por favor ingrese ${item.nombre} para continuar.`,
          Tipo: 'warning',
        });
        return;
      }
    }

    if (
      this.entregaSeleccionada === 'archivo' &&
      !this.tipoArchivoSeleccionado
    ) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Tipo de archivo no seleccionado',
        Texto: 'Por favor seleccione un tipo de archivo para continuar.',
        Tipo: 'warning',
      });
      return;
    }
    if (
      this.contenidoEditandoTareas.fecha_entrega <
      this.contenidoEditandoTareas.fecha_inicio
    ) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Fecha de entrega inválida',
        Texto:
          'La fecha de entrega no puede ser anterior a la fecha de inicio.',
        Tipo: 'warning',
      });
      return;
    }

    if (
      this.contenidoEditandoTareas.fecha_limite_entrega <
      this.contenidoEditandoTareas.fecha_entrega
    ) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Fecha límite inválida',
        Texto:
          'La fecha límite de entrega no puede ser anterior a la fecha de entrega.',
        Tipo: 'warning',
      });
      return;
    }
    if (
      this.contenidoEditandoTareas.fecha_limite_entrega <
      this.contenidoEditandoTareas.fecha_inicio
    ) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Fecha límite inválida',
        Texto:
          'La fecha límite de entrega no puede ser anterior a la fecha de inicio.',
        Tipo: 'warning',
      });
      return;
    }

    // crear formData para enviar archivos si es necesario
    const formData = new FormData();

    // Agregar archivos al FormData
    this.archivosEducativos.forEach((a) => {
      formData.append('archivos[]', a.file);
    });

    formData.append('titulo_tarea', this.contenidoEditandoTareas.titulo_tarea);
    formData.append(
      'contenido_tarea',
      this.contenidoEditandoTareas.descripcion
    );
    formData.append(
      'fecha_entrega',
      this.contenidoEditandoTareas.fecha_entrega
    );
    formData.append('fecha_inicio', this.contenidoEditandoTareas.fecha_inicio);
    formData.append(
      'fecha_limite_entrega',
      this.contenidoEditandoTareas.fecha_limite_entrega
    );
    formData.append(
      'recordarme_calificar',
      this.contenidoEditandoTareas.recordarme_calificar
    );
    formData.append('tipo_entrega', this.entregaSeleccionada);
    formData.append('tipo_archivo_seleccionado', this.tipoArchivoSeleccionado);
    formData.append('es_grupal', this.esGrupal.toString());
    formData.append('id_tema', this.temaSeleccionado.id_tema);
    formData.append('documento_profesor', this.usuario?.documento);
    formData.append(
      'codigo_institucion',
      this.sede?.codigo_institucion_encriptado
    );
    formData.append('id_sede', this.sede?.id_sede_encriptado);
    formData.append('id_contenido', id_contenido.toString());
    formData.append('id_tarea', id_tarea.toString());

    this.materiaService.EditarTareasEducativas(formData).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.consultarInformaTareasEducativas(id_contenido);
        this.consultarEntregasTareasEstudiantes(id_tarea.toString());
        this.consultar_contenido_tema_seleccionado();
        // Limpiar campos después de guardar
        this.entregaSeleccionada = '';
        this.tipoArchivoSeleccionado = '';
        this.archivosEducativos = []; // Limpiar archivos

        this.socketService.enviarEvento({
          tipo: 'TareaEditada',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          id_tarea: id_tarea,
          id_contenido: id_contenido, // 👈 importante para que el receptor sepa qué actualizar
          timestamp: Date.now(),
        });
      },
      error: (error) => {
        if (error.error && error.error.Titulo) {
          this.alertaService.mostrarAlerta(error.error);
        } else {
          this.alertaService.mostrarAlerta({
            Alerta: 'simple',
            Titulo: 'Error de servidor',
            Texto: 'No se pudo procesar tu solicitud',
            Tipo: 'error',
          });
        }
      },
    });
  }

  eliminarTareasEducativas(contenido: any) {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text: '¿Deseas eliminar la tareas ' + contenido.titulo + ' ?',
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
        const datostemas = {
          id_tema: this.temaSeleccionado.id_tema,
          codigo_institucion: this.sede?.codigo_institucion_encriptado,
          id_sede: this.sede?.id_sede_encriptado,
          id_contenido: contenido.id_contenido,
          documento_profesor: this.usuario?.documento,
        };

        this.materiaService.EliminarTareasEducativas(datostemas).subscribe({
          // lo masndamos al servicio eliminar texto, porque tambien nos sirve para eliminar videos
          next: (respuesta) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.consultar_contenido_tema_seleccionado();

            this.socketService.enviarEvento({
              tipo: 'TareaEliminada',
              id_materia: this.id_materia,
              id_grado: this.id_grado,
              id_grupo: this.id_grupo,
              timestamp: Date.now(),
            });
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

  /********************* entregar tareas y calificar tareas ********************** */

  InformacionTareas: any = {};
  ResultadoEntregaEstudiante: any = {};
  IdTareaRegistradaEstiudiante: number = 0; // ID de la tarea registrada por el estudiante
  contenidoTareaEstudiante = ''; // Contenido de la tarea a entregar
  tipoEntrega: string = ''; // texto | archivo | mixto
  tipoArchivoPermitido: string = ''; // 'application/pdf,image/*' etc.
  modoFormulario: 'oculto' | 'formulario' = 'oculto';
  tiempoRestante: string = '';
  private timerSub!: Subscription;
  entregaVencida = false;

  AbrirAffcaRevisarTareas(contenido: any) {
    this.InformacionTareas = { ...contenido };
    // Mostrar el offcanvas para revisar
    const offcanvasElement = document.getElementById(
      'offcanvasBottomRevisarTareas'
    );
    const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
    offcanvas.show();
    this.consultarInformaTareasEducativas(this.InformacionTareas.id_contenido);
  }

  iniciarCuentaRegresiva(fechaEntregaString: string) {
    // Detener cuenta anterior si existe
    if (this.timerSub) {
      this.timerSub.unsubscribe();
    }

    if (!fechaEntregaString) {
      this.tiempoRestante = 'Fecha no disponible';
      this.entregaVencida = true;
      return;
    }

    const fechaEntrega = new Date(fechaEntregaString).getTime();

    if (isNaN(fechaEntrega)) {
      this.tiempoRestante = 'Fecha inválida';
      this.entregaVencida = true;
      return;
    }

    this.entregaVencida = false; // Reiniciamos por si estaba en true
    this.timerSub = interval(1000).subscribe(() => {
      const ahora = Date.now();
      const diferencia = fechaEntrega - ahora;

      if (diferencia <= 0) {
        this.tiempoRestante = 'Tiempo vencido';
        this.entregaVencida = true;
        this.timerSub.unsubscribe();
      } else {
        this.tiempoRestante = this.formatearTiempo(diferencia);
      }
    });
  }

  formatearTiempo(ms: number): string {
    const seg = Math.floor(ms / 1000) % 60;
    const min = Math.floor(ms / (1000 * 60)) % 60;
    const hrs = Math.floor(ms / (1000 * 60 * 60)) % 24;
    const dias = Math.floor(ms / (1000 * 60 * 60 * 24));

    let tiempo = '';
    if (dias > 0) tiempo += `${dias} día${dias !== 1 ? 's' : ''} `;
    if (hrs > 0 || dias > 0) tiempo += `${hrs} h `;
    if (min > 0 || hrs > 0 || dias > 0) tiempo += `${min} min `;
    tiempo += `${seg} seg`;

    return tiempo;
  }

  consultarEntregasTareasEstudiantes(id_tarea: string) {
    const datos = {
      id_tarea: id_tarea,
      documento_estudiante: this.usuario?.documento,
    };

    this.servicioEstudiantes
      .ConsultarEnregasTareasEstudiantes(datos)
      .subscribe({
        next: (respuesta: any) => {
          if (respuesta.entrega === null) {
            this.ResultadoEntregaEstudiante = {
              modoEntrega: respuesta.mensaje,
              entregaEstudiante: null,
              estadoCalificacion: 'No calificado',
              fechaEntregaReal: null,
              ultimaModificacion: null,
              tipo: null,
              archivo: null,
            };
          } else {
            const entrega = respuesta.entrega;
            this.ResultadoEntregaEstudiante = {
              modoEntrega: `Entregado el ${new Date(
                entrega.fecha_entrega
              ).toLocaleString()}`,
              estadoCalificacion: entrega.calificado
                ? `Calificado: ${entrega.calificacion}`
                : 'No calificado',
              fechaEntregaReal: entrega.fecha_entrega,
              ultimaModificacion: entrega.ultima_modificacion,
              tipo: entrega.contenido_texto ? 'texto' : 'archivo',
              archivo: entrega.archivo_adjunto,
              texto: entrega.contenido_texto || null,
              id_entrega: entrega.id_entrega,
              retroalimentacion: entrega.retroalimentacion || null, // Aquí
            };
          }
        },
        error: () => {
          this.alertaService.mostrarAlerta({
            Alerta: 'simple',
            Titulo: 'Error al consultar entregas',
            Texto: 'No se pudo consultar las entregas de la tarea',
            Tipo: 'error',
          });
        },
      });
  }

  activarFormularioEntrega() {
    this.modoFormulario = 'formulario';
    this.tipoEntrega = this.contenidoEditandoTareas.tipo_entrega; // por ejemplo: "archivo"
    this.tipoArchivoPermitido =
      this.contenidoEditandoTareas.tipo_archivo_entrega || '*/*';
    this.archivosEducativos = []; // limpiar cualquier archivo anterior
    this.contenidoTareaEstudiante = ''; // limpiar contenido de tarea
  }

  cancelarFormularioEntrega() {
    this.modoFormulario = 'oculto';
    this.archivosEducativos = [];
    this.contenidoTareaEstudiante = ''; // limpiar contenido de tarea
    this.tipoEntrega = '';
    this.tipoArchivoPermitido = '';
  }

  enviarEntrega() {
    
    if (this.tipoEntrega === 'texto' && this.contenidoTareaEstudiante.length === 0) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Contenido vacío',
        Texto: 'Por favor ingresa el contenido de la tarea antes de enviar.',
        Tipo: 'warning',
      });
      return;
    }
    if (
      this.tipoEntrega === 'archivo' &&
      this.archivosEducativos.length === 0
    ) {
      if (this.archivosEducativos.length === 0) {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Archivo no seleccionado',
          Texto: 'Por favor selecciona un archivo para enviar.',
          Tipo: 'warning',
        });
        return;
      }
    }
    // Aquí se envía la entrega

    const formData = new FormData();

    if (this.tipoEntrega === 'texto') {
      formData.append('contenido_entrega', this.contenidoTareaEstudiante);
      formData.append('tipo_entrega', this.tipoEntrega);
      formData.append('documento_estudiante', this.usuario?.documento);
      formData.append(
        'id_tarea',
        this.contenidoEditandoTareas.id_tarea.toString()
      );

    } else if (this.tipoEntrega === 'archivo') {
      // Agregar archivos al FormData
      this.archivosEducativos.forEach((a) => {
        formData.append('archivos[]', a.file);
      });
      formData.append('tipo_entrega', this.tipoEntrega);
      formData.append('documento_estudiante', this.usuario?.documento);
      formData.append(
        'id_tarea',
        this.contenidoEditandoTareas.id_tarea.toString()
      );
      formData.append(
        'codigo_institucion',
        this.sede?.codigo_institucion_encriptado
      );
      formData.append('id_sede', this.sede?.id_sede_encriptado);
      formData.append('documento_profesor', this.documento_profesor);
      formData.append('id_tema', this.temaSeleccionado.id_tema);
    }

    this.servicioEstudiantes.EnviarEntregaTareasEstudiante(formData).subscribe({
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.consultarInformaTareasEducativas(
          this.contenidoEditandoTareas.id_contenido
        );
        this.consultar_contenido_tema_seleccionado();
        this.consultarEntregasTareasEstudiantes(
          this.contenidoEditandoTareas.id_tarea
        );
        this.cancelarFormularioEntrega();
        this.socketService.enviarEvento({
          tipo: 'TareaEntregada',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          id_tarea: this.contenidoEditandoTareas.id_tarea,
          id_contenido: this.contenidoEditandoTareas.id_contenido,
          timestamp: Date.now(),
        });
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error al consultar entregas',
          Texto: 'No se pudo consultar las entregas de la tarea',
          Tipo: 'error',
        });
      },
    });
  }

  eliminarEntregaTarea(id_entrega: number, nombre_archivo: string) {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text: '¿Deseas eliminar el archivo ' + nombre_archivo + ' ?',
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
        const formData = new FormData();
        formData.append('id_entrega', id_entrega.toString());
        formData.append('nombre_archivo', nombre_archivo);
        formData.append('tipo_entrega', 'archivo');
        formData.append('documento_estudiante', this.usuario?.documento);
        formData.append(
          'id_tarea',
          this.contenidoEditandoTareas.id_tarea.toString()
        );
        formData.append(
          'codigo_institucion',
          this.sede?.codigo_institucion_encriptado
        );
        formData.append('id_sede', this.sede?.id_sede_encriptado);
        formData.append('documento_profesor', this.documento_profesor);
        formData.append('id_tema', this.temaSeleccionado.id_tema);

        this.servicioEstudiantes
          .EliminarArchivoEntregaEstudiantes(formData)
          .subscribe({
            next: (respuesta: any) => {
              this.alertaService.mostrarAlerta(respuesta);
              this.consultarInformaTareasEducativas(
                this.contenidoEditandoTareas.id_contenido
              );
              this.consultar_contenido_tema_seleccionado();
              this.consultarEntregasTareasEstudiantes(
                this.contenidoEditandoTareas.id_tarea
              );
              this.cancelarFormularioEntrega();
              this.socketService.enviarEvento({
                tipo: 'TareaEntregadaEliminada',
                id_materia: this.id_materia,
                id_grado: this.id_grado,
                id_grupo: this.id_grupo,
                id_tarea: this.contenidoEditandoTareas.id_tarea,
                id_contenido: this.contenidoEditandoTareas.id_contenido,
                timestamp: Date.now(),
              });
            },
            error: () => {
              this.alertaService.mostrarAlerta({
                Alerta: 'simple',
                Titulo: 'Error al consultar entregas',
                Texto: 'No se pudo consultar las entregas de la tarea',
                Tipo: 'error',
              });
            },
          });
      }
    });
  }

  mostrarModalTexto: boolean = false;

  mostrarTextoEntrega() {
    this.mostrarModalTexto = true;
  }

  cerrarModalTexto() {
    this.mostrarModalTexto = false;
  }

  eliminarEntregaTextoTarea(id_entrega: number, ) {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text: '¿Deseas eliminar el texto de la tarea seleccionada ?',
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
        const formData = new FormData();
        formData.append('id_entrega', id_entrega.toString());
        formData.append('tipo_entrega', 'texto');
        formData.append('documento_estudiante', this.usuario?.documento);
        formData.append('id_tarea',this.contenidoEditandoTareas.id_tarea.toString());
        

        this.servicioEstudiantes
          .EliminartextoEntregaEstudiantes(formData)
          .subscribe({
            next: (respuesta: any) => {
              this.alertaService.mostrarAlerta(respuesta);
              this.consultarInformaTareasEducativas(
                this.contenidoEditandoTareas.id_contenido
              );
              this.consultar_contenido_tema_seleccionado();
              this.consultarEntregasTareasEstudiantes(
                this.contenidoEditandoTareas.id_tarea
              );
              this.cancelarFormularioEntrega();
              this.socketService.enviarEvento({
                tipo: 'TareaEntregadaEliminada',
                id_materia: this.id_materia,
                id_grado: this.id_grado,
                id_grupo: this.id_grupo,
                id_tarea: this.contenidoEditandoTareas.id_tarea,
                id_contenido: this.contenidoEditandoTareas.id_contenido,
                timestamp: Date.now(),
              });
            },
            error: () => {
              this.alertaService.mostrarAlerta({
                Alerta: 'simple',
                Titulo: 'Error al consultar entregas',
                Texto: 'No se pudo eliminar el texto de la tarea',
                Tipo: 'error',
              });
            },
          });
      }
    });
  }

  /*************************** crear talleres educativos  educativos******************************************** */

  guardarTalleres() {
    const camposRequeridos = [
      { campo: this.nuevaTarea.titulo, nombre: 'el título del taller' },
      { campo: this.nuevaTarea.contenido, nombre: 'el contenido' },
      { campo: this.nuevaTarea.fecha_inicio, nombre: 'la fecha de inicio' },
      { campo: this.nuevaTarea.fecha_entrega, nombre: 'la fecha de entrega' },
      {
        campo: this.nuevaTarea.fecha_limite_entrega,
        nombre: 'la fecha límite de entrega',
      },
      {
        campo: this.nuevaTarea.recordarme_calificar,
        nombre: 'el recordatorio para calificar',
      },
      { campo: this.entregaSeleccionada, nombre: 'el tipo de entrega' },
    ];

    for (const item of camposRequeridos) {
      if (!item.campo || item.campo.toString().trim() === '') {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Campos vacíos',
          Texto: `Por favor ingrese ${item.nombre} para continuar.`,
          Tipo: 'warning',
        });
        return;
      }
    }

    if (
      this.entregaSeleccionada === 'archivo' &&
      !this.tipoArchivoSeleccionado
    ) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Tipo de archivo no seleccionado',
        Texto: 'Por favor seleccione un tipo de archivo para continuar.',
        Tipo: 'warning',
      });
      return;
    }

    if (this.nuevaTarea.fecha_entrega < this.nuevaTarea.fecha_inicio) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Fecha de entrega inválida',
        Texto:
          'La fecha de entrega no puede ser anterior a la fecha de inicio.',
        Tipo: 'warning',
      });
      return;
    }

    if (this.nuevaTarea.fecha_limite_entrega < this.nuevaTarea.fecha_entrega) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Fecha límite inválida',
        Texto:
          'La fecha límite de entrega no puede ser anterior a la fecha de entrega.',
        Tipo: 'warning',
      });
      return;
    }
    if (this.nuevaTarea.fecha_limite_entrega < this.nuevaTarea.fecha_inicio) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Fecha límite inválida',
        Texto:
          'La fecha límite de entrega no puede ser anterior a la fecha de inicio.',
        Tipo: 'warning',
      });
      return;
    }

    // crear formData para enviar archivos si es necesario
    const formData = new FormData();

    // Agregar archivos al FormData
    this.archivosEducativos.forEach((a) => {
      formData.append('archivos[]', a.file);
    });

    formData.append('titulo_tarea', this.nuevaTarea.titulo);
    formData.append('contenido_tarea', this.nuevaTarea.contenido);
    formData.append('fecha_entrega', this.nuevaTarea.fecha_entrega);
    formData.append('fecha_inicio', this.nuevaTarea.fecha_inicio);
    formData.append(
      'fecha_limite_entrega',
      this.nuevaTarea.fecha_limite_entrega
    );
    formData.append(
      'recordarme_calificar',
      this.nuevaTarea.recordarme_calificar
    );
    formData.append('tipo_entrega', this.entregaSeleccionada);
    formData.append('tipo_archivo_seleccionado', this.tipoArchivoSeleccionado);
    formData.append('es_grupal', this.esGrupal.toString());
    formData.append('id_tema', this.temaSeleccionado.id_tema);
    formData.append('documento_profesor', this.usuario?.documento);
    formData.append(
      'codigo_institucion',
      this.sede?.codigo_institucion_encriptado
    );
    formData.append('id_sede', this.sede?.id_sede_encriptado);
    formData.append('id_grado', this.id_grado);
    formData.append('id_grupo', this.id_grupo);
    formData.append('id_materia', this.id_materia);
    formData.append('tipo_contenido', 'taller');

    this.materiaService.CrearTareasEducativas(formData).subscribe({
      // sirve para crear talleres tambien
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.consultar_contenido_tema_seleccionado();
        // Limpiar campos después de guardar
        this.nuevaTarea = {
          titulo: '',
          contenido: '',
          fecha_entrega: '',
          fecha_inicio: '',
          fecha_limite_entrega: '',
          recordarme_calificar: '',
        };
        this.entregaSeleccionada = '';
        this.tipoArchivoSeleccionado = '';
        this.archivosEducativos = []; // Limpiar archivos
        this.socketService.enviarEvento({
          tipo: 'NuevoTallerRegistrado',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          timestamp: Date.now(),
        });
      },
      error: (error) => {
        if (error.error && error.error.Titulo) {
          this.alertaService.mostrarAlerta(error.error);
        } else {
          this.alertaService.mostrarAlerta({
            Alerta: 'simple',
            Titulo: 'Error de servidor',
            Texto: 'No se pudo procesar tu solicitud',
            Tipo: 'error',
          });
        }
      },
    });
  }

  editarTaller(contenido: any) {
    this.contenidoEditando = { ...contenido };

    const offcanvasElement = document.getElementById(
      'offcanvasBottomeditarTaller'
    );
    if (offcanvasElement) {
      const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
      offcanvas.show();
    }
    this.consultarInformaTalleresEducativas(contenido.id_contenido);
  }

  consultarInformaTalleresEducativas(id_contenido: number) {
    const datos = {
      id_contenido: id_contenido,
      documento_profesor: this.usuario?.documento,
      id_tema: this.temaSeleccionado.id_tema,
      codigo_institucion: this.sede?.codigo_institucion_encriptado,
      id_sede: this.sede?.id_sede_encriptado,
    };

    this.materiaService
      .ConsultarInformacionTalleresEducativas(datos)
      .subscribe({
        next: (respuesta: any) => {
          this.contenidoEditandoTareas = { ...respuesta };
          this.listaArchivos = JSON.parse(respuesta.archivos_adjuntos || '[]');
        },
        error: () => {
          this.alertaService.mostrarAlerta({
            Alerta: 'simple',
            Titulo: 'Error al consultar tarea',
            Texto: 'No se pudo consultar la información de la tarea',
            Tipo: 'error',
          });
        },
      });
  }

  EliminarArchivoTallerRegistrado(
    nombre: string,
    id_taller: number,
    nombre_taller: string,
    id_contenido: number
  ) {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text:
        '¿Deseas eliminar el archivo ' +
        nombre +
        ' del taller,  ' +
        nombre_taller +
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
        const datos = {
          nombre_archivo: nombre,
          id_taller: id_taller,
          documento_profesor: this.usuario?.documento,
          id_tema: this.temaSeleccionado.id_tema,
          codigo_institucion: this.sede?.codigo_institucion_encriptado,
          id_sede: this.sede?.id_sede_encriptado,
        };

        this.materiaService
          .eliminar_archivos_taller_registrados(datos)
          .subscribe({
            next: (respuesta) => {
              this.alertaService.mostrarAlerta(respuesta);
              this.consultarInformaTalleresEducativas(id_contenido);
              this.socketService.enviarEvento({
                tipo: 'ArchivoTallerEliminado',
                id_materia: this.id_materia,
                id_grado: this.id_grado,
                id_grupo: this.id_grupo,
                id_contenido: id_contenido, // 👈 importante para que el receptor sepa qué actualizar
                timestamp: Date.now(),
              });
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

  EditarTalleresRegistrados(id_contenido: number, id_taller: number) {
    const camposRequeridos = [
      {
        campo: this.contenidoEditandoTareas.titulo_tarea,
        nombre: 'el título del taller',
      },
      {
        campo: this.contenidoEditandoTareas.descripcion,
        nombre: 'el contenido',
      },
      {
        campo: this.contenidoEditandoTareas.fecha_inicio,
        nombre: 'la fecha de inicio',
      },
      {
        campo: this.contenidoEditandoTareas.fecha_entrega,
        nombre: 'la fecha de entrega',
      },
      {
        campo: this.contenidoEditandoTareas.fecha_limite_entrega,
        nombre: 'la fecha límite de entrega',
      },
      {
        campo: this.contenidoEditandoTareas.recordarme_calificar,
        nombre: 'el recordatorio para calificar',
      },
      { campo: this.entregaSeleccionada, nombre: 'el tipo de entrega' },
    ];

    for (const item of camposRequeridos) {
      if (!item.campo || item.campo.toString().trim() === '') {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Campos vacíos',
          Texto: `Por favor ingrese ${item.nombre} para continuar.`,
          Tipo: 'warning',
        });
        return;
      }
    }

    if (
      this.entregaSeleccionada === 'archivo' &&
      !this.tipoArchivoSeleccionado
    ) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Tipo de archivo no seleccionado',
        Texto: 'Por favor seleccione un tipo de archivo para continuar.',
        Tipo: 'warning',
      });
      return;
    }
    if (
      this.contenidoEditandoTareas.fecha_entrega <
      this.contenidoEditandoTareas.fecha_inicio
    ) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Fecha de entrega inválida',
        Texto:
          'La fecha de entrega no puede ser anterior a la fecha de inicio.',
        Tipo: 'warning',
      });
      return;
    }

    if (
      this.contenidoEditandoTareas.fecha_limite_entrega <
      this.contenidoEditandoTareas.fecha_entrega
    ) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Fecha límite inválida',
        Texto:
          'La fecha límite de entrega no puede ser anterior a la fecha de entrega.',
        Tipo: 'warning',
      });
      return;
    }
    if (
      this.contenidoEditandoTareas.fecha_limite_entrega <
      this.contenidoEditandoTareas.fecha_inicio
    ) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Fecha límite inválida',
        Texto:
          'La fecha límite de entrega no puede ser anterior a la fecha de inicio.',
        Tipo: 'warning',
      });
      return;
    }

    // crear formData para enviar archivos si es necesario
    const formData = new FormData();

    // Agregar archivos al FormData
    this.archivosEducativos.forEach((a) => {
      formData.append('archivos[]', a.file);
    });

    formData.append('titulo_tarea', this.contenidoEditandoTareas.titulo_tarea);
    formData.append(
      'contenido_tarea',
      this.contenidoEditandoTareas.descripcion
    );
    formData.append(
      'fecha_entrega',
      this.contenidoEditandoTareas.fecha_entrega
    );
    formData.append('fecha_inicio', this.contenidoEditandoTareas.fecha_inicio);
    formData.append(
      'fecha_limite_entrega',
      this.contenidoEditandoTareas.fecha_limite_entrega
    );
    formData.append(
      'recordarme_calificar',
      this.contenidoEditandoTareas.recordarme_calificar
    );
    formData.append('tipo_entrega', this.entregaSeleccionada);
    formData.append('tipo_archivo_seleccionado', this.tipoArchivoSeleccionado);
    formData.append('es_grupal', this.esGrupal.toString());
    formData.append('id_tema', this.temaSeleccionado.id_tema);
    formData.append('documento_profesor', this.usuario?.documento);
    formData.append(
      'codigo_institucion',
      this.sede?.codigo_institucion_encriptado
    );
    formData.append('id_sede', this.sede?.id_sede_encriptado);
    formData.append('id_contenido', id_contenido.toString());
    formData.append('id_taller', id_taller.toString());

    this.materiaService.EditarTallereEducativos(formData).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.consultarInformaTalleresEducativas(id_contenido);
        this.consultar_contenido_tema_seleccionado();
        // Limpiar campos después de guardar
        this.entregaSeleccionada = '';
        this.tipoArchivoSeleccionado = '';
        this.archivosEducativos = []; // Limpiar archivos

        this.socketService.enviarEvento({
          tipo: 'TallerEditado',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          id_contenido: id_contenido, // 👈 importante para que el receptor sepa qué actualizar
          timestamp: Date.now(),
        });
      },
      error: (error) => {
        if (error.error && error.error.Titulo) {
          this.alertaService.mostrarAlerta(error.error);
        } else {
          this.alertaService.mostrarAlerta({
            Alerta: 'simple',
            Titulo: 'Error de servidor',
            Texto: 'No se pudo procesar tu solicitud',
            Tipo: 'error',
          });
        }
      },
    });
  }

  eliminarTallerEducativos(contenido: any) {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold; color: var(--color-primario);  margin-bottom: -12px;">¿Estás seguro?</h1>',
      text: '¿Deseas eliminar el taller ' + contenido.titulo + ' ?',
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
        const datostemas = {
          id_tema: this.temaSeleccionado.id_tema,
          codigo_institucion: this.sede?.codigo_institucion_encriptado,
          id_sede: this.sede?.id_sede_encriptado,
          id_contenido: contenido.id_contenido,
          documento_profesor: this.usuario?.documento,
        };

        this.materiaService.EliminarTalleresEducativos(datostemas).subscribe({
          // lo masndamos al servicio eliminar texto, porque tambien nos sirve para eliminar videos
          next: (respuesta) => {
            this.alertaService.mostrarAlerta(respuesta);
            this.consultar_contenido_tema_seleccionado();

            this.socketService.enviarEvento({
              tipo: 'TallerEliminado',
              id_materia: this.id_materia,
              id_grado: this.id_grado,
              id_grupo: this.id_grupo,
              timestamp: Date.now(),
            });
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

  /*********************crear foros educativos********************** */

  InformacionForo: any = {};
  yaParticipo: boolean = false;

  nuevaParticipacion = {
    titulo: '',
    respuesta: '',
  };

  decodeHTML(html: string): SafeHtml {
    if (!html) return this.sanitizer.bypassSecurityTrustHtml('');
    const cleanedHtml = html.replace(/&nbsp;/g, ' ');
    return this.sanitizer.bypassSecurityTrustHtml(cleanedHtml);
  }

  descripcionEsLarga(descripcion: string): boolean {
    const clean = descripcion.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ');
    return clean.length > 400; // Puedes ajustar el umbral según lo que consideres “largo”
  }

  guardarForo() {
    if (!this.nuevoTexto.titulo.trim()) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'campos vacios',
        Texto: 'por favor ingrese un titulo para poder continuar',
        Tipo: 'warning',
      });
      return;
    }

    if (!this.nuevoTexto.contenido.trim()) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'campos vacios',
        Texto: 'por favor ingrese un contenido para poder continuar',
        Tipo: 'warning',
      });
      return;
    }

    const datos_texto = {
      titulo_texto: this.nuevoTexto.titulo,
      contenido: this.nuevoTexto.contenido,
      id_tema: this.temaSeleccionado.id_tema,
      tipo_contenido: 'foro',
      documento_profesor: this.usuario?.documento,
    };

    this.materiaService.CreartextosEducativos(datos_texto).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.consultar_contenido_tema_seleccionado();
        // Limpiar campos después de guardar
        this.nuevoTexto = { titulo: '', contenido: '' };

        this.socketService.enviarEvento({
          tipo: 'NuevoForoRegistrado',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          timestamp: Date.now(),
        });
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

  AbrirAffcaRevisarForos(contenido: any) {
    this.InformacionForo = { ...contenido };
    const offcanvasElement = document.getElementById(
      'offcanvasBottomRevisarForo'
    );
    const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
    offcanvas.show();
    this.ObtenerDiscucionesFrosos();
  }

  enviarParticipacion() {
    const camposRequeridos = [
      {
        campo: this.nuevaParticipacion.titulo,
        nombre: 'el título del foro',
      },
      {
        campo: this.nuevaParticipacion.respuesta,
        nombre: 'la respuesta',
      },
    ];

    for (const item of camposRequeridos) {
      if (!item.campo || item.campo.toString().trim() === '') {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Campos vacíos',
          Texto: `Por favor ingrese ${item.nombre} para continuar.`,
          Tipo: 'warning',
        });
        return;
      }
    }

    const formData = new FormData();
    formData.append('titulo_discusion', this.nuevaParticipacion.titulo);
    formData.append('descripcion', this.nuevaParticipacion.respuesta);
    formData.append('creado_por', this.usuario?.documento);
    formData.append(
      'codigo_institucion',
      this.sede?.codigo_institucion_encriptado
    );
    formData.append('id_sede', this.sede?.id_sede_encriptado);
    formData.append('id_grado', this.id_grado);
    formData.append('id_grupo', this.id_grupo);
    formData.append('id_materia', this.id_materia);
    formData.append('id_contenido', this.InformacionForo.id_contenido);
    formData.append('id_tema', this.temaSeleccionado.id_tema);

    this.servicioEstudiantes.ContestarForosUsuarios(formData).subscribe({
      // sirve para crear talleres tambien
      next: (respuesta: any) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.nuevaParticipacion = {
          titulo: '',
          respuesta: '',
        };
        this.ObtenerDiscucionesFrosos();

        // ✅ Notificar a los demás usuarios que hay una nueva discusión
        this.socketService.enviarEvento({
          tipo: 'discusion_creada',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          timestamp: Date.now(),
        });
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

  listaDiscuciones: any[] = [];

  ObtenerDiscucionesFrosos() {
    const datos = {
      id_contenido: this.InformacionForo.id_contenido,
      id_tema: this.temaSeleccionado.id_tema,
      id_materia: this.id_materia,
      id_grupo: this.id_grupo,
      id_grado: this.id_grado,
      codigo_institucion: this.sede?.codigo_institucion_encriptado,
      id_sede: this.sede?.id_sede_encriptado,
    };

    this.servicioEstudiantes.ConsultarDiscucionesForos(datos).subscribe({
      next: (respuesta: any) => {
        // Añadir campos para UI
        this.listaDiscuciones = respuesta.map((item: any) => ({
          ...item,
          mostrarFormulario: false,
          respuestaTexto: '',
          expandido: false, // Para manejar la expansión de la discusión
          modoEdicion: false, // Para manejar el modo de edición
        }));
        // 🔎 Verifica si el usuario ya tiene una discusión
        this.yaParticipo = this.listaDiscuciones.some(
          (d: any) => d.creado_por === this.usuario?.documento
        );
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error al consultar discusiones',
          Texto: 'No se pudo consultar las discusiones del foro',
          Tipo: 'error',
        });
      },
    });
  }

  mostrarRespuesta(discusion: any) {
    discusion.mostrarFormulario = true;
  }

  enviarRespuesta(discusion: any) {
    const texto = discusion.respuestaTexto?.trim();
    if (!texto) return;

    const payload = {
      id_discusion: discusion.id_discusion,
      texto: texto,
      creado_por: this.usuario?.documento, // o como lo manejes tú
    };

    this.servicioEstudiantes.ResponderForosEstudiantes(payload).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        discusion.mostrarFormulario = false;
        discusion.respuestaTexto = '';
        this.ObtenerDiscucionesFrosos(); // Refrescar la lista de discusiones

        // 🔄 Enviar mensaje a todos los demás clientes vía WebSocket
        this.socketService.enviarEvento({
          tipo: 'comentario_creado',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          timestamp: Date.now(),
        });
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error al enviar respuesta',
          Texto: 'No se pudo enviar tu respuesta jhon',
          Tipo: 'error',
        });
      },
    });
  }

  eliminarDiscusion(discusion: any) {
    Swal.fire({
      title:
        '<h1 style="font-size: 28px; font-weight: bold;">¿Estás seguro?</h1>',
      text: '¿Esta acción eliminará la discusión y todos sus comentarios?',
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
        const payload = { id_discusion: discusion.id_discusion };

        this.servicioEstudiantes.EliminarDiscusion(payload).subscribe({
          next: (respuesta: any) => {
            this.alertaService.mostrarAlerta(respuesta);

            // Actualiza en este navegador
            this.listaDiscuciones = this.listaDiscuciones.filter(
              (d) => d.id_discusion !== discusion.id_discusion
            );

            // Notifica a los demás usuarios
            this.socketService.enviarEvento({
              tipo: 'discusion_eliminada',
              id_materia: this.id_materia,
              id_grado: this.id_grado,
              id_grupo: this.id_grupo,
              timestamp: Date.now(),
            });
          },
          error: () => {
            this.alertaService.mostrarAlerta({
              Alerta: 'simple',
              Titulo: 'Error',
              Texto: 'No se pudo eliminar la discusión',
              Tipo: 'error',
            });
          },
        });
      }
    });
  }

  mostrandoCollage = false;
  discusionEnEdicion: any = {};

  abrirCollageDiscusion(discusion: any) {
    this.mostrandoCollage = true;
    this.discusionEnEdicion = {
      id_discusion: discusion.id_discusion,
      tituloEditado: discusion.titulo_discusion,
      descripcionEditada: discusion.descripcion,
    };
  }

  cerrarCollage() {
    this.mostrandoCollage = false;
  }

  guardarActualizacionDiscusion() {
    const titulo = this.discusionEnEdicion.tituloEditado?.trim();
    const descripcion = this.discusionEnEdicion.descripcionEditada?.trim();

    // ✅ Validación de campos vacíos
    if (!titulo || !descripcion) {
      this.alertaService.mostrarAlerta({
        Alerta: 'simple',
        Titulo: 'Campos vacíos',
        Texto: 'Por favor ingresa tanto el título como la descripción.',
        Tipo: 'warning',
      });
      return;
    }

    const payload = {
      id_discusion: this.discusionEnEdicion.id_discusion,
      titulo: titulo,
      descripcion: descripcion,
      creado_por: this.usuario?.documento,
    };

    this.servicioEstudiantes.ActualizarDiscusion(payload).subscribe({
      next: (respuesta) => {
        this.alertaService.mostrarAlerta(respuesta);
        this.ObtenerDiscucionesFrosos();
        this.cerrarCollage();
        // 🔄 Notificar a los demás usuarios que la discusión ha sido actualizada

        this.socketService.enviarEvento({
          tipo: 'discusion_actualizada',
          id_materia: this.id_materia,
          id_grado: this.id_grado,
          id_grupo: this.id_grupo,
          timestamp: Date.now(),
        });
      },
      error: () => {
        this.alertaService.mostrarAlerta({
          Alerta: 'simple',
          Titulo: 'Error',
          Texto: 'No se pudo actualizar la discusión',
          Tipo: 'error',
        });
      },
    });
  }
}
