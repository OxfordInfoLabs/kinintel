import {Component, Inject, Input, OnInit} from '@angular/core';
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {ProjectService} from '../../services/project.service';
import {TagService} from '../../services/tag.service';

@Component({
    selector: 'ki-project-picker',
    templateUrl: './project-picker.component.html',
    styleUrls: ['./project-picker.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class ProjectPickerComponent implements OnInit {

    public projects: any = [];
    public addNew = false;
    public newName;
    public newDescription;
    public searchText = new BehaviorSubject<string>('');
    public reload = new Subject();
    public activeProject: any = {};
    public isAdmin = false;

    constructor(public dialogRef: MatDialogRef<ProjectPickerComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private projectService: ProjectService,
                private tagService: TagService) {
    }

    ngOnInit(): void {
        this.isAdmin = !!this.data.isAdmin;
        this.activeProject = this.projectService.activeProject.getValue();

        merge(this.searchText, this.reload).pipe(
            debounceTime(300),
            distinctUntilChanged(),
            switchMap(() =>
                this.getProjects()
            )
        ).subscribe((projects: any) => {
            this.projects = projects;
        });
    }

    public createProject() {
        this.projectService.createProject(this.newName, this.newDescription).then(() => {
            this.newName = '';
            this.newDescription = '';
            this.addNew = false;
            this.reload.next(Date.now());
        });
    }

    public removeProject(key) {
        const message = 'Are you sure you would like to remove this project?';
        if (window.confirm(message)) {
            this.projectService.removeProject(key).then(() => {
                if (this.projectService.activeProject.getValue() &&
                    this.projectService.activeProject.getValue().projectKey === key) {
                    this.projectService.resetActiveProject();
                }
                this.reload.next(Date.now());
            });
        }
    }

    public activateProject(project) {
        this.projectService.setActiveProject(project);
        this.tagService.resetActiveTag();
        this.dialogRef.close(project);
    }

    private getProjects() {
        return this.projectService.getProjects(
            this.searchText.getValue()
        ).pipe(map((projects: any) => {
                return projects;
            })
        );
    }

}
