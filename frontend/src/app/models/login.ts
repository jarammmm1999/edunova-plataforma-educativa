export interface LoginModel {
  id_sede: String;
  id_sede_encriptado: String;
  codigo_institucion_encriptado: String;
  nombre_sede: string;
  direccion: string | null;
  telefono: string | null;
  logo_sede: string;
  colores_sede: { primario: string; secundario: string };
  codigo_institucion: number;
  activo: string;
}
