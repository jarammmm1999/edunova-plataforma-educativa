import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GradosAcademicos } from './grados-academicos';

describe('GradosAcademicos', () => {
  let component: GradosAcademicos;
  let fixture: ComponentFixture<GradosAcademicos>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [GradosAcademicos]
    })
    .compileComponents();

    fixture = TestBed.createComponent(GradosAcademicos);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
