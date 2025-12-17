import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_DIALOG_DATA,
    MatDialogRef
} from '@angular/material/dialog';
import { HttpClient } from '@angular/common/http';
import {ProjectService} from '../../../../services/project.service';
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'ki-save-as-query',
    templateUrl: './save-as-query.component.html',
    styleUrls: ['./save-as-query.component.sass'],
    host: { class: 'dialog-wrapper' },
    standalone: false
})
export class SaveAsQueryComponent implements OnInit {

    public datasetInstanceSummary: any;
    public categories: any = [];
    public transformations: any;
    public includeAllTransformations = true;
    public _ = _;
    public filteredTransformations: any = [];

    private projectKey = '';

    constructor(public dialogRef: MatDialogRef<SaveAsQueryComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private http: HttpClient,
                private projectService: ProjectService) {
    }

    ngOnInit() {
        this.datasetInstanceSummary = this.data.datasetInstanceSummary;
        this.transformations = this.data.transformations;
        this.updateExcludedTransformations();
        this.projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';

        this.loadCategories();
    }

    public updateExcludedTransformations() {
        this.filteredTransformations = _.filter(this.transformations, transformation => {
            return this.includeAllTransformations ? true : (!transformation.exclude || transformation.type === 'multisort');
        });
    }

    public showSelected(o1: any, o2: any) {
        return o1 && o2 && o1.key === o2.key;
    }

    private async loadCategories() {
        this.categories = await this.http.get('/account/metadata/category?projectKey=' + this.projectKey, {
            params: {limit: '100'}
        }).toPromise();
    }
}
