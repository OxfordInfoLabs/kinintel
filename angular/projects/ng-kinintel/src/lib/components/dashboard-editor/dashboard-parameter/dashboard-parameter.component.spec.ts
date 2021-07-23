import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DashboardParameterComponent } from './dashboard-parameter.component';

describe('DashboardParameterComponent', () => {
  let component: DashboardParameterComponent;
  let fixture: ComponentFixture<DashboardParameterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DashboardParameterComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DashboardParameterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
