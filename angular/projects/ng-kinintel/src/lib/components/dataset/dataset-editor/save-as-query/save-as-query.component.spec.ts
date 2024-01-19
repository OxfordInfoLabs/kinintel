import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SaveAsQueryComponent } from './save-as-query.component';

describe('SaveAsQueryComponent', () => {
  let component: SaveAsQueryComponent;
  let fixture: ComponentFixture<SaveAsQueryComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SaveAsQueryComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SaveAsQueryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
