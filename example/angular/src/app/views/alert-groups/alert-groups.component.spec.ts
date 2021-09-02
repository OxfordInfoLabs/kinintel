import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AlertGroupsComponent } from './alert-groups.component';

describe('AlertGroupsComponent', () => {
  let component: AlertGroupsComponent;
  let fixture: ComponentFixture<AlertGroupsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AlertGroupsComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(AlertGroupsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
