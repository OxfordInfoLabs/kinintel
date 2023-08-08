import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {DatasetService} from '../../../services/dataset.service';
import * as fileSaver from 'file-saver';
import * as lodash from 'lodash';
import {MatSnackBar} from '@angular/material/snack-bar';

const _ = lodash.default;

@Component({
    selector: 'ki-export-data',
    templateUrl: './export-data.component.html',
    styleUrls: ['./export-data.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class ExportDataComponent implements OnInit {

    public exportDataset: any = {};

    constructor(public dialogRef: MatDialogRef<ExportDataComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private datasetService: DatasetService,
                private snackBar: MatSnackBar) {
    }

    ngOnInit(): void {
        this.exportDataset.dataSetInstanceSummary = this.data.datasetInstanceSummary;
        this.exportDataset.transformationInstances = [];
        this.exportDataset.parameterValues = [];
        this.exportDataset.limit = '100';
        this.exportDataset.offset = '0';
        this.exportDataset.exporterKey = 'sv';
        this.exportDataset.exporterConfiguration = {includeHeaderRow: true, separator: ','};
    }

    public exportData() {
        this.datasetService.exportDataset(this.exportDataset).then(res => {
            let fileType = 'txt';
            if (this.exportDataset.exporterKey === 'json') {
                fileType = 'json';
            } else if (this.exportDataset.exporterKey === 'sv' &&
                this.exportDataset.exporterConfiguration.separator === ',') {
                fileType = 'csv';
            }

            const filename = _.kebabCase(this.exportDataset.dataSetInstanceSummary.title) + '-' + Date.now();
            fileSaver.saveAs(res, filename + '.' + fileType);
            this.dialogRef.close();
            this.snackBar.open('Data Exported Successfully.', 'Close', {
                duration: 3000,
                verticalPosition: 'top'
            });
        });
    }

}
