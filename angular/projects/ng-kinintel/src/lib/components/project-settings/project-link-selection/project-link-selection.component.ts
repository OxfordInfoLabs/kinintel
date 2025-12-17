import {Component, Inject, OnInit} from '@angular/core';
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {DashboardService} from '../../../services/dashboard.service';
import {DatasetService} from '../../../services/dataset.service';
import {DatasourceService} from '../../../services/datasource.service';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import * as _ from 'lodash';

@Component({
    selector: 'ki-project-link-selection',
    templateUrl: './project-link-selection.component.html',
    styleUrls: ['./project-link-selection.component.sass'],
    host: { class: 'dialog-wrapper' },
    standalone: false
})
export class ProjectLinkSelectionComponent implements OnInit {

    public searchText = new BehaviorSubject('');
    public projectDashboards: any = [];
    public sharedDashboards: any = [];
    public storedQueries: any = [];
    public dataPackages: any = [];
    public datasources: any = [];

    constructor(public dialogRef: MatDialogRef<ProjectLinkSelectionComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dashboardService: DashboardService,
                private datasetService: DatasetService,
                private datasourceService: DatasourceService) {
    }

    ngOnInit(): void {

        merge(this.searchText)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getProjectDashboards()
                )
            ).subscribe((dashboards: any) => {
            this.projectDashboards = dashboards;
        });

        merge(this.searchText)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getSharedDashboards()
                )
            ).subscribe((dashboards: any) => {
            this.sharedDashboards = dashboards;
        });

        merge(this.searchText)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasets()
                )
            ).subscribe((datasets: any) => {
            this.storedQueries = datasets;
        });

        merge(this.searchText)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDataPackages()
                )
            ).subscribe((datasets: any) => {
            this.dataPackages = datasets;
        });

        merge(this.searchText)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasources()
                )
            ).subscribe((sources: any) => {
            this.datasources = sources;
        });
    }

    public selectDashboard(dashboard) {
        this.dialogRef.close(this.data.dashboardURL + '/view/' + dashboard.id);
    }

    public selectQuery(query) {
        this.dialogRef.close(this.data.queryURL + '/' + query.id);
    }

    public selectDatasource(datasource) {
        const datasourceURL = _.find(this.data.datasourceURLs, {type: datasource.type});
        this.dialogRef.close((datasourceURL ? datasourceURL.url : '') + '/' + datasource.key);
    }

    private getSharedDashboards() {
        return this.dashboardService.getDashboards(
            this.searchText.getValue() || '',
            '5',
            '0',
            null
        ).pipe(map((dashboards: any) => {
                return dashboards;
            })
        );
    }

    private getProjectDashboards() {
        return this.dashboardService.getDashboards(
            this.searchText.getValue() || '',
            '5',
            '0',
            ''
        ).pipe(map((dashboards: any) => {
                return dashboards;
            })
        );
    }

    private getDatasets() {
        return this.datasetService.getDatasets(
            this.searchText.getValue() || '',
            '5',
            '0',
            ''
        ).pipe(map((datasets: any) => {
                return datasets;
            })
        );
    }

    private getDataPackages() {
        return this.datasetService.getDatasets(
            this.searchText.getValue() || '',
            '5',
            '0',
            null
        ).pipe(map((datasets: any) => {
                return datasets;
            })
        );
    }

    private getDatasources() {
        return this.datasourceService.getDatasources(
            this.searchText.getValue() || '',
            '5',
            '0'
        ).pipe(map((sources: any) => {
                return sources;
            })
        );
    }
}
