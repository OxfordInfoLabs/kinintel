import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NotificationGroupsComponent } from './notification-groups.component';

describe('NotificationGroupsComponent', () => {
  let component: NotificationGroupsComponent;
  let fixture: ComponentFixture<NotificationGroupsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NotificationGroupsComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(NotificationGroupsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
