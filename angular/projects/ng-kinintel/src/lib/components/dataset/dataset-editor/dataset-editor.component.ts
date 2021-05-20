import {Component, Inject, Input, OnInit} from '@angular/core';
import * as _ from 'lodash';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';

@Component({
    selector: 'ki-dataset-editor',
    templateUrl: './dataset-editor.component.html',
    styleUrls: ['./dataset-editor.component.sass']
})
export class DatasetEditorComponent implements OnInit {

    @Input() datasource: any = {};
    @Input() dataset: any;

    public tableData = [];
    public showFilters = false;
    public displayedColumns = [];
    public _ = _;
    public filterFields = [];
    public filters = [
        {
            type: 'single',
            column: '',
        },
        {
            type: 'group',
            group: [
                {
                    column: '',
                },
                {
                    column: ''
                }
            ]
        }
    ];
    public transformations = [];

    constructor(public dialogRef: MatDialogRef<DatasetEditorComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        if (!this.datasource) {
            this.datasource = this.data.datasource;
        }

        this.loadData();
    }

    public applyFilter(field, condition, value, andOr) {
        const existingFilters = _.filter(this.transformations, {type: 'filter'});
        const andOrValue = existingFilters.length ? andOr.value : '';
        this.transformations.push({
            type: 'filter',
            column: field.value,
            condition: condition.value,
            value: value.value,
            andOr: andOrValue,
            string: andOrValue + ' ' + field.value + ' ' + condition.value + ' ' + value.value
        });

        field.value = '';
        condition.value = '';
        value.value = '';
    }

    public removeFilter(index) {
        this.transformations.splice(index, 1);
    }

    public sort(event) {
        const column = event.active;
        const direction = _.upperCase(event.direction);
        const existingIndex = _.findIndex(this.transformations, {type: 'sort', column});
        if (existingIndex > -1) {
            if (!direction) {
                this.transformations.splice(existingIndex, 1);
            } else {
                this.transformations[existingIndex].direction = direction;
                this.transformations[existingIndex].string = 'Sort ' + _.startCase(column) + ' ' + direction;
            }
        } else {
            this.transformations.push({
                type: 'sort',
                column,
                direction,
                string: 'Sort ' + _.startCase(column) + ' ' + direction
            });
        }
    }

    private loadData() {
        this.tableData = this.dataset.allData;
        this.displayedColumns = _.map(this.dataset.columns, 'name');
        this.filterFields = _.map(this.dataset.columns, column => {
            return {
                label: column.title,
                value: column.name
            };
        });
    }

}
