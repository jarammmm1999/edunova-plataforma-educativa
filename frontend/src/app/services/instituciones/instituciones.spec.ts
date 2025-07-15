import { TestBed } from '@angular/core/testing';

import { Instituciones } from './instituciones';

describe('Instituciones', () => {
  let service: Instituciones;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Instituciones);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
