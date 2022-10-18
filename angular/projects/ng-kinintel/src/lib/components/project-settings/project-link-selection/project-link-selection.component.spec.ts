import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ProjectLinkSelectionComponent } from './project-link-selection.component';

describe('ProjectLinkSelectionComponent', () => {
  let component: ProjectLinkSelectionComponent;
  let fixture: ComponentFixture<ProjectLinkSelectionComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ProjectLinkSelectionComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ProjectLinkSelectionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
