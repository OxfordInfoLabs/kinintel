import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QueryCachingComponent } from './query-caching.component';

describe('QueryCachingComponent', () => {
  let component: QueryCachingComponent;
  let fixture: ComponentFixture<QueryCachingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QueryCachingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QueryCachingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
