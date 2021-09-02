import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EditAlertGroupComponent } from './edit-alert-group.component';

describe('EditAlertGroupComponent', () => {
  let component: EditAlertGroupComponent;
  let fixture: ComponentFixture<EditAlertGroupComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EditAlertGroupComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(EditAlertGroupComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
