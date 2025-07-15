import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Comentarios } from './comentarios';

describe('Comentarios', () => {
  let component: Comentarios;
  let fixture: ComponentFixture<Comentarios>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Comentarios]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Comentarios);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
