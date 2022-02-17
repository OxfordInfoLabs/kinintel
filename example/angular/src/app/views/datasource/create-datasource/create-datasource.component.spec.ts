import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CreateDatasourceComponent } from './create-datasource.component';

describe('CreateDatasourceComponent', () => {
  let component: CreateDatasourceComponent;
  let fixture: ComponentFixture<CreateDatasourceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CreateDatasourceComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(CreateDatasourceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
