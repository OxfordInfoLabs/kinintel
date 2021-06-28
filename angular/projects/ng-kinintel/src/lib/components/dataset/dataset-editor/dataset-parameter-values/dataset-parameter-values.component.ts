import {Component, Input, OnInit, Output, EventEmitter} from '@angular/core';
import * as _ from 'lodash';
import {MatDialog} from '@angular/material/dialog';
import {DatasetAddParameterComponent} from './dataset-add-parameter/dataset-add-parameter.component';

@Component({
    selector: 'ki-dataset-parameter-values',
    templateUrl: './dataset-parameter-values.component.html',
    styleUrls: ['./dataset-parameter-values.component.sass']
})
export class DatasetParameterValuesComponent implements OnInit {

    @Input() parameterValues: any = [];
    @Input() focusParameters = false;
    @Input() evaluatedDatasource: any;

    @Output() changed = new EventEmitter();
    @Output() evaluatedDatasourceChange = new EventEmitter();

    public _ = _;

    constructor(private dialog: MatDialog) {
    }

    ngOnInit(): void {
    }

    public parameterChange(data?) {
        this.changed.emit(this.parameterValues);
    }

    public addParameter() {
        const dialogRef = this.dialog.open(DatasetAddParameterComponent, {
            width: '600px',
            height: '600px'
        });
        dialogRef.afterClosed().subscribe(parameter => {
            if (parameter) {
                this.parameterValues.push(parameter);
                this.evaluatedDatasource.parameters.push(parameter);
            }
        });
    }

}
