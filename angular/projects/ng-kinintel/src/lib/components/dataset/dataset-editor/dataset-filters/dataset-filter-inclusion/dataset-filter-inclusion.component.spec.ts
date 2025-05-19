import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DatasetFilterInclusionComponent } from './dataset-filter-inclusion.component';

describe('DatasetFilterInclusionComponent', () => {
  let component: DatasetFilterInclusionComponent;
  let fixture: ComponentFixture<DatasetFilterInclusionComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DatasetFilterInclusionComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DatasetFilterInclusionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
