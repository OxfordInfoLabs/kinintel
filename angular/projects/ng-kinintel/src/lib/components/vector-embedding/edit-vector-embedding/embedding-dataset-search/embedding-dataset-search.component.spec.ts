import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EmbeddingDatasetSearchComponent } from './embedding-dataset-search.component';

describe('EmbeddingDatasetSearchComponent', () => {
  let component: EmbeddingDatasetSearchComponent;
  let fixture: ComponentFixture<EmbeddingDatasetSearchComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EmbeddingDatasetSearchComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EmbeddingDatasetSearchComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
