import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import * as lodash from 'lodash';

const _ = lodash.default;
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
    public resetColumnNames: boolean = false;
    public namingConvention: string = "CAMEL";

    constructor(public dialogRef: MatDialogRef<DatasetColumnSettingsComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        this.columns = this.data.columns;
        this.reset = this.data.reset;
        this.resetFields = this.data.resetFields;
        this.resetColumnNames = this.data.resetColumnNames;
        this.namingConvention = this.data.namingConvention;
    }

    public updateSettings() {
        this.dialogRef.close({columns: _.filter(this.columns, 'selected'), resetColumnNames: this.resetColumnNames, namingConvention: this.namingConvention});
    }

    public resetColumns() {
        this.columns = this.resetFields;
        setTimeout(() => {
            this.resetTrigger.next(Date.now());
        }, 0);
    }

}
