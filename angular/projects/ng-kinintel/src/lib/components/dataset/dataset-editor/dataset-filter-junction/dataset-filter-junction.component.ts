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

    @Output() filterJunctionChange = new EventEmitter<any>();

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
        this.filterJunctionChange.emit(this.filterJunction);
    }

    public filtersRemoved(change) {
        this.parentJunction.filterJunctions.splice(this.junctionIndex, 1);
    }
}
