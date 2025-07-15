import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PeriodosAcademicos } from './periodos-academicos';

describe('PeriodosAcademicos', () => {
  let component: PeriodosAcademicos;
  let fixture: ComponentFixture<PeriodosAcademicos>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PeriodosAcademicos]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PeriodosAcademicos);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
