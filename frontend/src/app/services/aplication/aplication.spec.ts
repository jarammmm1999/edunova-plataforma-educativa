import { TestBed } from '@angular/core/testing';

import { Aplication } from './aplication';

describe('Aplication', () => {
  let service: Aplication;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Aplication);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
