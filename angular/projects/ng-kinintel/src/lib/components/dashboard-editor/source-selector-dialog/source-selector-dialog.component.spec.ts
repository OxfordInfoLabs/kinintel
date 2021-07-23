import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SourceSelectorDialogComponent } from './source-selector-dialog.component';

describe('SourceSelectorDialogComponent', () => {
  let component: SourceSelectorDialogComponent;
  let fixture: ComponentFixture<SourceSelectorDialogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SourceSelectorDialogComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(SourceSelectorDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
