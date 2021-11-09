import {Component, Inject, Input, OnInit, Output, EventEmitter} from '@angular/core';
import * as _ from 'lodash';
import {MAT_DIALOG_DATA, MatDialog, MatDialogRef} from '@angular/material/dialog';
import {DatasetSummariseComponent} from './dataset-summarise/dataset-summarise.component';
import {DatasetAddJoinComponent} from './dataset-add-join/dataset-add-join.component';
import {DatasetCreateFormulaComponent} from './dataset-create-formula/dataset-create-formula.component';
import {DatasetColumnSettingsComponent} from './dataset-column-settings/dataset-column-settings.component';
import {DatasetService} from '../../../services/dataset.service';
import {MatSnackBar} from '@angular/material/snack-bar';

@Component({
    selector: 'ki-dataset-editor',
    templateUrl: './dataset-editor.component.html',
    styleUrls: ['./dataset-editor.component.sass']
})
export class DatasetEditorComponent implements OnInit {

    @Input() datasetInstanceSummary: any;
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
            lhsExpression: '',
            rhsExpression: '',
            filterType: ''
        }],
        filterJunctions: []
    };
    public page = 1;
    public endOfResults = false;
    public parameterValues: any = [];
    public focusParams = false;
    public terminatingTransformations = [];

    public limit = 25;
    private offset = 0;

    constructor(public dialogRef: MatDialogRef<DatasetEditorComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dialog: MatDialog,
                private datasetService: DatasetService,
                private snackBar: MatSnackBar) {
    }

    ngOnInit(): void {
        let limit = null;
        limit = localStorage.getItem('datasetInstanceLimit' + this.datasetInstanceSummary.id);

        this.limit = limit ? Number(limit) : 25;

        if (this.datasetInstanceSummary.transformationInstances.length) {
            const filters = _.filter(this.datasetInstanceSummary.transformationInstances, {type: 'filter'});
            if (filters.length) {
                filters.forEach(filter => {
                    filter.hide = false;
                });
            }
        }

        this.evaluateDataset();
    }

    public addFilter() {
        this.datasetInstanceSummary.transformationInstances.push({
            type: 'filter',
            config: {
                logic: 'AND',
                filters: [{
                    lhsExpression: '',
                    rhsExpression: '',
                    filterType: ''
                }],
                filterJunctions: []
            },
            hide: false
        });
    }

    public removeFilter(index) {
        const existingMultiSort = _.find(this.datasetInstanceSummary.transformationInstances, {type: 'multisort'});
        existingMultiSort.config.sorts.splice(index, 1);
        if (!existingMultiSort.config.sorts.length) {
            _.remove(this.datasetInstanceSummary.transformationInstances, {type: 'multisort'});
        }
        this.evaluateDataset();
    }

    public removeTransformation(transformation, confirm = false) {
        const index = _.findIndex(this.datasetInstanceSummary.transformationInstances, {
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
        transformation.exclude = !transformation.exclude;
        this.evaluateDataset();
    }

    public editTerminatingTransformation(transformation) {
        if (transformation.type === 'summarise') {
            const clonedTransformation = _.clone(transformation);
            // Grab the index of the current transformation - so we know which upstream transformations to exclude
            const existingIndex = _.findIndex(this.datasetInstanceSummary.transformationInstances, {
                type: transformation.type,
                config: transformation.config
            });

            // Exclude all upstream transformations from the evaluate
            this.datasetInstanceSummary.transformationInstances.forEach((instance, index) => {
                if (index >= existingIndex) {
                    instance.exclude = true;
                }
            });

            this.evaluateDataset().then(() => {
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
                reset: !!_.find(this.datasetInstanceSummary.transformationInstances, {type: 'columns'})
            }
        });

        dialogRef.afterClosed().subscribe(columns => {
            if (columns) {
                const fields = _.map(columns, column => {
                    return {title: column.title, name: column.name};
                });

                _.remove(this.datasetInstanceSummary.transformationInstances, {type: 'columns'});

                this.datasetInstanceSummary.transformationInstances.push({
                    type: 'columns',
                    config: {
                        columns: fields
                    }
                });

                this.evaluateDataset();
            }
        });
    }

    public createFormula() {
        const existingFormulas = _.find(this.datasetInstanceSummary.transformationInstances, {type: 'formula'});

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
                _.remove(this.datasetInstanceSummary.transformationInstances, {type: 'formula'});
                this.datasetInstanceSummary.transformationInstances.push({
                    type: 'formula',
                    config: {
                        expressions: formulas
                    }
                });
                this.evaluateDataset();
            }
        });
    }

    public joinData(transformation?) {
        let existingIndex = -1;
        if (transformation) {
            existingIndex = _.findIndex(this.datasetInstanceSummary.transformationInstances, {
                config: transformation.config,
                type: 'join'
            });
        }
        const data: any = {
            environment: this.environment,
            filterFields: this.filterFields,
            parameterValues: _.map(this.parameterValues, param => {
                return {
                    title: param.title,
                    name: param.name,
                    currentValue: this.datasetInstanceSummary.parameterValues[param.name]
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
                if (existingIndex >= 0) {
                    this.datasetInstanceSummary.transformationInstances[existingIndex] = joinTransformation;
                } else {
                    this.datasetInstanceSummary.transformationInstances.push(joinTransformation);
                }
                this.evaluateDataset();
            }
        });
    }

    public applyFilters() {
        const filterTransformations = _.filter(this.datasetInstanceSummary.transformationInstances, {type: 'filter'});
        filterTransformations.forEach(transformation => {
            this.validateFilterJunction(transformation.config);
        });

        this.evaluateDataset();
    }

    public increaseOffset() {
        this.page = this.page + 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.evaluateDataset();
    }

    public decreaseOffset() {
        this.page = this.page <= 1 ? 1 : this.page - 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.evaluateDataset();
    }

    public pageSizeChange(value) {
        this.limit = value;
        this.evaluateDataset();
    }

    public addPagingMarker() {
        this.datasetInstanceSummary.transformationInstances.push({type: 'pagingmarker'});
        this.evaluateDataset();
    }

    public sort(event) {
        const column = event.active;
        const direction = event.direction;

        const existingMultiSort = _.find(this.datasetInstanceSummary.transformationInstances, {type: 'multisort'});
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
                    _.remove(this.datasetInstanceSummary.transformationInstances, {type: 'multisort'});
                }
            }
        } else {
            if (direction) {
                this.datasetInstanceSummary.transformationInstances.push(
                    {
                        type: 'multisort',
                        config: {
                            sorts: [{fieldName: column, direction}]
                        }
                    }
                );
            }
        }
        this.evaluateDataset();
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
                    if (!_.isEqual(clonedConfig, summariseTransformation) && (existingIndex + 1) < this.datasetInstanceSummary.transformationInstances.length) {
                        // Things have changed, ask user to confirm
                        const message = 'You have made changes which may result in upstream transformations becoming incompatible, causing' +
                            ' the evaluation to fail. In order to proceed we will need to remove the affected transformations. Do ' +
                            'you want to proceed?';
                        if (window.confirm(message)) {
                            for (let i = existingIndex; i < this.datasetInstanceSummary.transformationInstances.length; i++) {
                                this.datasetInstanceSummary.transformationInstances.splice(i, 1);
                            }
                            this.datasetInstanceSummary.transformationInstances[existingIndex] = {
                                type: 'summarise',
                                config: summariseTransformation
                            };
                            this.evaluateDataset();
                        }
                    } else {
                        this.datasetInstanceSummary.transformationInstances[existingIndex] = {
                            type: 'summarise',
                            config: summariseTransformation
                        };
                        this.evaluateDataset();
                    }
                } else {
                    this.datasetInstanceSummary.transformationInstances.push({
                        type: 'summarise',
                        config: summariseTransformation
                    });
                    this.evaluateDataset();
                }
            } else {
                // If we get here then the summarise transformation was cancelled - restore if we have one
                if (clonedConfig) {
                    this.datasetInstanceSummary.transformationInstances[existingIndex] = {
                        type: 'summarise',
                        config: clonedConfig
                    };
                    this.datasetInstanceSummary.transformationInstances.forEach((instance, index) => {
                        instance.exclude = false;
                    });
                    this.evaluateDataset();
                }
            }
        });
    }

    public setEvaluatedParameters(parameterValues, evaluate?) {
        this.parameterValues = parameterValues;
        parameterValues.forEach(param => {
            this.datasetInstanceSummary.parameterValues[param.name] = param.value;
        });
        if (evaluate) {
            this.evaluateDataset();
        }
    }

    public getOrdinal(n) {
        const s = ['th', 'st', 'nd', 'rd'];
        const v = n % 100;
        return n + (s[(v - 20) % 10] || s[v] || s[0]);
    }

    public viewFullItemData(data, columnName) {
        this.dialog.open(DatasetEditorPopupComponent, {
            width: '800px',
            height: '400px',
            data: {
                fullData: data,
                columnName
            }
        });
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
        this.evaluatedDatasourceChange.emit(this.datasetInstanceSummary);
        this.setTerminatingTransformations();
        return true;
    }

    private setParameterValues(values) {

    }

    private setTerminatingTransformations() {
        let summarise = 0;
        let join = 0;
        const summariseTotal = _.filter(this.datasetInstanceSummary.transformationInstances, {type: 'summarise'}).length;
        const joinTotal = _.filter(this.datasetInstanceSummary.transformationInstances, {type: 'join'}).length;

        this.terminatingTransformations = _.filter(this.datasetInstanceSummary.transformationInstances, (transformation, index) => {
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
    }

    private hidePreTerminatingFilters(terminatingIndex) {
        this.datasetInstanceSummary.transformationInstances.forEach((transformation, index) => {
            if (transformation.type === 'filter') {
                transformation.hide = transformation.exclude || terminatingIndex > index;
            }
        });
    }

    public evaluateDataset() {
        return this.datasetService.getEvaluatedParameters(this.datasetInstanceSummary)
            .then((values: any) => {
                let paramValues = values;
                if (this.datasetInstanceSummary.parameters.length) {
                    paramValues = values.concat(this.datasetInstanceSummary.parameters);
                }

                this.focusParams = false;
                const parameterValues = {};
                paramValues.forEach(paramValue => {
                    const existParam = _.find(this.parameterValues, {name: paramValue.name});
                    if (!existParam || (existParam && !existParam.value)) {
                        const existingValue = this.datasetInstanceSummary.parameterValues[paramValue.name];
                        paramValue.value = existingValue || paramValue.defaultValue;
                        parameterValues[paramValue.name] = paramValue;
                        this.focusParams = !existingValue;
                    } else {
                        parameterValues[existParam.name] = existParam;
                    }
                });

                this.parameterValues = _.values(parameterValues);
                this.setEvaluatedParameters(this.parameterValues);

                if (this.datasetInstanceSummary && this.datasetInstanceSummary.id) {
                    localStorage.setItem('datasetInstanceLimit' + this.datasetInstanceSummary.id, (this.limit).toString());
                } else if (this.datasetInstanceSummary.instanceKey) {
                    localStorage.setItem('datasetInstanceLimit' + this.datasetInstanceSummary.instanceKey, (this.limit).toString());
                }

                // Clone the current instance object so we can remove any excluded transformations, without affecting
                // the original object which the gui uses to draw transformation items.
                const clonedDatasetInstance = _.merge({}, this.datasetInstanceSummary);
                _.remove(clonedDatasetInstance.transformationInstances, {exclude: true});

                return this.datasetService.evaluateDataset(
                    clonedDatasetInstance,
                    this.offset,
                    this.limit
                ).then(dataset => {
                    this.dataset = dataset;
                    return this.loadData();
                }).catch(err => {
                    if (err.error && err.error.message) {
                        const message = err.error.message.toLowerCase();
                        if (!message.includes('parameter') && !message.includes('required')) {
                            this.snackBar.open(err.error.message, 'Close', {
                                verticalPosition: 'top'
                            });
                        }
                    }
                    // If the evaluate fails we still want to publish the instance and set the terminating transformations
                    this.evaluatedDatasourceChange.emit(this.datasetInstanceSummary);
                    this.setTerminatingTransformations();
                });
            });

    }

    private _removeTransformation(transformation, index?) {
        if (index >= 0) {
            // If a current index has been supplied, reset all the pre transformation hidden fields prior to eval.
            for (let i = 0; i < index; i++) {
                this.datasetInstanceSummary.transformationInstances[i].hide = false;
            }
        }

        _.remove(this.datasetInstanceSummary.transformationInstances, _.omitBy({
            type: transformation.type,
            config: transformation.type !== 'pagingmarker' ? transformation.config : null
        }, _.isNil));
        this.evaluateDataset();
    }

}

@Component({
    selector: 'ki-dataset-editor-popup',
    templateUrl: 'dataset-editor-popup.html',
    host: {class: 'dialog-wrapper'}
})
export class DatasetEditorPopupComponent {

    public fullData: string;
    public columnName: string;

    constructor(@Inject(MAT_DIALOG_DATA) public data: DatasetEditorPopupComponent) {

        this.fullData = this.data.fullData;
        this.columnName = this.data.columnName;
    }

}
