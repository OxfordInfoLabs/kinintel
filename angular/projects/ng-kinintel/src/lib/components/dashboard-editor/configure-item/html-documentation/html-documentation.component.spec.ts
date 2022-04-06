import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HtmlDocumentationComponent } from './html-documentation.component';

describe('HtmlDocumentationComponent', () => {
  let component: HtmlDocumentationComponent;
  let fixture: ComponentFixture<HtmlDocumentationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ HtmlDocumentationComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(HtmlDocumentationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
