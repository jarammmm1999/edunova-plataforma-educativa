import { Routes } from '@angular/router';
import { Instituciones } from './pages/instituciones/instituciones';
import { Sede } from './pages/sede/sede';
import { Login } from './pages/login/login';
import { LayoutComponent } from './layout/layout';
import { Home } from './pages/home/home';
import { Bienvenida } from './pages/modules/bienvenida/bienvenida';
import { RegistrarUsuarios } from './pages/modules/registrar-usuarios/registrar-usuarios';
import { ConsultarUsuarios } from './pages/modules/consultar-usuarios/consultar-usuarios';
import { ConfiguracionSistema } from './pages/modules/configuracion-sistema/configuracion-sistema';
import { MateriasAcademicas } from './pages/modules/materias-academicas/materias-academicas';
import { PeriodosAcademicos } from './pages/modules/periodos-academicos/periodos-academicos';
import { Portada } from './pages/modules/portada/portada';
import { GradosAcademicos } from './pages/modules/grados-academicos/grados-academicos';
import { AsignarMateriasProfesores } from './pages/modules/asignar-materias-profesores/asignar-materias-profesores';
import { PerfilUsuario } from './pages/modules/perfil-usuario/perfil-usuario';
import { InformacionSede } from './pages/modules/informacion-sede/informacion-sede';
import { Materia } from './pages/modules/profesores/materia/materia';

export const routes: Routes = [
  { path: '', redirectTo: 'instituciones', pathMatch: 'full' },
  { path: 'instituciones', component: Instituciones },
  { path: 'sede/:id', component: Sede },
  { path: 'login/:id', component: Login },
  // ✅ Layout general con rutas hijas
  {
    path: '',
    component: LayoutComponent,
    children: [
      {
        path: 'home',
        component: Home,
        children: [
          { path: '', redirectTo: 'inicio', pathMatch: 'full' },
          { path: 'inicio', component: Bienvenida },
          { path: 'registrar-usuarios', component: RegistrarUsuarios },
          { path: 'consultar-usuarios', component: ConsultarUsuarios },
          { path: 'configuracion-sistema', component: ConfiguracionSistema },
          { path: 'materias-academicas', component: MateriasAcademicas },
          { path: 'periodos-academicos', component: PeriodosAcademicos },
          { path: 'portada', component: Portada },
          { path: 'grados-academicos', component: GradosAcademicos },
          {
            path: 'asignar-materias-profesores',
            component: AsignarMateriasProfesores,
          },
          { path: 'perfil-usuario', component: PerfilUsuario },
          { path: 'informacion-sede', component: InformacionSede },
          { path: 'materia/:idMateria/:idGrado/:idGrupo', component: Materia },
        ],
      },
    ],
  },
];


