import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QueryCacheViewComponent } from './query-cache-view.component';

describe('QueryCacheViewComponent', () => {
  let component: QueryCacheViewComponent;
  let fixture: ComponentFixture<QueryCacheViewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QueryCacheViewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QueryCacheViewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
