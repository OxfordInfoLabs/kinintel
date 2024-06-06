import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DataSearchComponent } from './data-search.component';

describe('DataSearchComponent', () => {
  let component: DataSearchComponent;
  let fixture: ComponentFixture<DataSearchComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DataSearchComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DataSearchComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
