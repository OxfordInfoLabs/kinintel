import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DatasetColumnSettingsComponent } from './dataset-column-settings.component';

describe('DatasetColumnSettingsComponent', () => {
  let component: DatasetColumnSettingsComponent;
  let fixture: ComponentFixture<DatasetColumnSettingsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DatasetColumnSettingsComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DatasetColumnSettingsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
