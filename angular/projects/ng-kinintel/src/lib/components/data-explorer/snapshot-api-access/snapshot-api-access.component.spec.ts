import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SnapshotApiAccessComponent } from './snapshot-api-access.component';

describe('SnapshotApiAccessComponent', () => {
  let component: SnapshotApiAccessComponent;
  let fixture: ComponentFixture<SnapshotApiAccessComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SnapshotApiAccessComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SnapshotApiAccessComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
