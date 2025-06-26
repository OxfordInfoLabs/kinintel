import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FeedApiModalComponent } from './feed-api-modal.component';

describe('FeedApiModalComponent', () => {
  let component: FeedApiModalComponent;
  let fixture: ComponentFixture<FeedApiModalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FeedApiModalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FeedApiModalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
