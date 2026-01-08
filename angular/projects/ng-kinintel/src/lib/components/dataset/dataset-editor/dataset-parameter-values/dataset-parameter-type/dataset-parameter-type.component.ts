import {Component, Input, OnInit, Output, EventEmitter} from '@angular/core';

@Component({
    selector: 'ki-dataset-parameter-type',
    templateUrl: './dataset-parameter-type.component.html',
    styleUrls: ['./dataset-parameter-type.component.sass'],
    standalone: false
})
export class DatasetParameterTypeComponent implements OnInit {

    @Input() parameter: any;

    @Output() parameterChange = new EventEmitter();
    @Output() triggerApply = new EventEmitter();

    constructor() {
    }

    ngOnInit(): void {
        if (this.parameter.type === 'join' && !this.parameter.value) {
            this.parameter.value = '';
        }
    }

    public valueChanged() {
        this.parameterChange.emit(this.parameter);
    }

    public applyChanges() {
        this.triggerApply.emit(this.parameter);
    }

}
