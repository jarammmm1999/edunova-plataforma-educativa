import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RegistrarUsuarios } from './registrar-usuarios';

describe('RegistrarUsuarios', () => {
  let component: RegistrarUsuarios;
  let fixture: ComponentFixture<RegistrarUsuarios>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [RegistrarUsuarios]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RegistrarUsuarios);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
