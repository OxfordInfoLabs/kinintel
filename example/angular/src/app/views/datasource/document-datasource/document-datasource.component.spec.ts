import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DocumentDatasourceComponent } from './document-datasource.component';

describe('DocumentDatasourceComponent', () => {
  let component: DocumentDatasourceComponent;
  let fixture: ComponentFixture<DocumentDatasourceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DocumentDatasourceComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DocumentDatasourceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
