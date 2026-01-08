import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {DatasourceService} from '../../../services/datasource.service';
import {DatasetService} from '../../../services/dataset.service';

@Component({
    selector: 'ki-create-dataset',
    templateUrl: './create-dataset.component.html',
    styleUrls: ['./create-dataset.component.sass'],
    host: { class: 'dialog-wrapper' },
    standalone: false
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

    public async select(action) {
        let selectedSource;

        if (action.datasourceKey) {
            selectedSource = {
                datasetInstanceId: null,
                datasourceInstanceKey: action.datasourceKey,
                transformationInstances: [],
                parameterValues: {},
                parameters: []
            };
        } else if (action.datasetId) {
            selectedSource = await this.datasetService.getExtendedDataset(action.datasetId);
        }

        this.dialogRef.close(selectedSource);
    }

}
