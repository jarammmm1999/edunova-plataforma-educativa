
export interface UsuarioModel {
  nombres: string;
  correo: string;
  telefono: string;
  id_sexo: number;
  imagen: string;
  estado: string;
}


export interface InformacionRegistroModel {
  documento: string;
  nombre: string;
  correo: string;
  telefono: string;
  sexo: string;
  contrasena: string;
  confirmContrasena: string;
  estado: string;
  rol: string;
  grado: string;
  grupo: string;
  acudientes: any;
  documentos: any;
  imagen: File | null;
  id_sede: string;
  codigo_institucion: string;
}


