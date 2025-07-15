import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EditorText } from './editor-text';

describe('EditorText', () => {
  let component: EditorText;
  let fixture: ComponentFixture<EditorText>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [EditorText]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EditorText);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
