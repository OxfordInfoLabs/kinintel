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
    @Input() admin: boolean;

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
    public datasetInstance: any;

    public limit = 25;
    private offset = 0;

    constructor(public dialogRef: MatDialogRef<DatasetEditorComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dialog: MatDialog,
                private datasourceService: DatasourceService) {
    }

    ngOnInit(): void {
        if (!this.datasource) {
            this.datasource = this.data ? this.data.datasource : {};
        }

        let limit = null;

        this.datasetInstance = this.data ? this.data.dataset : null;
        if (this.datasetInstance) {
            limit = localStorage.getItem('datasetInstanceLimit' + this.datasetInstance.id);
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

        if (this.evaluatedDatasource && !limit) {
            limit = localStorage.getItem('datasetInstanceLimit' + this.evaluatedDatasource.instanceKey);
        }

        this.limit = limit ? Number(limit) : 25;

        if (this.evaluatedDatasource.transformationInstances.length) {
            const filter = _.find(this.evaluatedDatasource.transformationInstances, {type: 'filter'});
            if (filter) {
                this.filterJunction = filter.config;
                this.showFilters = true;
            }
        }

        this.evaluateDatasource();
    }

    public addFilter() {
        this.evaluatedDatasource.transformationInstances.push({
            type: 'filter',
            config: {
                logic: 'AND',
                filters: [{
                    fieldName: '',
                    value: '',
                    filterType: ''
                }],
                filterJunctions: []
            },
            hide: false
        });
    }

    public removeFilter(index) {
        const existingMultiSort = _.find(this.evaluatedDatasource.transformationInstances, {type: 'multisort'});
        existingMultiSort.config.sorts.splice(index, 1);
        if (!existingMultiSort.config.sorts.length) {
            _.remove(this.evaluatedDatasource.transformationInstances, {type: 'multisort'});
        }
        this.evaluateDatasource();
    }

    public removeTransformation(transformation, confirm = false) {
        const index = _.findIndex(this.evaluatedDatasource.transformationInstances, {
            type: transformation.type,
            config: transformation.config
        });
        if (confirm) {
            const message = 'Are you sure you would like to remove this transformation?';
            if (window.confirm(message)) {
                this._removeTransformation(transformation, index);
            }
        } else {
            this._removeTransformation(transformation, index);
        }
    }


    public disableTransformation(transformation) {

    }

    public editTerminatingTransformation(transformation) {
        if (transformation.type === 'summarise') {
            const clonedTransformation = _.clone(transformation);
            // Grab the index of the current transformation - so we know which upstream transformations to exclude
            const existingIndex = _.findIndex(this.evaluatedDatasource.transformationInstances, {
                type: transformation.type,
                config: transformation.config
            });

            // Exclude all upstream transformations from the evaluate
            this.evaluatedDatasource.transformationInstances.forEach((instance, index) => {
                if (index >= existingIndex) {
                    instance.exclude = true;
                }
            });

            this.evaluateDatasource().then(() => {
                this.summariseData(clonedTransformation.config, existingIndex);
            });
        }
        if (transformation.type === 'join') {
            this.joinData(transformation);
        }
        if (transformation.type === 'formula') {
            this.createFormula();
        }
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
            if (formulas) {
                _.remove(this.evaluatedDatasource.transformationInstances, {type: 'formula'});
                this.evaluatedDatasource.transformationInstances.push({
                    type: 'formula',
                    config: {
                        expressions: formulas
                    }
                });
                this.evaluateDatasource();
            }
        });
    }

    public joinData(transformation?) {
        const data: any = {
            environment: this.environment,
            filterFields: this.filterFields,
            parameterValues: _.map(this.parameterValues, param => {
                return {
                    title: param.title,
                    name: param.name,
                    currentValue: this.evaluatedDatasource.parameterValues[param.name]
                };
            }),
            datasetEditor: this,
        };
        if (transformation) {
            data.joinTransformation = {
                type: 'join',
                config: transformation.config
            };
        }
        const dialogRef = this.dialog.open(DatasetAddJoinComponent, {
            width: '1200px',
            height: '800px',
            data,
        });

        dialogRef.afterClosed().subscribe(joinTransformation => {
            if (joinTransformation) {
                this.evaluatedDatasource.transformationInstances.push(joinTransformation);
                this.evaluateDatasource();
            }
        });
    }

    public applyFilters() {
        const filterTransformations = _.filter(this.evaluatedDatasource.transformationInstances, {type: 'filter'});
        filterTransformations.forEach(transformation => {
            this.validateFilterJunction(transformation.config);
        });

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

    public addPagingMarker() {
        this.evaluatedDatasource.transformationInstances.push({type: 'pagingmarker'});
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

    public summariseData(config?, existingIndex?) {
        const clonedConfig = config ? _.clone(config) : null;
        const summariseDialog = this.dialog.open(DatasetSummariseComponent, {
            width: '1200px',
            height: '675px',
            data: {
                availableColumns: this.filterFields,
                config: config ? config : null
            }
        });

        summariseDialog.afterClosed().subscribe(summariseTransformation => {
            if (summariseTransformation) {
                if (existingIndex >= 0) {
                    // Check if anything has changed
                    if (!_.isEqual(clonedConfig, summariseTransformation) && (existingIndex + 1) < this.evaluatedDatasource.transformationInstances.length) {
                        // Things have changed, ask user to confirm
                        const message = 'You have made changes which may result in upstream transformations becoming incompatible, causing' +
                            ' the evaluation to fail. In order to proceed we will need to remove the affected transformations. Do ' +
                            'you want to proceed?';
                        if (window.confirm(message)) {
                            for (let i = existingIndex; i < this.evaluatedDatasource.transformationInstances.length; i++) {
                                this.evaluatedDatasource.transformationInstances.splice(i, 1);
                            }
                            this.evaluatedDatasource.transformationInstances[existingIndex] = {
                                type: 'summarise',
                                config: summariseTransformation
                            };
                            this.evaluateDatasource();
                        }
                    } else {
                        this.evaluatedDatasource.transformationInstances[existingIndex] = {
                            type: 'summarise',
                            config: summariseTransformation
                        };
                        this.evaluateDatasource();
                    }
                } else {
                    this.evaluatedDatasource.transformationInstances.push({
                        type: 'summarise',
                        config: summariseTransformation
                    });
                    this.evaluateDatasource();
                }
            } else {
                // If we get here then the summarise transformation was cancelled - restore if we have one
                if (clonedConfig) {
                    this.evaluatedDatasource.transformationInstances[existingIndex] = {
                        type: 'summarise',
                        config: clonedConfig
                    };
                    this.evaluatedDatasource.transformationInstances.forEach((instance, index) => {
                        instance.exclude = false;
                    });
                    this.evaluateDatasource();
                }
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

    private validateFilterJunction(filterConfig) {

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

        check(filterConfig);
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

                this.hidePreTerminatingFilters(index);

                return true;
            }
            if (transformation.type === 'join') {
                join++;
                transformation._label = this.getOrdinal(join) + ' Join';
                transformation._disable = join < joinTotal;
                transformation._active = join === joinTotal;

                this.hidePreTerminatingFilters(index);

                return true;
            }
            if (transformation.type === 'pagingmarker') {
                transformation._label = 'Paging Marker';
                transformation._disable = false;
                transformation._active = true;
                return true;
            }
            if (transformation.type === 'formula') {
                transformation._label = 'Formula Expression';
                transformation._disable = false;
                transformation._active = true;
                return true;
            }
            if (transformation.type === 'columns') {
                transformation._label = 'Columns Updated';
                transformation._disable = false;
                transformation._active = true;
                return true;
            }
            return false;
        });
        return true;
    }

    private setParameterValues(values) {

    }

    private hidePreTerminatingFilters(terminatingIndex) {
        this.evaluatedDatasource.transformationInstances.forEach((transformation, index) => {
            if (transformation.type === 'filter') {
                transformation.hide = transformation.exclude || terminatingIndex > index;
            }
        });
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
                    _.filter(this.evaluatedDatasource.transformationInstances, transformation => {
                        return !transformation.exclude;
                    }),
                    this.evaluatedDatasource.parameterValues,
                    this.offset,
                    this.limit
                ).then(dataset => {
                    this.dataset = dataset;
                    if (this.datasetInstance && this.datasetInstance.id) {
                        localStorage.setItem('datasetInstanceLimit' + this.datasetInstance.id, (this.limit).toString());
                    } else if (this.evaluatedDatasource.instanceKey) {
                        localStorage.setItem('datasetInstanceLimit' + this.evaluatedDatasource.instanceKey, (this.limit).toString());
                    }
                    return this.loadData();
                }).catch(err => {
                });
            });

    }

    private _removeTransformation(transformation, index?) {
        if (index >= 0) {
            // If a current index has been supplied, reset all the pre transformation hidden fields prior to eval.
            for (let i = 0; i < index; i++) {
                this.evaluatedDatasource.transformationInstances[i].hide = false;
            }
        }

        _.remove(this.evaluatedDatasource.transformationInstances, _.omitBy({
            type: transformation.type,
            config: transformation.type !== 'pagingmarker' ? transformation.config : null
        }, _.isNil));
        this.evaluateDatasource();
    }

}
