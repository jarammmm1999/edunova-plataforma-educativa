import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Instituciones } from './instituciones';

describe('Instituciones', () => {
  let component: Instituciones;
  let fixture: ComponentFixture<Instituciones>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Instituciones]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Instituciones);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
