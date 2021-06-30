import {Component, Inject, Input, OnInit} from '@angular/core';
import {BehaviorSubject, merge} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {DatasetService} from '../../../../services/dataset.service';
import {DatasourceService} from '../../../../services/datasource.service';
import {ProjectService} from '../../../../services/project.service';
import {TagService} from '../../../../services/tag.service';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import * as _ from 'lodash';

@Component({
    selector: 'ki-dataset-add-join',
    templateUrl: './dataset-add-join.component.html',
    styleUrls: ['./dataset-add-join.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class DatasetAddJoinComponent implements OnInit {

    public environment: any = {};
    public datasources: any = [];
    public datasets: any = [];
    public searchText = new BehaviorSubject('');
    public selectedSource: any;
    public filterFields: any;
    public joinFilterFields: any;
    public joinColumns: any = [];
    public joinTransformation: any = {
        type: 'join',
        config: {
            joinFilters: {
                logic: 'AND',
                filters: [{
                    fieldName: '',
                    value: '',
                    filterType: ''
                }],
                filterJunctions: []
            },
            joinColumns: []
        }
    };

    public activeProject: any;
    public activeTag: any;

    constructor(private datasetService: DatasetService,
                private datasourceService: DatasourceService,
                private projectService: ProjectService,
                private tagService: TagService,
                public dialogRef: MatDialogRef<DatasetAddJoinComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        this.environment = this.data.environment || {};
        this.filterFields = this.data.filterFields;
        this.activeProject = this.projectService.activeProject.getValue();
        this.activeTag = this.tagService.activeTag.getValue();

        merge(this.searchText)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasets()
                )
            ).subscribe((datasets: any) => {
            this.datasets = datasets;
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

    public select(item, type, step) {
        let promise: Promise<any>;
        if (type === 'datasource') {
            promise = this.datasourceService.getDatasource(item.key).then(datasource => {
                this.selectedSource = datasource;
                this.joinTransformation.config.joinedDataSourceInstanceKey = item.key;
            });
        } else {
            promise = this.datasetService.getDataset(item.id).then(dataset => {
                this.selectedSource = dataset;
                this.joinTransformation.config.joinedDataSetInstanceId = item.id;
            });
        }

        promise.then(() => {
            this.getRightColumns();
            setTimeout(() => {
                step.next();
            }, 0);
        });
    }

    public join() {
        const selectedColumns = _.filter(this.joinColumns, 'selected');
        this.joinTransformation.config.joinColumns = selectedColumns.map(column => {
            return {name: column.name, title: column.title};
        });
        this.dialogRef.close(this.joinTransformation);
    }

    private getRightColumns() {
        this.datasourceService.evaluateDatasource(
            this.selectedSource.key || this.selectedSource.datasourceInstanceKey,
            this.selectedSource.transformationInstances || [],
            this.selectedSource.parameterValues || [],
            [{
                type: 'paging',
                config: {
                    limit: '1',
                    offset: '0'
                }
            }])
            .then((data: any) => {
                if (data.columns) {
                    this.joinFilterFields = [];
                    this.joinColumns = [];
                    data.columns.forEach(column => {
                        this.joinFilterFields.push(
                            {
                                label: column.title,
                                value: column.name
                            }
                        );
                        this.joinColumns.push({
                            selected: true,
                            name: column.name,
                            title: column.title,
                            duplicate: !!_.find(this.filterFields, {value: column.name, label: column.title})
                        });
                    });
                }
            });
    }

    private getDatasets() {
        return this.datasetService.getDatasets(
            this.searchText.getValue() || '',
            '5',
            '0'
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
