import {Component, Input, OnInit, Output, EventEmitter} from '@angular/core';
import * as _ from 'lodash';

@Component({
    selector: 'ki-dataset-filter',
    templateUrl: './dataset-filter.component.html',
    styleUrls: ['./dataset-filter.component.sass']
})
export class DatasetFilterComponent implements OnInit {

    @Input() filter: any;
    @Input() filterIndex: any;
    @Input() filterJunction: any;
    @Input() filterFields: any = [];
    @Input() joinFilterFields: any;
    @Input() joinFieldsName: string;

    @Output() filtersRemoved = new EventEmitter();

    public filterTypes = [
        {label: '(==) Equal To', value: 'eq'},
        {label: '(!=) Not Equal To', value: 'neq'},
        {label: 'Is Null', value: 'null'},
        {label: 'Not Null', value: 'notnull'},
        {label: '(>) Greater Than', value: 'gt'},
        {label: '(>=) Greater Than Or Equal To', value: 'gte'},
        {label: '(<) Less Than', value: 'lt'},
        {label: '(<=) Less Than Or Equal To', value: 'lte'},
        {label: 'Like', value: 'like'},
    ];

    constructor() {
    }

    ngOnInit(): void {
    }

    public removeFilter() {
        this.filterJunction.filters.splice(this.filterIndex, 1);
        if (!this.filterJunction.filters.length &&
            !this.filterJunction.filterJunctions.length) {

            this.filtersRemoved.emit(this.filterJunction);
        }
    }

}
