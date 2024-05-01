import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EditQueryCacheComponent } from './edit-query-cache.component';

describe('EditQueryCacheComponent', () => {
  let component: EditQueryCacheComponent;
  let fixture: ComponentFixture<EditQueryCacheComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EditQueryCacheComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EditQueryCacheComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
