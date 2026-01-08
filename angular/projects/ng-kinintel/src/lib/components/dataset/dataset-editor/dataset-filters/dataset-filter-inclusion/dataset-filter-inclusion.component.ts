import {Component, Input, OnInit} from '@angular/core';

@Component({
    selector: 'ki-dataset-filter-inclusion',
    templateUrl: './dataset-filter-inclusion.component.html',
    styleUrls: ['./dataset-filter-inclusion.component.sass'],
    standalone: false
})
export class DatasetFilterInclusionComponent implements OnInit {

    @Input() filter: any;
    @Input() parameterValues: any;
    @Input() dependsString = 'Depends';

    constructor() {
    }

    ngOnInit() {
    }

}
