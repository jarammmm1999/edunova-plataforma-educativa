import { TestBed } from '@angular/core/testing';

import { Profesores } from './profesores';

describe('Profesores', () => {
  let service: Profesores;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Profesores);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
