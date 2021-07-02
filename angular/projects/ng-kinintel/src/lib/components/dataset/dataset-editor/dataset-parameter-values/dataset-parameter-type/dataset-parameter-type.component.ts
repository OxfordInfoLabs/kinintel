import {Component, Input, OnInit, Output, EventEmitter} from '@angular/core';

@Component({
    selector: 'ki-dataset-parameter-type',
    templateUrl: './dataset-parameter-type.component.html',
    styleUrls: ['./dataset-parameter-type.component.sass']
})
export class DatasetParameterTypeComponent implements OnInit {

    @Input() parameter: any;

    @Output() parameterChange = new EventEmitter();

    constructor() {
    }

    ngOnInit(): void {
        if (this.parameter.type === 'join') {
            this.parameter.value = '';
        }
    }

    public valueChanged() {
        this.parameterChange.emit(this.parameter);
    }

}
