import {Component, Inject, Input, OnInit, Output, EventEmitter} from '@angular/core';
import * as _ from 'lodash';
import {MAT_DIALOG_DATA, MatDialog, MatDialogRef} from '@angular/material/dialog';
import {BehaviorSubject} from 'rxjs';
import {DatasetFilterComponent} from './dataset-filter/dataset-filter.component';
import {DatasetSummariseComponent} from './dataset-summarise/dataset-summarise.component';

@Component({
    selector: 'ki-dataset-editor',
    templateUrl: './dataset-editor.component.html',
    styleUrls: ['./dataset-editor.component.sass']
})
export class DatasetEditorComponent implements OnInit {

    @Input() datasource: any = {};
    @Input() datasetInstance: any;
    @Input() datasetService: any;

    @Output() dataLoaded = new EventEmitter<any>();
    @Output() datasetInstanceChange = new EventEmitter();

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

        if (!this.datasetInstance) {
            this.datasetInstance = {
                datasourceInstanceKey: this.datasource.key,
                transformationInstances: []
            };
        }
        this.evaluateDataset();
    }

    public addFilter(type) {
        if (type === 'single') {
            this.filterJunction.filters.push({
                fieldName: '',
                value: '',
                filterType: ''
            });
        } else if (type === 'group') {
            this.filterJunction.filterJunctions.push({
                logic: 'AND',
                filters: [{
                    fieldName: '',
                    value: '',
                    filterType: ''
                }],
                filterJunctions: []
            });
        }
    }

    public removeFilter(index) {
        this.multiSortConfig.splice(index, 1);
        this.setMultiSortValues();
    }

    public applyFilters() {
        const filterTransformation = _.find(this.datasetInstance.transformationInstances, {type: 'filter'});
        if (filterTransformation) {
            filterTransformation.config = this.filterJunction;
        } else {
            this.datasetInstance.transformationInstances.push({
                type: 'filter',
                config: this.filterJunction
            });
        }

        this.evaluateDataset();
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

        const filterTransformation = _.findIndex(this.datasetInstance.transformationInstances, {type: 'filter'});
        if (filterTransformation > -1) {
            this.datasetInstance.transformationInstances.splice(filterTransformation, 1);
        }
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

    private setMultiSortValues() {
        if (!this.multiSortConfig.length) {
            const sortIndex = _.findIndex(this.datasetInstance.transformationInstances, {type: 'multisort'});
            if (sortIndex > -1) {
                this.datasetInstance.transformationInstances.splice(sortIndex, 1);
            }
        } else {
            const multiSortTransformation = _.find(this.datasetInstance.transformationInstances, {type: 'multisort'});
            if (multiSortTransformation) {
                multiSortTransformation.config.sorts = _.map(this.multiSortConfig, item => {
                    return {fieldName: item.column, direction: item.direction};
                });
            } else {
                this.datasetInstance.transformationInstances.push(
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


        this.evaluateDataset();
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
        this.datasetInstanceChange.emit(this.datasetInstance);
    }

    private evaluateDataset() {
        this.datasetService.evaluateDataset(this.datasetInstance, [{
            type: 'paging',
            config: {
                limit: this.limit,
                offset: this.offset
            }
        }]).then(dataset => {
            this.dataset = dataset;
            this.loadData();
        });
    }

}
