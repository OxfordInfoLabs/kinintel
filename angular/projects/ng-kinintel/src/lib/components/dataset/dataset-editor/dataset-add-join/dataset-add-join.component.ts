import {Component, Inject, Input, OnInit, ViewChild} from '@angular/core';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {DatasetService} from '../../../../services/dataset.service';
import {DatasourceService} from '../../../../services/datasource.service';
import {ProjectService} from '../../../../services/project.service';
import {TagService} from '../../../../services/tag.service';
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';
import * as lodash from 'lodash';
const _ = lodash.default;
import {DatasetEditorComponent} from '../../dataset-editor/dataset-editor.component';
import {MatStepper} from '@angular/material/stepper';
import {DataProcessorService} from '../../../../services/data-processor.service';

@Component({
    selector: 'ki-dataset-add-join',
    templateUrl: './dataset-add-join.component.html',
    styleUrls: ['./dataset-add-join.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class DatasetAddJoinComponent implements OnInit {

    @ViewChild('stepper') matStepper: MatStepper;

    public Object = Object;
    public environment: any = {};
    public datasources: any = [];
    public datasets: any = [];
    public snapshots: any = [];
    public sharedDatasets: any = [];
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
            joinParameterMappings: [],
            strictJoin: false
        }
    };
    public admin: boolean;
    public allColumns = true;
    public activeProject: any;
    public activeTag: any;
    public requiredParameters: any;
    public parameterValues: any;
    public updateParams = new Subject();
    public openSide = new BehaviorSubject(false);
    public tableData: any = {
        datasources: {
            data: [],
            limit: 5,
            offset: 0,
            page: 1,
            endOfResults: false,
            shared: false,
            reload: new Subject(),
            title: 'My Data',
            type: 'datasource'
        },
        datasets: {
            data: [],
            limit: 5,
            offset: 0,
            page: 1,
            endOfResults: false,
            shared: false,
            reload: new Subject(),
            title: 'Stored Queries',
            type: 'dataset'
        }
    };

    public _ = _;

    private datasetEditor: DatasetEditorComponent;

    constructor(private datasetService: DatasetService,
                private datasourceService: DatasourceService,
                private projectService: ProjectService,
                private tagService: TagService,
                public dialogRef: MatDialogRef<DatasetAddJoinComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dataProcessorService: DataProcessorService) {
    }

    ngOnInit(): void {
        this.admin = !!this.data.admin;
        this.environment = this.data.environment || {};
        this.filterFields = this.data.filterFields;
        this.parameterValues = this.data.parameterValues;
        this.datasetEditor = this.data.datasetEditor;

        if (!this.admin) {
            this.tableData.shared = {
                data: [],
                limit: 5,
                offset: 0,
                page: 1,
                endOfResults: false,
                shared: false,
                reload: new Subject(),
                title: 'Data Feeds',
                type: 'dataset'
            };
        }

        this.tableData.snapshots = {
            data: [],
            limit: 5,
            offset: 0,
            page: 1,
            endOfResults: false,
            shared: false,
            reload: new Subject(),
            title: 'Snapshots',
            type: 'snapshot'
        };

        this.activeProject = this.projectService.activeProject.getValue();
        this.activeTag = this.tagService.activeTag.getValue();

        merge(this.searchText, this.tableData.datasets.reload)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasets(false, this.tableData.datasets.limit, this.tableData.datasets.offset)
                )
            ).subscribe((datasets: any) => {
            this.tableData.datasets.endOfResults = datasets.length < this.tableData.datasets.limit;
            this.tableData.datasets.data = datasets;
        });

        merge(this.searchText, this.tableData.snapshots.reload)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getSnapshots(this.tableData.snapshots.limit, this.tableData.snapshots.offset)
                )
            ).subscribe((snapshots: any) => {
            this.tableData.snapshots.endOfResults = snapshots.length < this.tableData.snapshots.limit;
            this.tableData.snapshots.data = snapshots;
        });

        merge(this.searchText, this.tableData.datasources.reload)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasources(this.tableData.datasources.limit, this.tableData.datasources.offset)
                )
            ).subscribe((datasources: any) => {
            this.tableData.datasources.endOfResults = datasources.length < this.tableData.datasources.limit;
            this.tableData.datasources.data = datasources;
        });

        if (!this.admin) {
            merge(this.searchText, this.tableData.shared.reload)
                .pipe(
                    debounceTime(300),
                    distinctUntilChanged(),
                    switchMap(() =>
                        this.getDatasets(true, this.tableData.shared.limit, this.tableData.shared.offset)
                    )
                ).subscribe((shared: any) => {
                this.tableData.shared.endOfResults = shared.length < this.tableData.shared.limit;
                this.tableData.shared.data = shared;
            });
        }

        if (this.data.joinTransformation) {
            this.joinTransformation = this.data.joinTransformation;
            const action = {
                datasourceKey: this.joinTransformation.config.joinedDataSourceInstanceKey,
                datasetId: this.joinTransformation.config.joinedDataSetInstanceId
            };

            setTimeout(() => {
                this.select(action, this.matStepper, false);
            }, 200);
        }
    }

    public increaseOffset(dataItem) {
        dataItem.page = dataItem.page + 1;
        dataItem.offset = (dataItem.limit * dataItem.page) - dataItem.limit;
        dataItem.reload.next(Date.now());
    }

    public decreaseOffset(dataItem) {
        dataItem.page = dataItem.page <= 1 ? 1 : dataItem.page - 1;
        dataItem.offset = (dataItem.limit * dataItem.page) - dataItem.limit;
        dataItem.reload.next(Date.now());
    }

    public pageSizeChange(value, dataItem) {
        dataItem.page = 1;
        dataItem.offset = 0;
        dataItem.limit = value;
        dataItem.reload.next(Date.now());
    }

    public async select(action, step, reset = true) {
        this.joinFilterFields = null;
        // Reset if we are selecting an item again, not loading initially.
        if (reset) {
            this.joinTransformation = {
                type: 'join',
                config: {
                    joinFilters: [],
                    joinColumns: [],
                    joinParameterMappings: [],
                    strictJoin: false
                }
            };
        }

        let requiredParams: any = [];

        if (action.datasourceKey) {
            this.selectedSource = {
                datasetInstanceId: null,
                datasourceInstanceKey: action.datasourceKey,
                transformationInstances: [],
                parameterValues: {},
                parameters: []
            };
            this.joinTransformation.config.joinedDataSourceInstanceKey = action.datasourceKey;

            requiredParams = await this.datasourceService.getEvaluatedParameters({
                key: action.datasourceKey
            });
        } else if (action.datasetId) {
            this.selectedSource = await this.datasetService.getDataset(action.datasetId);
            this.joinTransformation.config.joinedDataSetInstanceId = action.datasetId;

            requiredParams = await this.datasetService.getEvaluatedParameters(this.selectedSource);
        }

        const existingJoinedParameters = this.joinTransformation.config.joinParameterMappings || [];

        this.createParameterStructure(requiredParams, existingJoinedParameters);

        if (!this.requiredParameters || !this.requiredParameters.length) {
            this.getJoinColumns(reset);
        }

        setTimeout(() => {
            step.next();
        }, 0);
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

    public async getParameterValues(step) {
        this.updateParams.next(Date.now());

        await this.getJoinColumns(true);

        setTimeout(() => {
            step.next();
        }, 0);
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
        this.joinTransformation.config.joinParameterMappings = parameterMappings;
    }

    public join() {
        const filters = this.joinTransformation.config.joinFilters;
        const selectedColumns = _.filter(this.joinColumns, 'selected');
        this.joinTransformation.config.joinColumns = selectedColumns.map(column => {
            return {name: column.name, title: column.title};
        });
        this.dialogRef.close(this.joinTransformation);
    }

    private createParameterStructure(requiredParameters, existingValue = []) {
        this.requiredParameters = requiredParameters.map(param => {
            param.type = 'join';
            param.selectedType = 'Column';

            const existing = _.find(existingValue, {parameterName: param.name});
            if (existing) {
                if (existing.sourceColumnName) {
                    param.value = existing.sourceColumnName;
                }
                if (existing.sourceParameterName) {
                    param.selectedType = 'Parameter';
                    param.value = existing.sourceParameterName;
                }
            }

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

    private async getJoinColumns(reset = true) {
        if (reset) {
            this.joinTransformation.config.joinFilters = {
                logic: 'AND',
                filters: [{
                    lhsExpression: '',
                    rhsExpression: '',
                    filterType: ''
                }],
                filterJunctions: []
            };
        }

        // If we have join parameter mappings then we need to add the parameter value to the evaluate to get column data
        if (this.joinTransformation.config.joinParameterMappings &&
            this.joinTransformation.config.joinParameterMappings.length) {

            const firstDataRow = this.datasetEditor.dataset.allData[0];
            const parameterValues = {};
            this.joinTransformation.config.joinParameterMappings.forEach(param => {
                parameterValues[param.parameterName] = firstDataRow[param.sourceColumnName];
            });

            this.selectedSource.parameterValues = parameterValues;
        }

        const data: any = await this.datasetService.evaluateDataset(this.selectedSource, '0', '1');
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
        }
    }

    private getDatasets(shared, limit, offset) {
        return this.datasetService.getDatasets(
            this.searchText.getValue() || '',
            limit.toString(),
            offset.toString(),
            shared ? null : ''
        ).pipe(map((datasets: any) => {
                return datasets;
            })
        );
    }

    private getDatasources(limit, offset) {
        return this.datasourceService.getDatasources(
            this.searchText.getValue() || '',
            limit.toString(),
            offset.toString()
        ).pipe(map((sources: any) => {
                return sources;
            })
        );
    }

    private getSnapshots(limit, offset) {
        return this.dataProcessorService.filterProcessorsByType(
            'snapshot',
            this.searchText.getValue() || '',
            limit.toString(),
            offset.toString()
        ).pipe(map((snapshots: any) => {
                return _.filter(snapshots, snapshot => {
                    return snapshot.taskStatus !== 'PENDING';
                });
            })
        );
    }
}
