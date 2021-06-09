import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DatasetNameDialogComponent } from './dataset-name-dialog.component';

describe('DatasetNameDialogComponent', () => {
  let component: DatasetNameDialogComponent;
  let fixture: ComponentFixture<DatasetNameDialogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DatasetNameDialogComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DatasetNameDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
