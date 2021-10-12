import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';

@Component({
    selector: 'ki-dataset-filter-junction',
    templateUrl: './dataset-filter-junction.component.html',
    styleUrls: ['./dataset-filter-junction.component.sass']
})
export class DatasetFilterJunctionComponent implements OnInit {

    @Input() filterJunction: any;
    @Input() filterFields: any = [];
    @Input() parentJunction: any;
    @Input() junctionIndex: number;
    @Input() joinFilterFields: any;
    @Input() joinFieldsName: string;

    @Output() filterJunctionChange = new EventEmitter<any>();

    constructor() {
    }

    ngOnInit(): void {
    }

    public addFilter(type) {
        if (type === 'single') {
            this.filterJunction.filters.push({
                lhsExpression: '',
                rhsExpression: '',
                filterType: ''
            });
        } else if (type === 'group') {
            this.filterJunction.filterJunctions.push({
                logic: 'AND',
                filters: [{
                    lhsExpression: '',
                    rhsExpression: '',
                    filterType: ''
                }],
                filterJunctions: []
            });
        }
        this.filterJunctionChange.emit(this.filterJunction);
    }

    public filtersRemoved() {
        this.parentJunction.filterJunctions.splice(this.junctionIndex, 1);
    }
}
