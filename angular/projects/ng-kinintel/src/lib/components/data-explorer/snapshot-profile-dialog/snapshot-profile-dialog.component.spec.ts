import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SnapshotProfileDialogComponent } from './snapshot-profile-dialog.component';

describe('SnapshotProfileDialogComponent', () => {
  let component: SnapshotProfileDialogComponent;
  let fixture: ComponentFixture<SnapshotProfileDialogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SnapshotProfileDialogComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(SnapshotProfileDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
