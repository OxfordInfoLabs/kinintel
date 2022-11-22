import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {DatasourceService} from '../../../services/datasource.service';
import {DatasetService} from '../../../services/dataset.service';

@Component({
    selector: 'ki-create-dataset',
    templateUrl: './create-dataset.component.html',
    styleUrls: ['./create-dataset.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class CreateDatasetComponent implements OnInit {

    public admin: boolean;

    constructor(public dialogRef: MatDialogRef<CreateDatasetComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private datasourceService: DatasourceService,
                private datasetService: DatasetService) {
    }

    ngOnInit(): void {
        this.admin = !!this.data.admin;
    }

    public async select(event) {
        const type = event.type;
        const item = event.item;
        let selectedSource;

        if (type === 'datasource') {
            const datasource: any = await this.datasourceService.getDatasource(item.key);
            selectedSource = {
                datasetInstanceId: null,
                datasourceInstanceKey: datasource.key,
                transformationInstances: [],
                parameterValues: {},
                parameters: []
            };

        } else if (type === 'snapshot') {
            selectedSource = {
                datasetInstanceId: null,
                datasourceInstanceKey: item,
                transformationInstances: [],
                parameterValues: {},
                parameters: []
            };
        } else {
            selectedSource = await this.datasetService.getExtendedDataset(item.id);
        }

        this.dialogRef.close(selectedSource);
    }

}
