import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DatasetAddJoinComponent } from './dataset-add-join.component';

describe('DatasetAddJoinComponent', () => {
  let component: DatasetAddJoinComponent;
  let fixture: ComponentFixture<DatasetAddJoinComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DatasetAddJoinComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DatasetAddJoinComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
