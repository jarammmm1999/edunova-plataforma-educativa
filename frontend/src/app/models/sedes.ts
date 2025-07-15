export interface SedeModel {
  id_sede: string;
  nombre_sede: string;
  direccion: string;
  telefono: string;
  logo_sede: string;
  colores_sede: { primario: string; secundario: string };
  activo: number;
  codigo_institucion: number;
  created_at: string | null;
  updated_at: string | null;
}
