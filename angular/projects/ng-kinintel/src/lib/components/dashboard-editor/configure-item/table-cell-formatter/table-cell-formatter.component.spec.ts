import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TableCellFormatterComponent } from './table-cell-formatter.component';

describe('TableCellFormatterComponent', () => {
  let component: TableCellFormatterComponent;
  let fixture: ComponentFixture<TableCellFormatterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TableCellFormatterComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TableCellFormatterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
