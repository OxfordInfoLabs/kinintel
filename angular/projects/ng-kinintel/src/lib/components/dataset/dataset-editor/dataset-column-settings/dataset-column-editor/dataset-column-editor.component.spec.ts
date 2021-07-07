import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DatasetColumnEditorComponent } from './dataset-column-editor.component';

describe('DatasetColumnEditorComponent', () => {
  let component: DatasetColumnEditorComponent;
  let fixture: ComponentFixture<DatasetColumnEditorComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DatasetColumnEditorComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DatasetColumnEditorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
