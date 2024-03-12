import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';

@Component({
    selector: 'ki-snapshot-api-access',
    templateUrl: './snapshot-api-access.component.html',
    styleUrls: ['./snapshot-api-access.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class SnapshotApiAccessComponent implements OnInit {

    public datasetInstance: any;

    constructor(public dialogRef: MatDialogRef<SnapshotApiAccessComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit() {
        this.datasetInstance = this.data.datasetInstanceSummary;
    }
}
