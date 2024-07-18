import {Component, Inject, Input, OnInit, Output, EventEmitter, OnDestroy} from '@angular/core';
import * as lodash from 'lodash';

const _ = lodash.default;
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialog as MatDialog,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import {DatasetSummariseComponent} from './dataset-summarise/dataset-summarise.component';
import {DatasetAddJoinComponent} from './dataset-add-join/dataset-add-join.component';
import {DatasetCreateFormulaComponent} from './dataset-create-formula/dataset-create-formula.component';
import {DatasetColumnSettingsComponent} from './dataset-column-settings/dataset-column-settings.component';
import {DatasetService} from '../../../services/dataset.service';
import {MatLegacySnackBar as MatSnackBar} from '@angular/material/legacy-snack-bar';
import {DatasetFilterComponent} from './dataset-filters/dataset-filter/dataset-filter.component';
import {
    DatasetAddParameterComponent
} from './dataset-parameter-values/dataset-add-parameter/dataset-add-parameter.component';
import {BehaviorSubject, Subject, Subscription} from 'rxjs';
import moment from 'moment';
import {
    UpstreamChangesConfirmationComponent
} from '../dataset-editor/upstream-changes-confirmation/upstream-changes-confirmation.component';
import {
    DatasetNameDialogComponent
} from '../dataset-editor/dataset-name-dialog/dataset-name-dialog.component';
import {CdkDragDrop, moveItemInArray} from '@angular/cdk/drag-drop';
import {
    MoveTransformationConfirmationComponent
} from '../dataset-editor/move-transformation-confirmation/move-transformation-confirmation.component';
import {
    SaveAsQueryComponent
} from '../dataset-editor/save-as-query/save-as-query.component';
import {
    ShareQueryComponent
} from '../dataset-editor/share-query/share-query.component';

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
    @Input() dashboardLayoutSettings: any;
    @Input() accountId;
    @Input() newTitle;
    @Input() newDescription;

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
            rhsExpression: [],
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
    public offset = 0;

    private evaluateSub: Subscription;
    private resultsSub: Subscription;
    private datasetTitle: string;

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
                    filter.hide = true;
                });
            }
        }

        if (Array.isArray(this.datasetInstanceSummary.parameterValues)) {
            this.datasetInstanceSummary.parameterValues = {};
        }

        if (!this.datasetInstanceSummary.id) {
            this.datasetTitle = this.datasetInstanceSummary.title;
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

    public async shareQuery() {
        this.dialog.open(ShareQueryComponent, {
            width: '800px',
            height: '950px',
            data: {
                datasetInstance: this.datasetInstanceSummary
            }
        }).afterClosed().subscribe(res => {

        });
    }

    public async saveAsQuery() {
        const newDatasetInstance: any = {
            title: this.datasetInstanceSummary.title || 'Stored Query' + ' COPY',
            summary: '',
            description: '',
            categories: this.datasetInstanceSummary.categories,
            datasourceInstanceKey: this.datasetInstanceSummary.datasourceInstanceKey,
            datasetInstanceId: this.datasetInstanceSummary.datasetInstanceId,
            tags: this.datasetInstanceSummary.tags,
            parameterValue: this.datasetInstanceSummary.parameterValues,
            parameters: this.datasetInstanceSummary.parameters
        };
        this.dialog.open(SaveAsQueryComponent, {
            width: '700px',
            height: '800px',
            data: {
                datasetInstanceSummary: newDatasetInstance,
                transformations: this.datasetInstanceSummary.transformationInstances
            }
        }).afterClosed().subscribe(async transformationInstances => {
            if (transformationInstances) {
                newDatasetInstance.transformationInstances = transformationInstances;
                await this.datasetService.saveDataset(newDatasetInstance, this.accountId);
                this.snackBar.open('Dataset successfully copied.', 'Close', {
                    verticalPosition: 'top',
                    duration: 3000
                });
            }
        });
    }

    public drop(event: CdkDragDrop<any>) {
        this.dialog.open(MoveTransformationConfirmationComponent, {
            width: '700px',
            height: '275px'
        }).afterClosed().subscribe(res => {
            if (res === 'proceed') {
                moveItemInArray(this.datasetInstanceSummary.transformationInstances, event.previousContainer.data.index, event.container.data.index);
                this.evaluateDataset();
            }
        });
    }

    public async insertTransformation(index: number, transformationType: string, nextTransformation: any) {
        await this.excludeUpstreamTransformations(nextTransformation);

        switch (transformationType) {
            case 'columns':
                this.editColumnSettings(null, null, index);
                break;
            case 'formula':
                this.createFormula(null, null, index);
                break;
            case 'join':
                this.joinData(null, null, index);
                break;
            case 'summarise':
                this.summariseData(null, null, index);
                break;
            case 'filter':
                this.addFilter(index);
                break;
        }
    }

    public addFilter(insertIndex?: number) {
        _.forEach(this.datasetInstanceSummary.transformationInstances || [], transformation => {
            transformation.hide = true;
        });

        const filterTransformation = {
            type: 'filter',
            config: {
                logic: 'AND',
                filters: [{
                    lhsExpression: '',
                    rhsExpression: [],
                    filterType: ''
                }],
                filterJunctions: []
            },
            hide: false
        };

        if (!_.isNil(insertIndex) && insertIndex >= 0) {
            this.datasetInstanceSummary.transformationInstances.splice(insertIndex, 0, filterTransformation);
        } else {
            this.datasetInstanceSummary.transformationInstances.push(filterTransformation);
        }

        this.showFilters = true;
        this.setTerminatingTransformations();

        setTimeout(() => {
            const filters = document.getElementById('datasetFilters');
            if (filters) {
                filters.scrollTo({top: filters.scrollHeight, behavior: 'smooth'});
            }
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

    public enableAllTransformation() {
        this.datasetInstanceSummary.transformationInstances.forEach((transformation: any) => {
            transformation.exclude = false;
        });
        this.evaluateDataset();
    }

    public disableTransformation(transformation, index) {
        transformation.exclude = !transformation.exclude;
        this.evaluateDataset();
    }

    public showTransformationDetail(transformation) {
        const clone = _.clone(transformation);
        this.terminatingTransformations.forEach(item => {
            item._showDetail = false;
        });
        if (!clone._showDetail) {
            transformation._showDetail = true;
        }
    }

    public async editTerminatingTransformation(transformation) {
        const [clonedTransformation, existingIndex] = await this.excludeUpstreamTransformations(transformation);
        if (transformation.type === 'filter') {
            const hiddenValue = !transformation.hide;
            const filters = _.filter(this.datasetInstanceSummary.transformationInstances, {type: 'filter'});
            filters.forEach(filter => {
                filter.hide = true;
            });
            transformation.hide = hiddenValue;
            this.showFilters = !hiddenValue;
        } else {
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
        }
    }

    public exportData() {

    }

    public async editColumnSettings(existingTransformation?: any, existingIndex?: number, insertIndex?: number) {
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
            disableClose: true,
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

                const columnTransformation = {
                    type: 'columns',
                    config: {
                        columns: fields
                    }
                };

                if (!_.isNil(existingIndex) && existingIndex > -1) {
                    this.datasetInstanceSummary.transformationInstances[existingIndex] = columnTransformation;
                } else if (!_.isNil(insertIndex) && insertIndex >= 0) {
                    this.datasetInstanceSummary.transformationInstances.splice(insertIndex, 0, columnTransformation);
                } else {
                    this.datasetInstanceSummary.transformationInstances.push(columnTransformation);
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

    public createFormula(existingTransformation?: any, existingIndex?: number, insertIndex?: number) {
        const clonedExpressions = existingTransformation ? _.clone(existingTransformation.config.expressions) : null;
        const dialogRef = this.dialog.open(DatasetCreateFormulaComponent, {
            width: '900px',
            height: '800px',
            disableClose: true,
            data: {
                allColumns: this.filterFields,
                formulas: existingTransformation ? existingTransformation.config.expressions : [{}]
            }
        });

        dialogRef.afterClosed().subscribe(formula => {
            if (formula) {
                const formulaTransformation = {
                    type: 'formula',
                    config: {
                        expressions: formula
                    }
                };

                if (!_.isNil(existingIndex) && existingIndex >= 0) {
                    this.datasetInstanceSummary.transformationInstances[existingIndex] = formulaTransformation;
                } else if (!_.isNil(insertIndex) && insertIndex >= 0) {
                    this.datasetInstanceSummary.transformationInstances.splice(insertIndex, 0, formulaTransformation);
                } else {
                    this.datasetInstanceSummary.transformationInstances.push(formulaTransformation);
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

    public joinData(transformation?: any, existingIndex?: number, insertIndex?: number) {
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
            disableClose: true,
            data,
        });

        dialogRef.afterClosed().subscribe(joinTransformation => {
            if (joinTransformation) {
                if (!_.isNil(existingIndex) && existingIndex >= 0) {
                    this.datasetInstanceSummary.transformationInstances[existingIndex] = joinTransformation;
                } else if (!_.isNil(insertIndex) && insertIndex >= 0) {
                    this.datasetInstanceSummary.transformationInstances.splice(insertIndex, 0, joinTransformation);
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
        localStorage.setItem('datasetInstanceLimit' + this.datasetInstanceSummary.id, this.limit.toString());
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

    public summariseData(config?: any, existingIndex?: number, insertIndex?: number) {
        const clonedConfig = config ? _.clone(config) : null;
        const summariseDialog = this.dialog.open(DatasetSummariseComponent, {
            width: '1100px',
            height: '900px',
            disableClose: true,
            data: {
                availableColumns: this.filterFields,
                config: config ? config : null,
                originDataItemTitle: this.datasetInstanceSummary.originDataItemTitle
            }
        });

        summariseDialog.afterClosed().subscribe(summariseTransformation => {
            if (summariseTransformation) {
                // this.datasetInstanceSummary.transformationInstances.forEach((instance, index) => {
                //     instance.exclude = false;
                // });
                if (!_.isNil(existingIndex) && existingIndex >= 0) {
                    // Check if anything has changed
                    if (!_.isEqual(clonedConfig, summariseTransformation) && (existingIndex + 1) < this.datasetInstanceSummary.transformationInstances.length) {
                        // Things have changed, ask user to confirm

                        this.dialog.open(UpstreamChangesConfirmationComponent, {
                            width: '700px',
                            height: '275px'
                        }).afterClosed().subscribe(res => {
                            if (res) {
                                if (res === 'remove') {
                                    for (let i = existingIndex; i < this.datasetInstanceSummary.transformationInstances.length; i++) {
                                        this.datasetInstanceSummary.transformationInstances.splice(i, 1);
                                    }
                                }

                                this.datasetInstanceSummary.transformationInstances[existingIndex] = {
                                    type: 'summarise',
                                    config: summariseTransformation
                                };
                                this.evaluateDataset(true);
                            }
                        });
                    } else {
                        this.datasetInstanceSummary.transformationInstances[existingIndex] = {
                            type: 'summarise',
                            config: summariseTransformation
                        };
                        this.evaluateDataset(true);
                    }
                } else if (!_.isNil(insertIndex) && insertIndex >= 0) {
                    this.datasetInstanceSummary.transformationInstances.splice(insertIndex, 0, {
                        type: 'summarise',
                        config: summariseTransformation
                    });
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
            width: '750px',
            height: '850px',
            disableClose: true,
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
    }

    public changeDateType(event, parameter, value) {
        event.stopPropagation();
        event.preventDefault();
        parameter._dateType = value;
    }

    public updatePeriodValue(value, period, parameter) {
        parameter.value = `${value}_${period}_AGO`;
    }

    public cancelEvaluate() {
        if (this.resultsSub) {
            this.resultsSub.unsubscribe();
        }

        this.longRunning = false;
    }

    public save() {
        if (!this.datasetInstanceSummary.id && (this.datasetInstanceSummary.title === this.datasetTitle)) {
            const dialogRef = this.dialog.open(DatasetNameDialogComponent, {
                width: '475px',
                height: '150px',
                disableClose: true,
                data: {
                    title: this.newTitle,
                    description: this.newDescription
                }
            });
            dialogRef.afterClosed().subscribe(res => {
                if (res) {
                    this.datasetInstanceSummary.title = res;
                    this.saveDataset();
                }
            });
        } else {
            this.saveDataset();
        }
    }

    private async saveDataset() {
        await this.datasetService.saveDataset(this.datasetInstanceSummary, this.accountId);
        this.snackBar.open('Dataset successfully saved.', 'Close', {
            verticalPosition: 'top',
            duration: 3000
        });
    }

    public async excludeUpstreamTransformations(transformation) {
        const clonedTransformation = _.clone(transformation);
        // Grab the index of the current transformation - so we know which upstream transformations to exclude
        const existingIndex = _.findIndex(this.datasetInstanceSummary.transformationInstances, {
            type: transformation.type,
            config: transformation.config
        });

        // Exclude all upstream transformations from the evaluate
        this.datasetInstanceSummary.transformationInstances.forEach((instance, index) => {
            if ((index >= existingIndex) && instance.type !== 'multisort') {
                instance.exclude = true;
            }
        });

        await this.evaluateDataset();
        return [clonedTransformation, existingIndex];
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

        if (!this.tableData.length) {
            const data = {};
            data[this.dataset.columns[0].name] = 'No Results';
            this.tableData = [data];
        }

        this.endOfResults = this.dataset.allData.length < this.limit;
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
                transformation._label = this.getOrdinal(summarise) + ' Summarise';
                transformation._disable = summarise < summariseTotal;
                transformation._active = summarise === summariseTotal;

                return true;
            }
            if (transformation.type === 'join') {
                join++;
                transformation._label = transformation.config.joinedDataItemTitle || this.getOrdinal(join) + ' Join';
                transformation._disable = join < joinTotal;
                transformation._active = join === joinTotal;

                return true;
            }
            if (transformation.type === 'pagingmarker') {
                transformation._label = 'Paging Marker';
                transformation._disable = false;
                transformation._active = true;
                return true;
            }
            if (transformation.type === 'formula') {
                transformation._label = transformation.config.expressions[0].fieldTitle;
                transformation._disable = false;
                transformation._active = true;
                return true;
            }
            if (transformation.type === 'columns') {
                transformation._label = 'Columns Update';
                transformation._disable = false;
                transformation._active = true;
                return true;
            }
            if (transformation.type === 'filter') {
                transformation._label = 'Filter';
                transformation._disable = false;
                transformation._active = true;
                return true;
            }
            return false;
        });
    }

    public async evaluateDataset(resetPager?) {
        return new Promise(async (resolve, reject) => {
            if (resetPager) {
                this.resetPager();
            }

            const clonedDatasetInstance = await this.prepareDatasetInstanceForEvaluation();

            const trackingKey = Date.now() + (Math.random() + 1).toString(36).substr(2, 5);
            let finished = false;

            if (this.dashboardLayoutSettings) {
                this.dashboardLayoutSettings.limit = this.limit;
                this.dashboardLayoutSettings.offset = this.offset;
            }

            this.evaluateSub = this.datasetService.evaluateDatasetWithTracking(
                clonedDatasetInstance,
                String(this.offset),
                String(this.limit),
                trackingKey
            ).subscribe(async dataset => {
                finished = true;
                this.dataset = dataset;
                this.loadData();
                resolve(true);
            }, err => {
                finished = true;
                if (err.error && err.error.message) {
                    let message = err.error.message.toLowerCase();
                    if (message.includes('sql')) {
                        message = 'An error occurred processing your query. Please check any SQL syntax and try again.';
                    }
                    if (!message.includes('parameter') && !message.includes('required')) {
                        this.snackBar.open(err.error.message, 'Close', {
                            verticalPosition: 'top',
                            duration: 5000
                        });
                    }
                }
                // If the evaluate fails we still want to publish the instance and set the terminating transformations
                this.datasetInstanceSummaryChange.emit(this.datasetInstanceSummary);
                this.setTerminatingTransformations();

                return reject(false);
            });

            setTimeout(() => {
                if (!finished) {
                    this.longRunning = true;
                    this.evaluateSub.unsubscribe();
                    this.resultsSub = this.datasetService.getDataTrackingResults(trackingKey)
                        .subscribe((results: any) => {
                            if (results.status === 'COMPLETED') {
                                this.resultsSub.unsubscribe();
                                this.dataset = results.result;
                                this.longRunning = false;
                                this.loadData();
                                resolve(true);
                            } else if (results.status === 'FAILED') {
                                this.resultsSub.unsubscribe();
                                this.longRunning = false;
                                const errorMessage = results.result;
                                if (errorMessage) {
                                    const message = errorMessage.toLowerCase();
                                    if (!message.includes('parameter') && !message.includes('required')) {
                                        this.snackBar.open(errorMessage, 'Close', {
                                            verticalPosition: 'top',
                                            duration: 5000
                                        });
                                    }
                                }
                                // If the evaluate fails we still want to publish the instance and set the terminating transformations
                                this.datasetInstanceSummaryChange.emit(this.datasetInstanceSummary);
                                this.setTerminatingTransformations();
                                resolve(false);
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
                if (this.datasetInstanceSummary.transformationInstances[i].type !== 'filter') {
                    this.datasetInstanceSummary.transformationInstances[i].hide = false;
                }
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

    private async prepareDatasetInstanceForEvaluation() {
        const values: any = await this.datasetService.getEvaluatedParameters(this.datasetInstanceSummary);

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

        if (this.datasetInstanceSummary && this.datasetInstanceSummary.id) {
            localStorage.setItem('datasetInstanceLimit' + this.datasetInstanceSummary.id, (this.limit).toString());
        } else if (this.datasetInstanceSummary.instanceKey) {
            localStorage.setItem('datasetInstanceLimit' + this.datasetInstanceSummary.instanceKey, (this.limit).toString());
        }

        const sort = _.find(this.datasetInstanceSummary.transformationInstances, {type: 'multisort'});
        if (sort) {
            const clone = _.clone(sort);
            _.remove(this.datasetInstanceSummary.transformationInstances, sort);
            this.datasetInstanceSummary.transformationInstances.push(clone);
        }

        // Clone the current instance object, so we can remove any excluded transformations, without affecting
        // the original object which the gui uses to draw transformation items.
        const clonedDatasetInstance = _.merge({}, this.datasetInstanceSummary);
        _.remove(clonedDatasetInstance.transformationInstances, {exclude: true});

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
            if (this.dashboardParameters && Object.keys(this.dashboardParameters).length) {
                if (_.isString(param.value) && param.value.includes('{{')) {
                    const paramKey = param.value.replace('{{', '').replace('}}', '');
                    if (this.dashboardParameters[paramKey]) {
                        clonedDatasetInstance.parameterValues[param.name] = this.dashboardParameters[paramKey].value;
                    }
                }

                // If no value has been set default to the dashboard param value
                if (!clonedDatasetInstance.parameterValues[param.name] && this.dashboardParameters[param.name]) {
                    clonedDatasetInstance.parameterValues[param.name] = this.dashboardParameters[param.name].value;
                }
            } else {
                clonedDatasetInstance.parameterValues[param.name] = param.value;
            }

            this.datasetInstanceSummary.parameterValues[param.name] = param.value;
        });

        // Merge in global dashboard params
        if (this.dashboardParameters && Object.keys(this.dashboardParameters).length) {
            _.forEach(this.dashboardParameters, (param, name) => {
                if (!clonedDatasetInstance.parameterValues[name]) {
                    clonedDatasetInstance.parameterValues[name] = param.value;
                }
            });
        }

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

        return clonedDatasetInstance;
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
