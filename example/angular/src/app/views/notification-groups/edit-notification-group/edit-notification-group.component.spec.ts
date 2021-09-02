import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EditNotificationGroupComponent } from './edit-notification-group.component';

describe('EditNotificationGroupComponent', () => {
  let component: EditNotificationGroupComponent;
  let fixture: ComponentFixture<EditNotificationGroupComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EditNotificationGroupComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(EditNotificationGroupComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
