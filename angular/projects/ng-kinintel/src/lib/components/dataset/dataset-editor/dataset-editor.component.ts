import {Component, Inject, Input, OnInit, Output, EventEmitter} from '@angular/core';
import * as _ from 'lodash';
import {MAT_DIALOG_DATA, MatDialog, MatDialogRef} from '@angular/material/dialog';
import {DatasetSummariseComponent} from './dataset-summarise/dataset-summarise.component';
import {DatasourceService} from '../../../services/datasource.service';
import {DatasetAddJoinComponent} from './dataset-add-join/dataset-add-join.component';
import {DatasetCreateFormulaComponent} from './dataset-create-formula/dataset-create-formula.component';
import {DatasetColumnSettingsComponent} from './dataset-column-settings/dataset-column-settings.component';

@Component({
    selector: 'ki-dataset-editor',
    templateUrl: './dataset-editor.component.html',
    styleUrls: ['./dataset-editor.component.sass']
})
export class DatasetEditorComponent implements OnInit {

    @Input() datasource: any = {};
    @Input() evaluatedDatasource: any;
    @Input() environment: any = {};

    @Output() dataLoaded = new EventEmitter<any>();
    @Output() evaluatedDatasourceChange = new EventEmitter();

    public dataset: any;
    public tableData = [];
    public showFilters = false;
    public showParameters = false;
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
    public page = 1;
    public endOfResults = false;
    public parameterValues: any = [];
    public focusParams = false;
    public terminatingTransformations = [];

    private limit = 25;
    private offset = 0;

