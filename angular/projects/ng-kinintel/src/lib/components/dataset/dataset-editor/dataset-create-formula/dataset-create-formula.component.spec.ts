import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DatasetCreateFormulaComponent } from './dataset-create-formula.component';

describe('DatasetCreateFormulaComponent', () => {
  let component: DatasetCreateFormulaComponent;
  let fixture: ComponentFixture<DatasetCreateFormulaComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DatasetCreateFormulaComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DatasetCreateFormulaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
