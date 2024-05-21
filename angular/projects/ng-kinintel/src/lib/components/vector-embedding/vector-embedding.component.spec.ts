import { ComponentFixture, TestBed } from '@angular/core/testing';

import { VectorEmbeddingComponent } from './vector-embedding.component';

describe('VectorEmbeddingComponent', () => {
  let component: VectorEmbeddingComponent;
  let fixture: ComponentFixture<VectorEmbeddingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ VectorEmbeddingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(VectorEmbeddingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
