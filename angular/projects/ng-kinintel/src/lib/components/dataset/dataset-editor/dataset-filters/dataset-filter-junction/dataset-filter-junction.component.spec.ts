import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DatasetFilterJunctionComponent } from './dataset-filter-junction.component';

describe('DatasetFilterJunctionComponent', () => {
  let component: DatasetFilterJunctionComponent;
  let fixture: ComponentFixture<DatasetFilterJunctionComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DatasetFilterJunctionComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DatasetFilterJunctionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
