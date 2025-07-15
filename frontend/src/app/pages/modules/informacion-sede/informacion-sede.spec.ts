import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InformacionSede } from './informacion-sede';

describe('InformacionSede', () => {
  let component: InformacionSede;
  let fixture: ComponentFixture<InformacionSede>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [InformacionSede]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InformacionSede);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
