import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import * as _ from 'lodash';

@Component({
    selector: 'ki-dataset-column-settings',
    templateUrl: './dataset-column-settings.component.html',
    styleUrls: ['./dataset-column-settings.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class DatasetColumnSettingsComponent implements OnInit {

    public columns: any = [];
    public reset = false;

    constructor(public dialogRef: MatDialogRef<DatasetColumnSettingsComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        this.columns = this.data.columns;
        this.reset = this.data.reset;
    }

    public updateSettings() {
        this.dialogRef.close(_.filter(this.columns, 'selected'));
    }

    public resetColumns() {
        this.dialogRef.close([]);
    }

}
