import {Component, Input, OnInit, Output, EventEmitter} from '@angular/core';
import * as _ from 'lodash';

@Component({
    selector: 'ki-dataset-parameter-values',
    templateUrl: './dataset-parameter-values.component.html',
    styleUrls: ['./dataset-parameter-values.component.sass']
})
export class DatasetParameterValuesComponent implements OnInit {

    @Input() parameterValues: any = [];

    @Output() changed = new EventEmitter();

    public _ = _;

    constructor() {
    }

    ngOnInit(): void {
    }

    public parameterChange(data?) {
        const parameterValues = {};
        this.parameterValues.forEach(value => {
            parameterValues[value.name] = value.value;
        });
        this.changed.emit(parameterValues);
    }

}
