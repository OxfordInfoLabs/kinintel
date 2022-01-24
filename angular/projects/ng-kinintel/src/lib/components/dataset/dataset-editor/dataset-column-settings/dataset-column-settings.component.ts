import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import * as _ from 'lodash';
import {Subject} from 'rxjs';

@Component({
    selector: 'ki-dataset-column-settings',
    templateUrl: './dataset-column-settings.component.html',
    styleUrls: ['./dataset-column-settings.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class DatasetColumnSettingsComponent implements OnInit {

    public columns: any = [];
    public reset = false;
    public resetFields = [];
    public resetTrigger = new Subject();

    constructor(public dialogRef: MatDialogRef<DatasetColumnSettingsComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        this.columns = this.data.columns;
        this.reset = this.data.reset;
        this.resetFields = this.data.resetFields;
    }

    public updateSettings() {
        this.dialogRef.close(_.filter(this.columns, 'selected'));
    }

    public resetColumns() {
        this.columns = this.resetFields;
        setTimeout(() => {
            this.resetTrigger.next(Date.now());
        }, 0);
    }

}
