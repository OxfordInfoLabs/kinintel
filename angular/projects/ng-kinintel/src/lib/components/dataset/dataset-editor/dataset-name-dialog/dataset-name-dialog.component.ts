import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import { HttpClient } from '@angular/common/http';
import {ProjectService} from '../../../../services/project.service';

@Component({
    selector: 'ki-dataset-name-dialog',
    templateUrl: './dataset-name-dialog.component.html',
    styleUrls: ['./dataset-name-dialog.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class DatasetNameDialogComponent implements OnInit {

    public datasetInstanceSummary: any;
    public categories: any = [];

    constructor(public dialogRef: MatDialogRef<DatasetNameDialogComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private http: HttpClient,
                private projectService: ProjectService) {
    }

    async ngOnInit() {
        this.datasetInstanceSummary = this.data.datasetInstanceSummary;
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';

        this.categories = await this.http.get('/account/metadata/category?projectKey=' + projectKey, {
            params: {limit: '100'}
        }).toPromise();
    }

    public showSelected(o1: any, o2: any) {
        return o1 && o2 && o1.key === o2.key;
    }

}
