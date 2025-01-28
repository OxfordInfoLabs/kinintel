import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import {Papa} from 'ngx-papaparse';
import {DatasourceService} from '../../../../services/datasource.service';
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
    public importType = 1;
    public importingData = false;
    public datasourceUpdate: any;
    public rows: any = [];
    public datasourceInstanceKey: string;
    public reloadURL: string;
    public importErrors: any[] = [];
    public _ = _;
    public Object = Object;

    constructor(public dialogRef: MatDialogRef<ImportDataComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private papa: Papa,
                private datasourceService: DatasourceService) {
    }

    ngOnInit(): void {
        this.columns = this.data.columns;
        this.datasourceUpdate = this.data.datasourceUpdate || null;
        this.rows = this.data.rows || [];
        this.datasourceInstanceKey = this.data.datasourceInstanceKey || null;
        this.reloadURL = this.data.reloadURL;
        this.importType = !this.rows.length ? 1 : 3;

        if (!this.columns.length) {
            this.import.headerRow = true;
            this.import.replaceColumns = true;
        }

        if (this.columns.length) {
            this.import.columns = _.filter(this.columns, col => {
                return col.type !== 'id';
            });
        }
    }

    public fileUpload(event) {
        const fileList: FileList = event.target.files;
        if (fileList.length > 0) {
            const file: File = fileList[0];

            const reader = new FileReader();
            reader.onload = () => {
                const text: any = reader.result;
                this.import.fileName = file.name;
                this.import.data = this.papa.parse(text, {
                    delimiter: this.import.delimiter,
                    quoteChar: '"',
                    escapeChar: '\\'
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

    public async importData() {
        this.importingData = true;
        this.importErrors = [];
        if (!this.datasourceUpdate.title) {
            this.datasourceUpdate.title = this.import.fileName;
        }

        if (!this.columns.length || this.import.replaceColumns) {
            this.datasourceUpdate.fields = [
                {title: 'ID', name: 'id', type: 'id'}
            ];
        }


        if (this.import.headerRow) {
            if (!this.columns.length || this.import.replaceColumns) {
                this.columns = [];
                this.import.data[0].forEach((header: string, index: number) => {
                    this.columns.push({
                        title: header,
                        name: _.snakeCase(header),
                        type: 'string'
                    });
                });

                this.import.columns = this.columns;

                this.columns.forEach(column => {
                    this.datasourceUpdate.fields.push(column);
                });
            }

            this.import.data.shift();
        }


        const csvData = [];
        _.filter(this.import.data, item => {
            return _.some(item);
        }).forEach(row => {
            const rowData = {};
            this.import.columns.forEach((column: any, index: number) => {
                rowData[column.name] = row[index];
            });
            csvData.push(rowData);
        });

        let result = null;

        if (this.importType === 1) {
            if (this.rows.length && this.datasourceInstanceKey) {
               await this.datasourceService.deleteFromDatasource(this.datasourceInstanceKey, []);
            }

            this.datasourceUpdate.adds = csvData;

            if (!this.datasourceInstanceKey) {
                result = this.datasourceInstanceKey = await this.datasourceService.createCustomDatasource(this.datasourceUpdate);
            } else {
                result = await this.datasourceService.updateCustomDatasource(this.datasourceInstanceKey, this.datasourceUpdate);
            }
        } else if (this.importType === 2) {
            this.datasourceUpdate.replaces = csvData;
            result = await this.datasourceService.updateCustomDatasource(this.datasourceInstanceKey, this.datasourceUpdate);
        } else if (this.importType === 3) {
            this.datasourceUpdate.adds = csvData;
            result = await this.datasourceService.updateCustomDatasource(this.datasourceInstanceKey, this.datasourceUpdate);
        }

       if (result && result.rejected > 0){
           this.importErrors = <any>Object.values(result.validationErrors)[0];
           console.log(this.importErrors);
       } else
           window.location.href = this.reloadURL + '/' + this.datasourceInstanceKey;
    }


}
