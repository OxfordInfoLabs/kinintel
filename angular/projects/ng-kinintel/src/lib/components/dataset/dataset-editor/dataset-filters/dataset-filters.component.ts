import {Component, Input, OnInit} from '@angular/core';
import {Subject} from 'rxjs';

@Component({
    selector: 'ki-dataset-filters',
    templateUrl: './dataset-filters.component.html',
    styleUrls: ['./dataset-filters.component.sass']
})
export class DatasetFiltersComponent implements OnInit {

    @Input() filterJunction: any;
    @Input() filterFields: any = [];
    @Input() joinFilterFields: any;
    @Input() joinFieldsName: string;
    @Input() openSide: Subject<boolean>;
    @Input() parameterValues: any;

    constructor() {
    }

    ngOnInit(): void {
        // If parameter value, make temporary members for binding
        if (this.filterJunction.inclusionCriteria && this.filterJunction.inclusionCriteria !== 'Always') {
            const splitData = this.filterJunction.inclusionData.split('=');
            this.filterJunction._inclusionParam = splitData[0];
            this.filterJunction._inclusionParamValue = splitData.length > 1 ? splitData[1] : '';
        }
    }

    public addFilter(type) {
        if (type === 'single') {
            this.filterJunction.filters.push({
                lhsExpression: '',
                rhsExpression: [],
                filterType: ''
            });
        } else if (type === 'group') {
            this.filterJunction.filterJunctions.push({
                logic: 'AND',
                filters: [{
                    lhsExpression: '',
                    rhsExpression: [],
                    filterType: ''
                }],
                filterJunctions: []
            });
        }
    }
}
