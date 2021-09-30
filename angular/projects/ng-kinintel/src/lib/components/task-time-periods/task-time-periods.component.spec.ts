import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TaskTimePeriodsComponent } from './task-time-periods.component';

describe('TaskTimePeriodsComponent', () => {
  let component: TaskTimePeriodsComponent;
  let fixture: ComponentFixture<TaskTimePeriodsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TaskTimePeriodsComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TaskTimePeriodsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
