import {Component, Inject, Input, OnInit, Output, EventEmitter} from '@angular/core';
import * as _ from 'lodash';
import {MAT_DIALOG_DATA, MatDialog, MatDialogRef} from '@angular/material/dialog';
import {DatasetSummariseComponent} from './dataset-summarise/dataset-summarise.component';

@Component({
    selector: 'ki-dataset-editor',
    templateUrl: './dataset-editor.component.html',
    styleUrls: ['./dataset-editor.component.sass']
})
export class DatasetEditorComponent implements OnInit {

    @Input() datasource: any = {};
    @Input() evaluatedDatasource: any;
    @Input() datasourceService: any;

    @Output() dataLoaded = new EventEmitter<any>();
    @Output() evaluatedDatasourceChange = new EventEmitter();

    public dataset: any;
    public tableData = [];
    public showFilters = false;
    public displayedColumns = [];
    public _ = _;
    public filterFields = [];
    public filterJunction = {
        logic: 'AND',
        filters: [{
            fieldName: '',
            value: '',
            filterType: ''
        }],
        filterJunctions: []
    };
    public multiSortConfig = [];
    public page = 1;
    public endOfResults = false;
    public parameterValues: any = [];
    public focusParams = false;

    private limit = 25;
    private offset = 0;

    constructor(public dialogRef: MatDialogRef<DatasetEditorComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dialog: MatDialog) {
    }

    ngOnInit(): void {
        if (!this.datasource) {
            this.datasource = this.data.datasource;
        }

        if (!this.evaluatedDatasource) {
            this.evaluatedDatasource = {
                key: this.datasource.key,
                datasourceInstanceKey: this.datasource.key,
                transformationInstances: [],
                parameterValues: {}
            };
        }
        this.evaluateDatasource();
    }


    public removeFilter(index) {
        this.multiSortConfig.splice(index, 1);
        this.setMultiSortValues();
    }

    public applyFilters() {
        this.validateFilterJunction();
        const filterTransformation = _.find(this.evaluatedDatasource.transformationInstances, {type: 'filter'});
        if (filterTransformation) {
            filterTransformation.config = this.filterJunction;
        } else {
            this.evaluatedDatasource.transformationInstances.push({
                type: 'filter',
                config: this.filterJunction
            });
        }

        this.evaluateDatasource();
    }

    public clearFilters() {
        this.filterJunction = {
            logic: 'AND',
            filters: [{
                fieldName: '',
                value: '',
                filterType: ''
            }],
            filterJunctions: []
        };

        const filterTransformation = _.findIndex(this.evaluatedDatasource.transformationInstances, {type: 'filter'});
        if (filterTransformation > -1) {
            this.evaluatedDatasource.transformationInstances.splice(filterTransformation, 1);
        }
        this.evaluateDatasource();
    }

    public increaseOffset() {
        this.page = this.page + 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.evaluateDatasource();
    }

    public decreaseOffset() {
        this.page = this.page <= 1 ? 1 : this.page - 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.evaluateDatasource();
    }

    public pageSizeChange(value) {
        this.limit = value;
        this.evaluateDatasource();
    }

    public sort(event) {
        const column = event.active;
        const direction = event.direction;
        const existingIndex = _.findIndex(this.multiSortConfig, {type: 'sort', column});
        if (existingIndex > -1) {
            if (!direction) {
                this.multiSortConfig.splice(existingIndex, 1);
            } else {
                this.multiSortConfig[existingIndex].direction = direction;
                this.multiSortConfig[existingIndex].string = 'Sort ' + _.startCase(column) + ' ' + direction;
            }
        } else {
            this.multiSortConfig.push({
                type: 'sort',
                column,
                direction,
                string: 'Sort ' + _.startCase(column) + ' ' + direction
            });
        }

        this.setMultiSortValues();
    }

    public summariseData() {
        this.dialog.open(DatasetSummariseComponent, {
            width: '1200px',
            height: '600px',
            data: {
                availableColumns: this.filterFields
            }
        });
    }

    public setEvaluatedParameters(parameterValues, evaluate?) {
        this.parameterValues = parameterValues;
        parameterValues.forEach(param => {
            this.evaluatedDatasource.parameterValues[param.name] = param.value;
        });
        if (evaluate) {
            this.evaluateDatasource();
        }
    }

    private validateFilterJunction() {

        const check = (filterJunction) => {
            // tslint:disable-next-line:prefer-for-of
            for (let i = 0; i < filterJunction.filterJunctions.length; i++) {
                const junction = filterJunction.filterJunctions[i];
                if (!junction.filters.length && !junction.filterJunctions.length) {
                    filterJunction.filterJunctions.splice(i, 1);
                } else {
                    check(junction);
                }
            }
        };

        check(this.filterJunction);
    }

    private setMultiSortValues() {
        if (!this.multiSortConfig.length) {
            const sortIndex = _.findIndex(this.evaluatedDatasource.transformationInstances, {type: 'multisort'});
            if (sortIndex > -1) {
                this.evaluatedDatasource.transformationInstances.splice(sortIndex, 1);
            }
        } else {
            const multiSortTransformation = _.find(this.evaluatedDatasource.transformationInstances, {type: 'multisort'});
            if (multiSortTransformation) {
                multiSortTransformation.config.sorts = _.map(this.multiSortConfig, item => {
                    return {fieldName: item.column, direction: item.direction};
                });
            } else {
                this.evaluatedDatasource.transformationInstances.push(
                    {
                        type: 'multisort',
                        config: {
                            sorts: _.map(this.multiSortConfig, item => {
                                return {fieldName: item.column, direction: item.direction};
                            })
                        }
                    }
                );
            }
        }


        this.evaluateDatasource();
    }

    private loadData() {
        this.tableData = this.dataset.allData;
        this.endOfResults = this.tableData.length < this.limit;
        this.displayedColumns = _.map(this.dataset.columns, 'name');
        this.filterFields = _.map(this.dataset.columns, column => {
            return {
                label: column.title,
                value: column.name
            };
        });
        this.dataLoaded.emit(this.dataset);
        this.evaluatedDatasourceChange.emit(this.evaluatedDatasource);
    }

    private evaluateDatasource() {
        this.datasourceService.getEvaluatedParameters(this.evaluatedDatasource)
            .then(values => {
                this.focusParams = false;
                const parameterValues = {};
                values.forEach(paramValue => {
                    const existParam = _.find(this.parameterValues, {name: paramValue.name});
                    if (!existParam || (existParam && !existParam.value)) {
                        paramValue.value = paramValue.defaultValue;
                        parameterValues[paramValue.name] = paramValue;
                        this.focusParams = true;
                    } else {
                        parameterValues[existParam.name] = existParam;
                    }
                });

                this.parameterValues = _.values(parameterValues);
                this.setEvaluatedParameters(this.parameterValues);

                console.log(this.parameterValues);
                this.datasourceService.evaluateDatasource(
                    this.evaluatedDatasource.key,
                    this.evaluatedDatasource.transformationInstances,
                    this.evaluatedDatasource.parameterValues,
                    [{
                        type: 'paging',
                        config: {
                            limit: this.limit,
                            offset: this.offset
                        }
                    }]).then(dataset => {
                    this.dataset = dataset;
                    this.loadData();
                }).catch(err => {
                    console.log(err);
                });
            });

    }

}
