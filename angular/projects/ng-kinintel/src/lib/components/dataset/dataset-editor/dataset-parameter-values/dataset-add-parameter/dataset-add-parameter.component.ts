import {Component, OnInit} from '@angular/core';
import * as _ from 'lodash';

@Component({
    selector: 'ki-dataset-add-parameter',
    templateUrl: './dataset-add-parameter.component.html',
    styleUrls: ['./dataset-add-parameter.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class DatasetAddParameterComponent implements OnInit {

    public parameter: any = {type: 'text', multiple: false, defaultValue: null};

    constructor() {
    }

    ngOnInit(): void {
    }

    public setName() {
        this.parameter.name = _.camelCase(this.parameter.title);
    }

}
