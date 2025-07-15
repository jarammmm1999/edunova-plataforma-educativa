import { TestBed } from '@angular/core/testing';

import { Sedes } from './sedes';

describe('Sedes', () => {
  let service: Sedes;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Sedes);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
