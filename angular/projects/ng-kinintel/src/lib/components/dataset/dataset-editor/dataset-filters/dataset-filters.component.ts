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
