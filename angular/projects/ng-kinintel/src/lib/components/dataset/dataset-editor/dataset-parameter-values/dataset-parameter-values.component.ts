import {Component, Input, OnInit, Output, EventEmitter} from '@angular/core';
import * as _ from 'lodash';

@Component({
    selector: 'ki-dataset-parameter-values',
    templateUrl: './dataset-parameter-values.component.html',
    styleUrls: ['./dataset-parameter-values.component.sass']
})
export class DatasetParameterValuesComponent implements OnInit {

    @Input() parameterValues: any = [];
    @Input() focusParameters = false;

    @Output() changed = new EventEmitter();

    public _ = _;

    constructor() {
    }

    ngOnInit(): void {
    }

    public parameterChange(data?) {
        this.changed.emit(this.parameterValues);
    }

}
