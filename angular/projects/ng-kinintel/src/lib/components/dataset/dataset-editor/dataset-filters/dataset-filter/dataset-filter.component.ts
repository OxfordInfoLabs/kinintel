import {Component, Input, OnInit, Output, EventEmitter} from '@angular/core';
import * as lodash from 'lodash';

const _ = lodash.default;
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {Subject} from 'rxjs';
import {MatChipEditedEvent, MatChipInputEvent} from "@angular/material/chips";
import {COMMA, ENTER} from '@angular/cdk/keycodes';

@Component({
    selector: 'ki-dataset-filter',
    templateUrl: './dataset-filter.component.html',
    styleUrls: ['./dataset-filter.component.sass']
})
export class DatasetFilterComponent implements OnInit {

    public static readonly filterTypes = [
        {label: '(==) Equal To', value: 'eq', string: '=='},
        {label: '(!=) Not Equal To', value: 'neq', string: '!='},
        {label: 'Contains', value: 'contains', string: 'contains'},
        {label: 'Starts with', value: 'startswith', string: 'starts with'},
        {label: 'Ends with', value: 'endswith', string: 'ends with'},
        {label: 'Similar to', value: 'similarto', string: 'similar to'},
        {label: '(>) Greater Than', value: 'gt', string: '>'},
        {label: '(>=) Greater Than Or Equal To', value: 'gte', string: '>='},
        {label: '(<) Less Than', value: 'lt', string: '<'},
        {label: '(<=) Less Than Or Equal To', value: 'lte', string: '<='},
        {label: 'Between', value: 'between', string: 'between'},
        {label: 'Is Null', value: 'null', string: 'is null'},
        {label: 'Not Null', value: 'notnull', string: 'not null'},
        {label: 'Like', value: 'like', string: 'like'},
        {label: 'Not Like', value: 'notlike', string: 'not like'},
        {label: 'Bitwise AND', value: 'bitwiseand', string: 'bitwise and'},
        {label: 'Bitwise OR', value: 'bitwiseor', string: 'bitwise or'},
        {label: 'Is One Of', value: 'in', string: 'in'},
        {label: 'Is Not One Of', value: 'notin', string: 'not in'}
    ];

    @Input() filter: any;
    @Input() filterIndex: any;
    @Input() filterJunction: any;
    @Input() filterFields: any = [];
    @Input() joinFilterFields: any;
    @Input() joinFieldsName: string;
    @Input() openSide: Subject<boolean>;
    @Input() parameterValues: any;

    @Output() filtersRemoved = new EventEmitter();

    public customValue = false;
    public customLhs = false;
    public _ = _;
    public COMMA = COMMA;
    public ENTER = ENTER;

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

        this.customValue = this.filter.rhsExpression.length && String(this.filter.rhsExpression[0]).length &&
            !_.find(this.joinFilterFields, field => {
                return `[[${field.name}]]` === this.filter.rhsExpression[0];
            });

        // Handle legacy string scenarios.
        if (_.isString(this.filter.rhsExpression)) {
            this.filter.rhsExpression = [this.filter.rhsExpression];
        }


        if (String(this.filter.rhsExpression[0]).includes('AGO') && !String(this.filter.rhsExpression[0]).includes('AGO}}')) {
            this.filter._expType = 'period';
            const periodValues = this.filter.rhsExpression[0].split('_');
            this.filter._periodValue = periodValues[0];
            this.filter._period = periodValues[1];
        }

        // If parameter value, make temporary members for binding
        if (this.filter.inclusionCriteria && this.filter.inclusionCriteria !== 'Always') {
            const splitData = this.filter.inclusionData.split('=');
            this.filter._inclusionParam = splitData[0];
            this.filter._inclusionParamValue = splitData.length > 1 ? splitData[1] : '';
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


    updateFilterType(filter: any) {
        if (filter.filterType !== 'similarto' && filter.filterType !== 'between'
            && filter.filterType !== 'like' && filter.filterType !== 'notlike' && filter.filterType !== 'in' && filter.filterType !== 'notin') {
            if (filter.rhsExpression && filter.rhsExpression.length > 1) {
                filter.rhsExpression = filter.rhsExpression.slice(0, 1);
            }
        }
        if (filter.filterType === 'similarto') {
            if (!filter.rhsExpression[1]) {
                filter.rhsExpression[1] = 1;
            }
        }
        if (filter.filterType === 'like' || filter.filterType === 'notlike') {
            if (!filter.rhsExpression[1]) {
                filter.rhsExpression[1] = 'likewildcard';
            }
        }
    }

    public updateExpressionType(filter, type, value?, fieldIndex = 0) {
        filter[fieldIndex === 0 ? '_expType' : '_otherExpType'] = type;
        if (type === 'period') {
            filter.rhsExpression[fieldIndex] = `${filter._periodValue || 1}_${filter._period || 'DAYS'}_AGO`;
        } else {
            if (filter.filterType == 'in' || filter.filterType == 'notin'){
                filter.rhsExpression.push(value);
            } else {
                filter.rhsExpression[fieldIndex] = value;
            }
        }
    }

    public updatePeriodValue(value, period, filter, fieldIndex = 0) {
        filter.rhsExpression[fieldIndex] = `${value}_${period}_AGO`;
    }


    /**
     * Add an in value
     *
     * @param event
     */
    public addInValue(event: MatChipInputEvent) {
        const value = (event.value || '').trim();

        // Add our fruit
        if (value) {
            this.filter.rhsExpression.push(value);
        }

        // Clear the input value
        event.chipInput!.clear();
    }

    /**
     * Remove an in value
     *
     * @param inValue
     */
    public removeInValue(inValue: string) {

        const index = this.filter.rhsExpression.indexOf(inValue);

        if (index >= 0) {
            this.filter.rhsExpression.splice(index, 1);
        }
    }

    /**
     * Edit an in value
     *
     * @param inValue
     * @param event
     */
    public editInValue(inValue: string, event: MatChipEditedEvent) {
        const value = event.value.trim();

        // Remove fruit if it no longer has a name
        if (!value) {
            this.removeInValue(inValue);
            return;
        }

        // Edit existing fruit
        const index = this.filter.rhsExpression.indexOf(inValue);
        if (index >= 0) {
            this.filter.rhsExpression[index].name = value;
        }

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


    protected readonly Object = Object;
}
