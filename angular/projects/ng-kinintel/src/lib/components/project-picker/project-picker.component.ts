import {Component, Inject, Input, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';

@Component({
    selector: 'ki-project-picker',
    templateUrl: './project-picker.component.html',
    styleUrls: ['./project-picker.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class ProjectPickerComponent implements OnInit {


    public projectService: any;
    public projects: any = [];
    public addNew = false;
    public newName;
    public newDescription;
    public searchText = new BehaviorSubject<string>('');
    public reload = new Subject();
    public activeProject: any = {};


    constructor(public dialogRef: MatDialogRef<ProjectPickerComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        this.projectService = this.data.projectService;

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
        this.dialogRef.close();
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
