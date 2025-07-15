import { TestBed } from '@angular/core/testing';

import { Estudiantes } from './estudiantes';

describe('Estudiantes', () => {
  let service: Estudiantes;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Estudiantes);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
