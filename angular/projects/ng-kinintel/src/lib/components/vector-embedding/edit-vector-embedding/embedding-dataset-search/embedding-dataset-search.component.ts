import {Component, Inject} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';

@Component({
    selector: 'ki-embedding-dataset-search',
    templateUrl: './embedding-dataset-search.component.html',
    styleUrls: ['./embedding-dataset-search.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class EmbeddingDatasetSearchComponent {

    constructor(public dialogRef: MatDialogRef<EmbeddingDatasetSearchComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    public async select(event) {
        const type = event.type;
        const item = event.item;

        const datasetInstanceSummary = {
            datasetInstanceId: type === 'dataset' ? item.id : null,
            datasourceInstanceKey: type === 'datasource' ? item.key : null,
            transformationInstances: [],
            parameterValues: {},
            parameters: [],
            title: item.title
        };

        if (type === 'snapshot') {
            datasetInstanceSummary.datasourceInstanceKey = item;
            datasetInstanceSummary.title = item;
        }

        this.dialogRef.close(datasetInstanceSummary);
    }

}
