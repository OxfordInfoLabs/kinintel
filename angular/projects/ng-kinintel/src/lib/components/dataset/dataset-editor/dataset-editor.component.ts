import {Component, Inject, Input, OnInit, Output, EventEmitter, OnDestroy} from '@angular/core';
import * as _ from 'lodash';
import {MAT_DIALOG_DATA, MatDialog, MatDialogRef} from '@angular/material/dialog';
import {DatasetSummariseComponent} from './dataset-summarise/dataset-summarise.component';
import {DatasetAddJoinComponent} from './dataset-add-join/dataset-add-join.component';
import {DatasetCreateFormulaComponent} from './dataset-create-formula/dataset-create-formula.component';
import {DatasetColumnSettingsComponent} from './dataset-column-settings/dataset-column-settings.component';
import {DatasetService} from '../../../services/dataset.service';
import {MatSnackBar} from '@angular/material/snack-bar';
import {DatasetFilterComponent} from './dataset-filters/dataset-filter/dataset-filter.component';
import {
    DatasetAddParameterComponent
} from './dataset-parameter-values/dataset-add-parameter/dataset-add-parameter.component';
import {BehaviorSubject, Subject, Subscription} from 'rxjs';
import * as moment from 'moment';

@Component({
    selector: 'ki-dataset-editor',
    templateUrl: './dataset-editor.component.html',
    styleUrls: ['./dataset-editor.component.sass']
})
export class DatasetEditorComponent implements OnInit, OnDestroy {

    @Input() datasetInstanceSummary: any;
    @Input() environment: any = {};
    @Input() admin: boolean;
    @Input() dashboardParameters: any;

    @Output() dataLoaded = new EventEmitter<any>();
    @Output() datasetInstanceSummaryChange = new EventEmitter();

    public dataset: any;
    public tableData = [];
    public showFilters = false;
    public showParameters = false;
    public displayedColumns = [];
    public _ = _;
    public Array = Array;
    public Object = Object;
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
    public String = String;
    public longRunning = false;
    public sideOpen = false;
    public openSide = new BehaviorSubject(false);

    public limit = 25;
    private offset = 0;
    private evaluateSub: Subscription;

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

        if (Array.isArray(this.datasetInstanceSummary.parameterValues)) {
            this.datasetInstanceSummary.parameterValues = {};
        }

        this.evaluateDataset();

