import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MoveTransformationConfirmationComponent } from './move-transformation-confirmation.component';

describe('MoveTransformationConfirmationComponent', () => {
  let component: MoveTransformationConfirmationComponent;
  let fixture: ComponentFixture<MoveTransformationConfirmationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MoveTransformationConfirmationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MoveTransformationConfirmationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
