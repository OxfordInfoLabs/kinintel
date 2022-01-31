import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {HttpClient} from '@angular/common/http';
import {ProjectService} from '../../services/project.service';
import * as _ from 'lodash';

@Component({
    selector: 'ki-metadata',
    templateUrl: './metadata.component.html',
    styleUrls: ['./metadata.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class MetadataComponent implements OnInit {

    public metadata: any = {};
    public categories: any = [];
    public metadataService: any;
    public newCategory: any = {};

    private projectKey = '';

    constructor(public dialogRef: MatDialogRef<MetadataComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private http: HttpClient,
                private projectService: ProjectService) {
    }

    ngOnInit(): void {
        this.metadata = this.data.metadata;
        this.metadataService = this.data.service;
        this.projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';


        this.loadCategories();
    }

    public showSelected(o1: any, o2: any) {
        return o1 && o2 && o1.key === o2.key;
    }

    public addCategory() {

        this.newCategory.key = _.camelCase(this.newCategory.category);

        this.http.post('/account/metadata/category?projectKey=' + this.projectKey, this.newCategory).toPromise()
            .then(() => {
                this.loadCategories().then(() => {
                    this.metadata.categories.push(this.newCategory);
                    this.newCategory = {};
                });
            });
    }

    public saveMetadata() {
        this.metadataService.updateMetadata(this.metadata).then(res => {
            this.dialogRef.close(true);
        });
    }

    private loadCategories() {
        return this.http.get('/account/metadata/category?projectKey=' + this.projectKey, {
            params: {limit: '100'}
        }).toPromise().then(categories => this.categories = categories);
    }

}
