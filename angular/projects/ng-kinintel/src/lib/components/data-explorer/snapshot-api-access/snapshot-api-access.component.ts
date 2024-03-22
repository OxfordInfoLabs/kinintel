import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import {DatasetService} from '../../../services/dataset.service';

@Component({
    selector: 'ki-snapshot-api-access',
    templateUrl: './snapshot-api-access.component.html',
    styleUrls: ['./snapshot-api-access.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class SnapshotApiAccessComponent implements OnInit {

    public datasetInstance: any;
    public showApiAccessDetails = false;

    constructor(public dialogRef: MatDialogRef<SnapshotApiAccessComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private datasetService: DatasetService) {
    }

    ngOnInit() {
        this.datasetInstance = this.data.datasetInstanceSummary;
        this.showApiAccessDetails = !!this.datasetInstance.managementKey;
    }

    public async saveDataset() {
        await this.datasetService.saveDataset(this.datasetInstance);
        this.showApiAccessDetails = !!this.datasetInstance.managementKey;
    }
}
