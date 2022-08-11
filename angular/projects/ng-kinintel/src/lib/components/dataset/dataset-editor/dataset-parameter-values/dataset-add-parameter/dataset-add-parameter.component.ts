import {Component, Inject, OnInit} from '@angular/core';
import * as lodash from 'lodash';
const _ = lodash.default;
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';

@Component({
    selector: 'ki-dataset-add-parameter',
    templateUrl: './dataset-add-parameter.component.html',
    styleUrls: ['./dataset-add-parameter.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class DatasetAddParameterComponent implements OnInit {

    public parameter: any = {type: 'text', multiple: false, defaultValue: null};

    constructor(public dialogRef: MatDialogRef<DatasetAddParameterComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        if (this.data && this.data.parameter) {
            this.parameter = this.data.parameter;
        }
    }

    public setName() {
        this.parameter.name = _.camelCase(this.parameter.title);
    }

}
