import {Component, Input, OnInit, Output, EventEmitter} from '@angular/core';

@Component({
    selector: 'ki-dataset-parameter-values',
    templateUrl: './dataset-parameter-values.component.html',
    styleUrls: ['./dataset-parameter-values.component.sass']
})
export class DatasetParameterValuesComponent implements OnInit {

    @Input() parameterValues: any = [];

    @Output() changed = new EventEmitter();

    constructor() {
    }

    ngOnInit(): void {
    }

    public parameterChange(data) {
        const values = this.parameterValues.map(value => {
            return {
                key: value.name,
                value: value.value
            };
        });
        this.changed.emit(values);
    }

}
