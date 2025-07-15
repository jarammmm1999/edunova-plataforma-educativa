import { TestBed } from '@angular/core/testing';

import { Alertas } from './alertas';

describe('Alertas', () => {
  let service: Alertas;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Alertas);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
