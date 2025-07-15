import { TestBed } from '@angular/core/testing';

import { Imagenes } from './imagenes';

describe('Imagenes', () => {
  let service: Imagenes;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Imagenes);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
