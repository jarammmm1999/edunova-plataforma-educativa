import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AsignarMateriasProfesores } from './asignar-materias-profesores';

describe('AsignarMateriasProfesores', () => {
  let component: AsignarMateriasProfesores;
  let fixture: ComponentFixture<AsignarMateriasProfesores>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AsignarMateriasProfesores]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AsignarMateriasProfesores);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
