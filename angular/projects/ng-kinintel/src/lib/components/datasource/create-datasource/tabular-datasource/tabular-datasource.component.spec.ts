import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TabularDatasourceComponent } from './tabular-datasource.component';

describe('TabularDatasourceComponent', () => {
  let component: TabularDatasourceComponent;
  let fixture: ComponentFixture<TabularDatasourceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TabularDatasourceComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TabularDatasourceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
