import {Component, Input, OnInit, Output, EventEmitter} from '@angular/core';
import * as lodash from 'lodash';
const _ = lodash.default;
import {MatDialog} from '@angular/material/dialog';
import {Subject} from 'rxjs';

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
    @Input() openSide: Subject<boolean>;

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

        if (String(this.filter.rhsExpression).includes('AGO')) {
            this.filter._expType = 'period';
            const periodValues = this.filter.rhsExpression.split('_');
            this.filter._periodValue = periodValues[0];
            this.filter._period = periodValues[1];
        }
    }

    public viewColumns(columns) {
        this.openSide.next(true);
    }

    public updateCustom(custom) {
        if (!custom) {
            this.openSide.next(false);
        }
    }

    public updateFilterType(filter, type, value?) {
        filter._expType = type;
        if (type === 'period') {
            filter.rhsExpression = `${filter._periodValue || 1}_${filter._period || 'DAYS'}_AGO`;
        } else {
            filter.rhsExpression = value;
        }
    }

    public updatePeriodValue(value, period, filter) {
        filter.rhsExpression = `${value}_${period}_AGO`;
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
