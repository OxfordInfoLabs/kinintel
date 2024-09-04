import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import {DataProcessorService} from '../../../services/data-processor.service';
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {
    EmbeddingDatasetSearchComponent
} from '../../vector-embedding/edit-vector-embedding/embedding-dataset-search/embedding-dataset-search.component';
import {DatasetService} from '../../../services/dataset.service';
import {DatasourceService} from '../../../services/datasource.service';

@Component({
    selector: 'ki-edit-vector-embedding',
    templateUrl: './edit-vector-embedding.component.html',
    styleUrls: ['./edit-vector-embedding.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class EditVectorEmbeddingComponent implements OnInit {

    public embedding: any;
    public datasetInstanceSummary: any = null;
    public columns: any = [];

    constructor(public dialogRef: MatDialogRef<EditVectorEmbeddingComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dataProcessorService: DataProcessorService,
                private dialog: MatDialog,
                private datasetService: DatasetService,
                private datasourceService: DatasourceService) {
    }

    async ngOnInit() {
        this.embedding = this.data.embedding || {
            type: 'vectorembedding',
            config: {readChunkSize: 1000 }
        };

        if (this.embedding.config.datasetInstanceId || this.embedding.config.datasourceInstanceKey) {
            const datasetInstanceSummary = {
                datasetInstanceId: this.embedding.config.datasetInstanceId || null,
                datasourceInstanceKey: this.embedding.config.datasourceInstanceKey || null,
                transformationInstances: [],
                parameterValues: {},
                parameters: []
            };

            if (this.embedding.config.datasetInstanceId) {
                this.datasetInstanceSummary = await this.datasetService.getDataset(this.embedding.config.datasetInstanceId);
            }

            if (this.embedding.config.datasourceInstanceKey) {
                this.datasetInstanceSummary = await this.datasourceService.getDatasource(this.embedding.config.datasourceInstanceKey);
            }

            const data: any = await this.datasetService.evaluateDataset(datasetInstanceSummary);
            this.columns = data.columns;
        }
    }

    public async selectDataset() {
        const dialogRef = this.dialog.open(EmbeddingDatasetSearchComponent, {
            width: '900px',
            height: '900px',
            data: {

            }
        });

        dialogRef.afterClosed().subscribe(async datasetInstanceSummary => {
            if (datasetInstanceSummary) {
                this.datasetInstanceSummary = datasetInstanceSummary;
                this.embedding.config.datasetInstanceId = datasetInstanceSummary.datasetInstanceId;
                this.embedding.config.datasourceInstanceKey = datasetInstanceSummary.datasourceInstanceKey;

                const data: any = await this.datasetService.evaluateDataset(datasetInstanceSummary);
                this.columns = data.columns;
            }
        });
    }

    public async saveEmbedding() {
        await this.dataProcessorService.saveProcessor(this.embedding, true);
        this.dialogRef.close(true);
    }

}
