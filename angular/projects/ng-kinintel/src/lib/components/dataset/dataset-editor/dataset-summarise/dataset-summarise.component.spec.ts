import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DatasetSummariseComponent } from './dataset-summarise.component';

describe('DatasetSummariseComponent', () => {
  let component: DatasetSummariseComponent;
  let fixture: ComponentFixture<DatasetSummariseComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DatasetSummariseComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DatasetSummariseComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
