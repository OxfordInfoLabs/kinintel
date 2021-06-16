import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DatasetParameterTypeComponent } from './dataset-parameter-type.component';

describe('DatasetParameterTypeComponent', () => {
  let component: DatasetParameterTypeComponent;
  let fixture: ComponentFixture<DatasetParameterTypeComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DatasetParameterTypeComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DatasetParameterTypeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
