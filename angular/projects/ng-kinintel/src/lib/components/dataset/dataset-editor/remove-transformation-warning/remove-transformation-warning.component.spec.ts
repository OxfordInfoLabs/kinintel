import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RemoveTransformationWarningComponent } from './remove-transformation-warning.component';

describe('RemoveTransformationWarningComponent', () => {
  let component: RemoveTransformationWarningComponent;
  let fixture: ComponentFixture<RemoveTransformationWarningComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RemoveTransformationWarningComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RemoveTransformationWarningComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
