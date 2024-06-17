import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DataSharingInviteComponent } from './data-sharing-invite.component';

describe('DataSharingInviteComponent', () => {
  let component: DataSharingInviteComponent;
  let fixture: ComponentFixture<DataSharingInviteComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DataSharingInviteComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DataSharingInviteComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
