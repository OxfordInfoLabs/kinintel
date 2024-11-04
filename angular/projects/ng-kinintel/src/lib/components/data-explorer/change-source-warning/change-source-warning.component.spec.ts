import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ChangeSourceWarningComponent } from './change-source-warning.component';

describe('ChangeSourceWarningComponent', () => {
  let component: ChangeSourceWarningComponent;
  let fixture: ComponentFixture<ChangeSourceWarningComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ChangeSourceWarningComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ChangeSourceWarningComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
