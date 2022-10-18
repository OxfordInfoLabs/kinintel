import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UpstreamChangesConfirmationComponent } from './upstream-changes-confirmation.component';

describe('UpstreamChangesConfirmationComponent', () => {
  let component: UpstreamChangesConfirmationComponent;
  let fixture: ComponentFixture<UpstreamChangesConfirmationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ UpstreamChangesConfirmationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(UpstreamChangesConfirmationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
