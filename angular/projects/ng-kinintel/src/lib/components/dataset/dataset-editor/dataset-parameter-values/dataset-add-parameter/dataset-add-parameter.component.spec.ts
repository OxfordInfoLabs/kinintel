import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DatasetAddParameterComponent } from './dataset-add-parameter.component';

describe('DatasetAddParameterComponent', () => {
  let component: DatasetAddParameterComponent;
  let fixture: ComponentFixture<DatasetAddParameterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DatasetAddParameterComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DatasetAddParameterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
