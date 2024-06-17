import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ShareQueryComponent } from './share-query.component';

describe('ShareQueryComponent', () => {
  let component: ShareQueryComponent;
  let fixture: ComponentFixture<ShareQueryComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ShareQueryComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ShareQueryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
