import {Component, Inject, OnInit} from '@angular/core';
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';
import {Papa} from 'ngx-papaparse';
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'ki-import-data',
    templateUrl: './import-data.component.html',
    styleUrls: ['./import-data.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class ImportDataComponent implements OnInit {

    public import: any = {headerRow: false, delimiter: ','};
    public columns: any = [];

    constructor(public dialogRef: MatDialogRef<ImportDataComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private papa: Papa) {
    }

    ngOnInit(): void {
        this.columns = this.data.columns.slice(0, 3);
    }

    public fileUpload(event) {
        const fileList: FileList = event.target.files;
        if (fileList.length > 0) {
            const file: File = fileList[0];

            const reader = new FileReader();
            reader.onload = () => {
                const text: any = reader.result;

                this.import.data = this.papa.parse(text, {
                    delimiter: this.import.delimiter
                }).data;

                const preview = _.clone(this.import.data);
                const rows = preview.slice(0, 3);
                this.import.preview = rows.map(row => {
                    return row.slice(0, 3);
                });
            };
            reader.readAsText(file);
        }
    }

    public importData() {
        this.dialogRef.close(this.import);
    }
}
