import {Component, Inject, Input, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject} from 'rxjs';
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
            joinFilters: [],
            joinColumns: [],
            joinParameterMappings: []
        }
    };

    public allColumns = true;
    public activeProject: any;
    public activeTag: any;
    public requiredParameters: any;
    public parameterValues: any;
    public updateParams = new Subject();

    public _ = _;

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
        this.parameterValues = this.data.parameterValues;

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
        this.joinFilterFields = null;
        let promise: Promise<any>;
        if (type === 'datasource') {
            promise = this.datasourceService.getDatasource(item.key).then(datasource => {
                this.selectedSource = datasource;
                this.joinTransformation.config.joinedDataSourceInstanceKey = item.key;
                return this.datasourceService.getEvaluatedParameters({
                    key: item.key
                }).then(requiredParams => {
                    this.createParameterStructure(requiredParams);
                    return requiredParams;
                });
            });
        } else {
            promise = this.datasetService.getDataset(item.id).then(dataset => {
                this.selectedSource = dataset;
                this.joinTransformation.config.joinedDataSetInstanceId = item.id;
                this.datasetService.getEvaluatedParameters(item.id).then((requiredParams: any) => {
                    this.createParameterStructure(requiredParams);
                    return requiredParams;
                });
            });
        }

        promise.then(() => {
            if (!this.requiredParameters || !this.requiredParameters.length) {
                this.getJoinColumns();
            }

            setTimeout(() => {
                step.next();
            }, 0);
        });
    }

    public toggleAllColumns(event) {
        this.joinColumns.map(column => {
            column.selected = event.checked;
            return column;
        });
    }

    public allSelected() {
        this.allColumns = _.every(this.joinColumns, 'selected');
    }

    public getParameterValues() {
        this.updateParams.next(Date.now());
    }

    public setEvaluatedParameters(requiredParameters, step?) {
        const parameterMappings = [];
        requiredParameters.forEach(param => {
            const mapping: any = {parameterName: param.name};
            if (param.selectedType === 'Column') {
                mapping.sourceColumnName = param.value;
            } else if (param.selectedType === 'Parameter') {
                mapping.sourceParameterName = param.value;
            }
            parameterMappings.push(mapping);
        });
        // if (!this.selectedSource.parameterValues) {
        //     this.selectedSource.parameterValues = {};
        // }
        // requiredParameters.forEach(param => {
        //     this.selectedSource.parameterValues[param.name] = param.value;
        // });
        // this.getJoinColumns();
        this.joinTransformation.config.joinParameterMappings = parameterMappings;

        if (step) {
            setTimeout(() => {
                step.next();
            }, 0);
        }
    }

    public join() {
        const selectedColumns = _.filter(this.joinColumns, 'selected');
        this.joinTransformation.config.joinColumns = selectedColumns.map(column => {
            return {name: column.name, title: column.title};
        });
        this.dialogRef.close(this.joinTransformation);
    }

    private createParameterStructure(requiredParameters) {
        this.requiredParameters = requiredParameters.map(param => {
            param.type = 'join';
            param.selectedType = 'Column';
            param.values = [
                {
                    label: 'Column',
                    values: this.filterFields
                },
                {
                    label: 'Parameter',
                    values: _.map(this.parameterValues, (value) => {
                        return {
                            title: value.title + ' (' + value.currentValue + ')',
                            name: value.name
                        };
                    })
                }
            ];
            return param;
        });
    }

    private getJoinColumns() {
        this.joinTransformation.config.joinFilters = {
            logic: 'AND',
            filters: [{
                fieldName: '',
                value: '',
                filterType: ''
            }],
            filterJunctions: []
        };
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
                                title: column.title,
                                name: column.name
                            }
                        );
                        const duplicate = !!_.find(this.filterFields, {name: column.name, title: column.title});
                        this.joinColumns.push({
                            selected: !duplicate,
                            name: column.name,
                            title: column.title,
                            duplicate
                        });
                    });
                    this.allColumns = _.every(this.joinColumns, 'selected');
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
