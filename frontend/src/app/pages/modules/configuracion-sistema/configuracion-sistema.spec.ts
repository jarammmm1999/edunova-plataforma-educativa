import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ConfiguracionSistema } from './configuracion-sistema';

describe('ConfiguracionSistema', () => {
  let component: ConfiguracionSistema;
  let fixture: ComponentFixture<ConfiguracionSistema>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ConfiguracionSistema]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ConfiguracionSistema);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