    constructor(public dialogRef: MatDialogRef<DatasetEditorComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dialog: MatDialog,
                private datasourceService: DatasourceService) {
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
                parameterValues: {},
                parameters: []
            };
        }

        if (this.evaluatedDatasource.transformationInstances.length) {
            const filter = _.find(this.evaluatedDatasource.transformationInstances, {type: 'filter'});
            if (filter) {
                this.filterJunction = filter.config;
                this.showFilters = true;
            }
        }

        this.evaluateDatasource();
    }


    public removeFilter(index) {
        const existingMultiSort = _.find(this.evaluatedDatasource.transformationInstances, {type: 'multisort'});
        existingMultiSort.config.sorts.splice(index, 1);
        if (!existingMultiSort.config.sorts.length) {
            _.remove(this.evaluatedDatasource.transformationInstances, {type: 'multisort'});
        }
        this.evaluateDatasource();
    }

    public removeTransformation(transformation) {
        _.remove(this.evaluatedDatasource.transformationInstances, {
            type: transformation.type,
            config: transformation.config
        });
        this.evaluateDatasource();
    }

    public disableTransformation(transformation) {

    }

    public exportData() {

    }

    public editColumnSettings() {
        const dialogRef = this.dialog.open(DatasetColumnSettingsComponent, {
            width: '1000px',
            height: '800px',
            data: {
                columns: this.filterFields,
                reset: !!_.find(this.evaluatedDatasource.transformationInstances, {type: 'columns'})
            }
        });

        dialogRef.afterClosed().subscribe(columns => {
            if (columns) {
                const fields = _.map(columns, column => {
                    return {title: column.title, name: column.name};
                });

                _.remove(this.evaluatedDatasource.transformationInstances, {type: 'columns'});

                this.evaluatedDatasource.transformationInstances.push({
                    type: 'columns',
                    config: {
                        columns: fields
                    }
                });

                this.evaluateDatasource();
            }
        });
    }

    public createFormula() {
        const existingFormulas = _.find(this.evaluatedDatasource.transformationInstances, {type: 'formula'});

        const dialogRef = this.dialog.open(DatasetCreateFormulaComponent, {
            width: '1000px',
            height: '800px',
            data: {
                allColumns: this.filterFields,
                formulas: existingFormulas ? existingFormulas.config.expressions : [{}]
            }
        });

        dialogRef.afterClosed().subscribe(formulas => {
            _.remove(this.evaluatedDatasource.transformationInstances, {type: 'formula'});
            this.evaluatedDatasource.transformationInstances.push({
                type: 'formula',
                config: {
                    expressions: formulas
                }
            });
            this.evaluateDatasource();
        });
    }

    public joinData() {
        const dialogRef = this.dialog.open(DatasetAddJoinComponent, {
            width: '1200px',
            height: '800px',
            data: {
                environment: this.environment,
                filterFields: this.filterFields,
                parameterValues: _.map(this.parameterValues, param => {
                    return {
                        title: param.title,
                        name: param.name,
                        currentValue: this.evaluatedDatasource.parameterValues[param.name]
                    };
                }),
                datasetEditor: this
            },
        });

        dialogRef.afterClosed().subscribe(joinTransformation => {
            if (joinTransformation) {
                this.evaluatedDatasource.transformationInstances.push(joinTransformation);
                this.evaluateDatasource();
            }
        });
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

        const existingMultiSort = _.find(this.evaluatedDatasource.transformationInstances, {type: 'multisort'});
        if (existingMultiSort) {
            const existingIndex = _.findIndex(existingMultiSort.config.sorts, {fieldName: column});
            if (direction) {
                if (existingIndex > -1) {
                    existingMultiSort.config.sorts[existingIndex] = {fieldName: column, direction};
                } else {
                    existingMultiSort.config.sorts.push({fieldName: column, direction});
                }
            } else {
                existingMultiSort.config.sorts.splice(existingIndex, 1);
                if (!existingMultiSort.config.sorts.length) {
                    _.remove(this.evaluatedDatasource.transformationInstances, {type: 'multisort'});
                }
            }
        } else {
            if (direction) {
                this.evaluatedDatasource.transformationInstances.push(
                    {
                        type: 'multisort',
                        config: {
                            sorts: [{fieldName: column, direction}]
                        }
                    }
                );
            }
        }
        this.evaluateDatasource();
    }

    public summariseData() {
        const summariseDialog = this.dialog.open(DatasetSummariseComponent, {
            width: '1200px',
            height: '675px',
            data: {
                availableColumns: this.filterFields
            }
        });

        summariseDialog.afterClosed().subscribe(summariseTransformation => {
            if (summariseTransformation) {
                this.evaluatedDatasource.transformationInstances.push({
                    type: 'summarise',
                    config: summariseTransformation
                });
                this.evaluateDatasource();
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

    public getOrdinal(n) {
        const s = ['th', 'st', 'nd', 'rd'];
        const v = n % 100;
        return n + (s[(v - 20) % 10] || s[v] || s[0]);
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

    private loadData() {
        this.tableData = this.dataset.allData;
        this.endOfResults = this.tableData.length < this.limit;
        this.displayedColumns = _.map(this.dataset.columns, 'name');
        this.filterFields = _.map(this.dataset.columns, column => {
            return {
                title: column.title,
                name: column.name
            };
        });
        this.dataLoaded.emit(this.dataset);
        this.evaluatedDatasourceChange.emit(this.evaluatedDatasource);

        let summarise = 0;
        let join = 0;
        const summariseTotal = _.filter(this.evaluatedDatasource.transformationInstances, {type: 'summarise'}).length;
        const joinTotal = _.filter(this.evaluatedDatasource.transformationInstances, {type: 'join'}).length;
        this.terminatingTransformations = _.filter(this.evaluatedDatasource.transformationInstances, (transformation, index) => {
            if (transformation.type === 'summarise') {
                summarise++;
                transformation._label = this.getOrdinal(summarise) + ' Summarisation';
                transformation._disable = summarise < summariseTotal;
                transformation._active = summarise === summariseTotal;
                return true;
            }
            if (transformation.type === 'join') {
                join++;
                transformation._label = this.getOrdinal(join) + ' Join';
                transformation._disable = join < joinTotal;
                transformation._active = join === joinTotal;
                return true;
            }
            return false;
        });
        return true;
    }

    private setParameterValues(values) {

    }

    public evaluateDatasource() {
        return this.datasourceService.getEvaluatedParameters(this.evaluatedDatasource)
            .then((values: any) => {
                let paramValues = values;
                if (this.evaluatedDatasource.parameters.length) {
                    paramValues = values.concat(this.evaluatedDatasource.parameters);
                }

                this.focusParams = false;
                const parameterValues = {};
                paramValues.forEach(paramValue => {
                    const existParam = _.find(this.parameterValues, {name: paramValue.name});
                    if (!existParam || (existParam && !existParam.value)) {
                        const existingValue = this.evaluatedDatasource.parameterValues[paramValue.name];
                        paramValue.value = existingValue || paramValue.defaultValue;
                        parameterValues[paramValue.name] = paramValue;
                        this.focusParams = !existingValue;
                    } else {
                        parameterValues[existParam.name] = existParam;
                    }
                });

                this.parameterValues = _.values(parameterValues);
                this.setEvaluatedParameters(this.parameterValues);

                return this.datasourceService.evaluateDatasource(
                    this.evaluatedDatasource.key || this.evaluatedDatasource.datasourceInstanceKey,
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
                    return this.loadData();
                }).catch(err => {
                });
            });

    }

}
