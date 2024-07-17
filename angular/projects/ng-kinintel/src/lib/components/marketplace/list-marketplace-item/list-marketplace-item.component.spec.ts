import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ListMarketplaceItemComponent } from './list-marketplace-item.component';

describe('ListMarketplaceItemComponent', () => {
  let component: ListMarketplaceItemComponent;
  let fixture: ComponentFixture<ListMarketplaceItemComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ListMarketplaceItemComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ListMarketplaceItemComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
