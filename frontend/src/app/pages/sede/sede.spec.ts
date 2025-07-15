import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Sede } from './sede';

describe('Sede', () => {
  let component: Sede;
  let fixture: ComponentFixture<Sede>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Sede]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Sede);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
