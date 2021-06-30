import {Component, Input, OnInit} from '@angular/core';

@Component({
    selector: 'ki-dataset-filters',
    templateUrl: './dataset-filters.component.html',
    styleUrls: ['./dataset-filters.component.sass']
})
export class DatasetFiltersComponent implements OnInit {

    @Input() filterJunction: any;
    @Input() filterFields: any = [];
    @Input() rightFilterFields: any;

    constructor() {
    }

    ngOnInit(): void {
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
}
