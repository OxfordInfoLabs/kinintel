import {Component, Inject, Input, OnInit, ViewChild} from '@angular/core';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {DatasetService} from '../../../../services/dataset.service';
import {DatasourceService} from '../../../../services/datasource.service';
import {ProjectService} from '../../../../services/project.service';
import {TagService} from '../../../../services/tag.service';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import * as _ from 'lodash';
import {DatasetEditorComponent} from '../../dataset-editor/dataset-editor.component';
import {MatStepper} from '@angular/material/stepper';

@Component({
    selector: 'ki-dataset-add-join',
    templateUrl: './dataset-add-join.component.html',
    styleUrls: ['./dataset-add-join.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class DatasetAddJoinComponent implements OnInit {

    @ViewChild('stepper') matStepper: MatStepper;

    public environment: any = {};
    public datasources: any = [];
    public datasets: any = [];
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

    public _ = _;

    private datasetEditor: DatasetEditorComponent;

    constructor(private datasetService: DatasetService,
                private datasourceService: DatasourceService,
                private projectService: ProjectService,
                private tagService: TagService,
                public dialogRef: MatDialogRef<DatasetAddJoinComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        this.admin = !!this.data.admin;
        this.environment = this.data.environment || {};
        this.filterFields = this.data.filterFields;
        this.parameterValues = this.data.parameterValues;
        this.datasetEditor = this.data.datasetEditor;

        this.activeProject = this.projectService.activeProject.getValue();
        this.activeTag = this.tagService.activeTag.getValue();

        merge(this.searchText)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasets(false)
                )
            ).subscribe((datasets: any) => {
            this.datasets = datasets;
        });

        if (this.admin) {
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
        } else {
            merge(this.searchText)
                .pipe(
                    debounceTime(300),
                    distinctUntilChanged(),
                    switchMap(() =>
                        this.getDatasets(true)
                    )
                ).subscribe((datasets: any) => {
                this.sharedDatasets = datasets;
            });
        }

        if (this.data.joinTransformation) {
            this.joinTransformation = this.data.joinTransformation;
            const item = {
                key: this.joinTransformation.config.joinedDataSourceInstanceKey,
                id: this.joinTransformation.config.joinedDataSetInstanceId
            };
            const type = item.key ? 'datasource' : 'dataset';

            setTimeout(() => {
                this.select(item, type, this.matStepper, false);
            }, 200);

            console.log('JOIN', this.joinTransformation);
        }
    }

    public async select(item, type, step, reset = true) {
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

        if (type === 'datasource') {
            const datasource: any = await this.datasourceService.getDatasource(item.key);
            this.selectedSource = {
                datasetInstanceId: null,
                datasourceInstanceKey: datasource.key,
                transformationInstances: [],
                parameterValues: {},
                parameters: []
            };
            this.joinTransformation.config.joinedDataSourceInstanceKey = item.key;

            requiredParams = await this.datasourceService.getEvaluatedParameters({
                key: item.key
            });
        } else {
            this.selectedSource = await this.datasetService.getDataset(item.id);
            this.joinTransformation.config.joinedDataSetInstanceId = item.id;

            requiredParams = await this.datasetService.getEvaluatedParameters(this.selectedSource);
        }

        this.createParameterStructure(requiredParams);

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
        this.joinTransformation.config.joinParameterMappings = parameterMappings;

        this.dialogRef.close(this.joinTransformation);
    }

    public join() {
        const filters = this.joinTransformation.config.joinFilters;
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

        const data: any = await this.datasetService.evaluateDataset(this.selectedSource, '0', '10');
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

    private getDatasets(shared) {
        return this.datasetService.getDatasets(
            this.searchText.getValue() || '',
            '5',
            '0',
            shared ? null : ''
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
