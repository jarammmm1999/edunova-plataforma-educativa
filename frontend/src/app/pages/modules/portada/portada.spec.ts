import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Portada } from './portada';

describe('Portada', () => {
  let component: Portada;
  let fixture: ComponentFixture<Portada>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Portada]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Portada);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
