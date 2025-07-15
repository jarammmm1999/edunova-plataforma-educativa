import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ButtonSubmit } from './button-submit';

describe('ButtonSubmit', () => {
  let component: ButtonSubmit;
  let fixture: ComponentFixture<ButtonSubmit>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ButtonSubmit]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ButtonSubmit);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