        this.openSide.subscribe((open: boolean) => {
            if (open) {
                document.getElementById('sidebarWrapper').classList.add('z-20');
                document.getElementById('sidebarWrapper').classList.remove('-z-10');
            } else {
                setTimeout(() => {
                    document.getElementById('sidebarWrapper').classList.add('-z-10');
                    document.getElementById('sidebarWrapper').classList.remove('z-20');
                }, 700);
            }
            this.sideOpen = open;
        });
    }

    ngOnDestroy() {
        if (this.evaluateSub) {
            this.evaluateSub.unsubscribe();
        }
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
        this.showFilters = true;

        setTimeout(() => {
            const filters = document.getElementById('datasetFilters');
            filters.scrollTo({top: filters.scrollHeight, behavior: 'smooth'});
        }, 0);
    }

    public getFilterString(config) {
        const filter = config.filters[0];
        if (config.filters.length) {
            if (_.every(filter)) {
                const type = DatasetFilterComponent.getFilterType(filter.filterType);
                if (type) {
                    const lhsExpression = filter.lhsExpression.replace('[[', '').replace(']]', '');
                    return `${_.startCase(lhsExpression)} ${type.string} ${filter.rhsExpression}`;
                }
                return '';
            }
        }
        return '';
    }

    public removeFilter(index) {
        const existingMultiSort = _.find(this.datasetInstanceSummary.transformationInstances, {type: 'multisort'});
        existingMultiSort.config.sorts.splice(index, 1);
        if (!existingMultiSort.config.sorts.length) {
            _.remove(this.datasetInstanceSummary.transformationInstances, {type: 'multisort'});
        }
        this.evaluateDataset();
    }

    public removeTransformation(transformation, confirm = false, index?) {
        if (index === undefined) {
            index = _.findIndex(this.datasetInstanceSummary.transformationInstances, {
                type: transformation.type,
                config: transformation.config
            });
        }

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

    public async editTerminatingTransformation(transformation) {
        const [clonedTransformation, existingIndex] = await this.excludeUpstreamTransformations(transformation);
        const dataLoadedSub = this.dataLoaded.subscribe(res => {
            if (transformation.type === 'summarise') {
                this.summariseData(clonedTransformation.config, existingIndex);
            }
            if (transformation.type === 'join') {
                this.joinData(clonedTransformation, existingIndex);
            }
            if (transformation.type === 'formula') {
                this.createFormula(clonedTransformation, existingIndex);
            }
            if (transformation.type === 'columns') {
                this.editColumnSettings(clonedTransformation, existingIndex);
            }
            dataLoadedSub.unsubscribe();
        });
    }

    public exportData() {

    }

    public async editColumnSettings(existingTransformation?, existingIndex?) {
        const clonedColumns = existingTransformation ? _.clone(existingTransformation.config.columns) : null;

        const columnSettings = [];

        if (clonedColumns) {
            clonedColumns.forEach(column => {
                column.selected = true;
                columnSettings.push(column);
            });
        }

        this.filterFields.forEach(field => {
            if (!_.find(columnSettings, {name: field.name})) {
                columnSettings.push(field);
            }
        });

        const dialogRef = this.dialog.open(DatasetColumnSettingsComponent, {
            width: '1000px',
            height: '800px',
            data: {
                columns: columnSettings,
                reset: !!_.find(this.datasetInstanceSummary.transformationInstances, {type: 'columns'}),
                resetFields: _.clone(this.filterFields)
            }
        });

        dialogRef.afterClosed().subscribe(columns => {
            if (columns) {
                const fields = _.map(columns, column => {
                    return {title: column.title, name: column.name};
                });

                if (existingIndex > -1) {
                    this.datasetInstanceSummary.transformationInstances[existingIndex] = {
                        type: 'columns',
                        config: {
                            columns: fields
                        }
                    };
                } else {
                    this.datasetInstanceSummary.transformationInstances.push({
                        type: 'columns',
                        config: {
                            columns: fields
                        }
                    });
                }

                this.evaluateDataset();
            } else {
                if (clonedColumns) {
                    this.datasetInstanceSummary.transformationInstances[existingIndex] = {
                        type: 'columns',
                        config: {
                            columns: clonedColumns
                        }
                    };
                    this.datasetInstanceSummary.transformationInstances.forEach((instance, index) => {
                        instance.exclude = false;
                    });
                    this.evaluateDataset();
                }
            }
        });
    }

    public createFormula(existingTransformation?, existingIndex?) {
        const clonedExpressions = existingTransformation ? _.clone(existingTransformation.config.expressions) : null;
        const dialogRef = this.dialog.open(DatasetCreateFormulaComponent, {
            width: '900px',
            height: '800px',
            data: {
                allColumns: this.filterFields,
                formulas: existingTransformation ? existingTransformation.config.expressions : [{}]
            }
        });

        dialogRef.afterClosed().subscribe(formula => {
            if (formula) {
                if (existingIndex > -1) {
                    this.datasetInstanceSummary.transformationInstances[existingIndex] = {
                        type: 'formula',
                        config: {
                            expressions: formula
                        }
                    };
                } else {
                    this.datasetInstanceSummary.transformationInstances.push({
                        type: 'formula',
                        config: {
                            expressions: formula
                        }
                    });
                }

                this.evaluateDataset();
            } else {
                // Formula was cancelled, restore if we have one
                if (clonedExpressions) {
                    this.datasetInstanceSummary.transformationInstances[existingIndex] = {
                        type: 'formula',
                        config: {
                            expressions: clonedExpressions
                        }
                    };
                    this.datasetInstanceSummary.transformationInstances.forEach((instance, index) => {
                        instance.exclude = false;
                    });
                    this.evaluateDataset();
                }
            }
        });
    }

    public joinData(transformation?, existingIndex?) {
        const clonedTransformation = transformation ? _.clone(transformation) : null;
        const data: any = {
            admin: this.admin,
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
                this.evaluateDataset(true);
            } else {
                if (clonedTransformation) {
                    this.datasetInstanceSummary.transformationInstances[existingIndex] = clonedTransformation;
                    this.datasetInstanceSummary.transformationInstances.forEach((instance, index) => {
                        instance.exclude = false;
                    });
                    this.evaluateDataset();
                }
            }
        });
    }

    public applyFilters() {
        const filterTransformations = _.filter(this.datasetInstanceSummary.transformationInstances, {type: 'filter'});
        filterTransformations.forEach(transformation => {
            this.validateFilterJunction(transformation.config);
        });

        this.evaluateDataset(true);
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
        this.evaluateDataset(true);
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
        this.evaluateDataset(true);
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
                            this.evaluateDataset(true);
                        }
                    } else {
                        this.datasetInstanceSummary.transformationInstances[existingIndex] = {
                            type: 'summarise',
                            config: summariseTransformation
                        };
                        this.evaluateDataset(true);
                    }
                } else {
                    this.datasetInstanceSummary.transformationInstances.push({
                        type: 'summarise',
                        config: summariseTransformation
                    });
                    this.evaluateDataset(true);
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

    public removeParameter(parameter) {
        const message = 'Are you sure you would like to remove this parameter. This may cause some dashboard items ' +
            'to fail.';
        if (window.confirm(message)) {
            _.remove(this.parameterValues, {name: parameter.name});
            _.remove(this.datasetInstanceSummary.parameters, {name: parameter.name});
            this.evaluateDataset();
        }
    }

    public addParameter(existingParameter?, parameterValueIndex?) {
        let clonedParameter = null;
        if (existingParameter) {
            clonedParameter = _.clone(existingParameter);
        }
        if (!this.showParameters) {
            this.showParameters = true;
        }
        const dialogRef = this.dialog.open(DatasetAddParameterComponent, {
            width: '600px',
            height: '600px',
            data: {
                parameter: clonedParameter
            }
        });
        dialogRef.afterClosed().subscribe(parameter => {
            if (parameter) {
                if (!clonedParameter) {
                    parameter.value = parameter.defaultValue || '';

                    this.parameterValues.push(parameter);
                    this.datasetInstanceSummary.parameters.push(parameter);
                } else {
                    if (!clonedParameter.value) {
                        parameter.value = parameter.defaultValue || '';
                    }
                    const diParamIndex = _.findIndex(this.datasetInstanceSummary.parameters, existingParameter);
                    this.parameterValues[parameterValueIndex] = parameter;
                    this.datasetInstanceSummary.parameters[diParamIndex] = parameter;
                }

            }
        });
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

    public booleanUpdate(event, parameter) {
        parameter.value = event.checked;
        this.evaluateDataset(true);
    }

    public changeDateType(event, parameter, value) {
        event.stopPropagation();
        event.preventDefault();
        parameter._dateType = value;
    }

    public updatePeriodValue(value, period, parameter) {
        parameter.value = `${value}_${period}_AGO`;
        this.evaluateDataset(true);
    }

    private excludeUpstreamTransformations(transformation) {
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

        return this.evaluateDataset().then(() => {
            return [clonedTransformation, existingIndex];
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
        this.datasetInstanceSummaryChange.emit(this.datasetInstanceSummary);
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

    public evaluateDataset(resetPager?) {
        if (resetPager) {
            this.resetPager();
        }

        return this.datasetService.getEvaluatedParameters(this.datasetInstanceSummary)
            .then((values: any) => {
                const paramValues = values.map(value => {
                    value._locked = !_.find(this.datasetInstanceSummary.parameters, {name: value.name});
                    return value;
                });

                this.focusParams = false;
                const parameterValues = {};
                paramValues.forEach(paramValue => {
                    const existParam = _.find(this.parameterValues, {name: paramValue.name});
                    if (!existParam || (existParam && _.isNil(existParam.value))) {
                        const existingValue = this.datasetInstanceSummary.parameterValues[paramValue.name];
                        paramValue.value = _.isNil(existingValue) ? paramValue.defaultValue : existingValue;
                        parameterValues[paramValue.name] = paramValue;
                        this.focusParams = !existingValue;
                    } else {
                        parameterValues[existParam.name] = existParam;
                    }
                });
                this.parameterValues = _.values(parameterValues);
                this.parameterValues.forEach(param => {
                    if (param.type === 'date' || param.type === 'datetime') {
                        if (!param._dateType && param.value) {
                            const isPeriod = param.value.includes('AGO');
                            param._dateType = isPeriod ? 'period' : 'picker';
                            if (isPeriod) {
                                const periodValues = param.value.split('_');
                                param._periodValue = periodValues[0];
                                param._period = periodValues[1];
                            }
                        }
                    }
                    this.datasetInstanceSummary.parameterValues[param.name] = param.value;
                });

                if (this.datasetInstanceSummary && this.datasetInstanceSummary.id) {
                    localStorage.setItem('datasetInstanceLimit' + this.datasetInstanceSummary.id, (this.limit).toString());
                } else if (this.datasetInstanceSummary.instanceKey) {
                    localStorage.setItem('datasetInstanceLimit' + this.datasetInstanceSummary.instanceKey, (this.limit).toString());
                }

                // Clone the current instance object, so we can remove any excluded transformations, without affecting
                // the original object which the gui uses to draw transformation items.
                const clonedDatasetInstance = _.merge({}, this.datasetInstanceSummary);
                _.remove(clonedDatasetInstance.transformationInstances, {exclude: true});

                this.parameterValues.forEach(param => {
                    let value = param.value;
                    if (_.isString(param.value) && param.value.includes('{{')) {
                        if (this.dashboardParameters && Object.keys(this.dashboardParameters).length) {
                            const paramKey = param.value.replace('{{', '').replace('}}', '');
                            if (this.dashboardParameters[paramKey]) {
                                value = this.dashboardParameters[paramKey].value;
                            }
                        }
                    }

                    clonedDatasetInstance.parameterValues[param.name] = value;
                });

                // Merge in global dashboard params
                if (this.dashboardParameters && Object.keys(this.dashboardParameters).length) {
                    _.forEach(this.dashboardParameters, (param, name) => {
                        if (!clonedDatasetInstance.parameterValues[name]) {
                            clonedDatasetInstance.parameterValues[name] = param.value;
                        }
                    });
                }

                const trackingKey = Date.now() + (Math.random() + 1).toString(36).substr(2, 5);
                let finished = false;

                // Ensure we send back any dates in correct format
                const datetimeParams = _.filter(clonedDatasetInstance.parameters, param => {
                    return param.type === 'date' || param.type === 'datetime';
                });
                datetimeParams.forEach(param => {
                    const value = clonedDatasetInstance.parameterValues[param.name];
                    if (value && !value.includes('AGO')) {
                        clonedDatasetInstance.parameterValues[param.name] = moment(value).format('YYYY-MM-DD HH:mm:ss');
                    }
                });

                this.evaluateSub = this.datasetService.evaluateDatasetWithTracking(
                    clonedDatasetInstance,
                    String(this.offset),
                    String(this.limit),
                    trackingKey
                ).subscribe(dataset => {
                    finished = true;
                    this.dataset = dataset;
                    return this.loadData();
                }, err => {
                    finished = true;
                    if (err.error && err.error.message) {
                        const message = err.error.message.toLowerCase();
                        if (!message.includes('parameter') && !message.includes('required')) {
                            this.snackBar.open(err.error.message, 'Close', {
                                verticalPosition: 'top'
                            });
                        }
                    }
                    // If the evaluate fails we still want to publish the instance and set the terminating transformations
                    this.datasetInstanceSummaryChange.emit(this.datasetInstanceSummary);
                    this.setTerminatingTransformations();
                    return true;
                });

                setTimeout(() => {
                    if (!finished) {
                        this.longRunning = true;
                        this.evaluateSub.unsubscribe();
                        const resultsSub = this.datasetService.getDataTrackingResults(trackingKey)
                            .subscribe((results: any) => {
                                if (results.status === 'COMPLETED') {
                                    resultsSub.unsubscribe();
                                    this.dataset = results.result;
                                    this.longRunning = false;
                                    return this.loadData();
                                } else if (results.status === 'FAILED') {
                                    resultsSub.unsubscribe();
                                    this.longRunning = false;
                                    const errorMessage = results.result;
                                    if (errorMessage) {
                                        const message = errorMessage.toLowerCase();
                                        if (!message.includes('parameter') && !message.includes('required')) {
                                            this.snackBar.open(errorMessage, 'Close', {
                                                verticalPosition: 'top'
                                            });
                                        }
                                    }
                                    // If the evaluate fails we still want to publish the instance and set the terminating transformations
                                    this.datasetInstanceSummaryChange.emit(this.datasetInstanceSummary);
                                    this.setTerminatingTransformations();
                                }
                            });
                    }
                }, 3000);
            });

    }

    private _removeTransformation(transformation, index?) {
        if (index >= 0) {
            // If a current index has been supplied, reset all the pre transformation hidden fields prior to eval.
            for (let i = 0; i < index; i++) {
                this.datasetInstanceSummary.transformationInstances[i].hide = false;
            }

            this.datasetInstanceSummary.transformationInstances.splice(index, 1);
        } else {
            _.remove(this.datasetInstanceSummary.transformationInstances, _.omitBy({
                type: transformation.type,
                config: transformation.type !== 'pagingmarker' ? transformation.config : null
            }, _.isNil));
        }

        this.evaluateDataset(true);
    }

    private resetPager() {
        this.offset = 0;
        this.page = 1;
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
        this.columnName = _.startCase(this.data.columnName);
    }

}
