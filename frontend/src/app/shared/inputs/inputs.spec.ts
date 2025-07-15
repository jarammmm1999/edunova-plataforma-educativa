import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Inputs } from './inputs';

describe('Inputs', () => {
  let component: Inputs;
  let fixture: ComponentFixture<Inputs>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Inputs]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Inputs);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
