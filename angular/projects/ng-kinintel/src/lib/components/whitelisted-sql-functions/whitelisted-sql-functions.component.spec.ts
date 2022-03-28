import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WhitelistedSqlFunctionsComponent } from './whitelisted-sql-functions.component';

describe('WhitelistedSqlFunctionsComponent', () => {
  let component: WhitelistedSqlFunctionsComponent;
  let fixture: ComponentFixture<WhitelistedSqlFunctionsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WhitelistedSqlFunctionsComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(WhitelistedSqlFunctionsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
