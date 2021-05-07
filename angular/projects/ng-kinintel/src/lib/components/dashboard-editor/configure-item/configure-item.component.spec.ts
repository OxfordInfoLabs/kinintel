import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ConfigureItemComponent } from './configure-item.component';

describe('ConfigureItemComponent', () => {
  let component: ConfigureItemComponent;
  let fixture: ComponentFixture<ConfigureItemComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ConfigureItemComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ConfigureItemComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
