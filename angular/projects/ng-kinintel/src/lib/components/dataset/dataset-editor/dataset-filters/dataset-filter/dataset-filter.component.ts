import {Component, Input, OnInit, Output, EventEmitter} from '@angular/core';
import * as _ from 'lodash';
import {MatDialog} from '@angular/material/dialog';
import {AvailableColumnsComponent} from '../../../dataset-editor/available-columns/available-columns.component';

@Component({
    selector: 'ki-dataset-filter',
    templateUrl: './dataset-filter.component.html',
    styleUrls: ['./dataset-filter.component.sass']
})
export class DatasetFilterComponent implements OnInit {

    public static readonly filterTypes = [
        {label: '(==) Equal To', value: 'eq', string: '=='},
        {label: '(!=) Not Equal To', value: 'neq', string: '!='},
        {label: 'Is Null', value: 'null', string: 'is null'},
        {label: 'Not Null', value: 'notnull', string: 'not null'},
        {label: '(>) Greater Than', value: 'gt', string: '>'},
        {label: '(>=) Greater Than Or Equal To', value: 'gte', string: '>='},
        {label: '(<) Less Than', value: 'lt', string: '<'},
        {label: '(<=) Less Than Or Equal To', value: 'lte', string: '<='},
        {label: 'Like', value: 'like', string: 'like'},
    ];

    @Input() filter: any;
    @Input() filterIndex: any;
    @Input() filterJunction: any;
    @Input() filterFields: any = [];
    @Input() joinFilterFields: any;
    @Input() joinFieldsName: string;

    @Output() filtersRemoved = new EventEmitter();

    public customValue = false;
    public customLhs = false;

    constructor(private dialog: MatDialog) {
    }

    public static getFilterType(filterType) {
        return _.find(DatasetFilterComponent.filterTypes, {value: filterType}) || null;
    }

    ngOnInit(): void {
        this.customLhs = String(this.filter.lhsExpression).length &&
            !_.find(this.filterFields, field => {
                return `[[${field.name}]]` === this.filter.lhsExpression;
            });

        this.customValue = String(this.filter.rhsExpression).length &&
            !_.find(this.joinFilterFields, field => {
                return `[[${field.name}]]` === this.filter.rhsExpression;
            });
    }

    public viewColumns(columns) {
        this.dialog.open(AvailableColumnsComponent, {
            width: '500px',
            height: '500px',
            data: {columns}
        });
    }

    public removeFilter() {
        this.filterJunction.filters.splice(this.filterIndex, 1);
        if (!this.filterJunction.filters.length &&
            !this.filterJunction.filterJunctions.length) {

            this.filtersRemoved.emit(this.filterJunction);
        }
    }

    public getFilterTypes() {
        return DatasetFilterComponent.filterTypes;
    }

}
