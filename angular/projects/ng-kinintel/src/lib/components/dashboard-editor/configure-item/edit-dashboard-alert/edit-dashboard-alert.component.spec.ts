import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EditDashboardAlertComponent } from './edit-dashboard-alert.component';

describe('EditDashboardAlertComponent', () => {
  let component: EditDashboardAlertComponent;
  let fixture: ComponentFixture<EditDashboardAlertComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EditDashboardAlertComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(EditDashboardAlertComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
