import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AvailableColumnsComponent } from './available-columns.component';

describe('AvailableColumnsComponent', () => {
  let component: AvailableColumnsComponent;
  let fixture: ComponentFixture<AvailableColumnsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AvailableColumnsComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(AvailableColumnsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
