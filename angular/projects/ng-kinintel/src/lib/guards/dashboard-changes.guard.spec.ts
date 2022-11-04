import { TestBed } from '@angular/core/testing';

import { DashboardChangesGuard } from './dashboard-changes.guard';

describe('DashboardChangesGuard', () => {
  let guard: DashboardChangesGuard;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    guard = TestBed.inject(DashboardChangesGuard);
  });

  it('should be created', () => {
    expect(guard).toBeTruthy();
  });
});
