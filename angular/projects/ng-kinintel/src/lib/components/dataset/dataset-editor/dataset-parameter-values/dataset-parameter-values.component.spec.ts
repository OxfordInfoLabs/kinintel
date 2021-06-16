import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DatasetParameterValuesComponent } from './dataset-parameter-values.component';

describe('DatasetParameterValuesComponent', () => {
  let component: DatasetParameterValuesComponent;
  let fixture: ComponentFixture<DatasetParameterValuesComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DatasetParameterValuesComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DatasetParameterValuesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
