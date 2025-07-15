import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Textos } from './textos';

describe('Textos', () => {
  let component: Textos;
  let fixture: ComponentFixture<Textos>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Textos]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Textos);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
