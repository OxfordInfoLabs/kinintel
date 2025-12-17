import {Component, Inject, Input, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import {DatasetService} from '../../../services/dataset.service';
import {MatLegacySnackBar as MatSnackBar} from "@angular/material/legacy-snack-bar";
import { HttpClient } from "@angular/common/http";

@Component({
    selector: 'ki-snapshot-api-access',
    templateUrl: './snapshot-api-access.component.html',
    styleUrls: ['./snapshot-api-access.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class SnapshotApiAccessComponent implements OnInit {

    @Input() apiKeys: any;

    public backendURL: string;

    public datasetInstance: any;
    public showApiAccessDetails = false;

    constructor(public dialogRef: MatDialogRef<SnapshotApiAccessComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private snackBar: MatSnackBar,
                private datasetService: DatasetService,
                private http: HttpClient) {
    }

    async ngOnInit() {
        this.backendURL = this.data.backendUrl;
        this.datasetInstance = this.data.datasetInstanceSummary;
        this.showApiAccessDetails = !!this.datasetInstance.managementKey;
        this.apiKeys = await this.http.get('/account/apikey/first/dataprocessormanage').toPromise();
    }

    public copied() {
        this.snackBar.open('Copied to Clipboard', null, {
            duration: 2000,
            verticalPosition: 'top'
        });
    }

    public examplePayload(): any {
        let payload = '{"title": "New Snapshot"';

        if (this.datasetInstance.parameters?.length) {
            let params = [];
            this.datasetInstance.parameters.forEach(parameter => {
                params.push('"' + parameter.name + '": "' + parameter.type + '"');
            });

            payload += ', "parameterValues": {' + params.join(", ") + '}';
        }

        payload += "}";

        return payload;
    }


    public exampleResults() {

        let results = [];
        for (let i = 0; i < 3; i++) {
            results.push('{"column1": "value1", "column2": "value2"}');
        }

        return '[' + results.join(',') + ']';
    }


    public async saveDataset() {
        await this.datasetService.saveDataset(this.datasetInstance);
        this.showApiAccessDetails = !!this.datasetInstance.managementKey;
    }
}
