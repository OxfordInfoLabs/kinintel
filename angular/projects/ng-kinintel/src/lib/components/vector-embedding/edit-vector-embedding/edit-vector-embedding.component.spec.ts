import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EditVectorEmbeddingComponent } from './edit-vector-embedding.component';

describe('EditVectorEmbeddingComponent', () => {
  let component: EditVectorEmbeddingComponent;
  let fixture: ComponentFixture<EditVectorEmbeddingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EditVectorEmbeddingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EditVectorEmbeddingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
