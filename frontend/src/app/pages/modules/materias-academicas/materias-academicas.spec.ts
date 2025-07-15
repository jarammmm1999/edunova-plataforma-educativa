import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MateriasAcademicas } from './materias-academicas';

describe('MateriasAcademicas', () => {
  let component: MateriasAcademicas;
  let fixture: ComponentFixture<MateriasAcademicas>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MateriasAcademicas]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MateriasAcademicas);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
