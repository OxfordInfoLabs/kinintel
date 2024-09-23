import {Component, Inject, OnInit} from '@angular/core';
import * as lodash from 'lodash';
const _ = lodash.default;
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';
import {CreateDatasetComponent} from '../../../../dataset/create-dataset/create-dataset.component';
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {DatasetService} from '../../../../../services/dataset.service';

@Component({
    selector: 'ki-dataset-add-parameter',
    templateUrl: './dataset-add-parameter.component.html',
    styleUrls: ['./dataset-add-parameter.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class DatasetAddParameterComponent implements OnInit {

    public parameter: any = {type: 'text', multiple: false, defaultValue: null};

    private updateName = true;

    constructor(public dialogRef: MatDialogRef<DatasetAddParameterComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dialog: MatDialog,
                private datasetService: DatasetService) {
    }

    ngOnInit(): void {
        if (this.data && this.data.parameter) {
            this.parameter = this.data.parameter;
            this.updateName = !this.parameter.name;
        }
    }

    public setName() {
        if (this.updateName) {
            this.parameter.name = _.camelCase(this.parameter.title);
        }
    }

    public selectDatasource() {
        const dialogRef = this.dialog.open(CreateDatasetComponent, {
            width: '1200px',
            height: '800px',
            data: { }
        });
        dialogRef.afterClosed().subscribe(async res => {
            if (res) {
                const data = await this.datasetService.evaluateDataset(res, '0', '1');
                console.log(data);
            }
        });
    }

}
