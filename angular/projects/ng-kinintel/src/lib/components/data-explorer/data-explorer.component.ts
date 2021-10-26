import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialog, MatDialogRef} from '@angular/material/dialog';
import {DatasetNameDialogComponent} from '../dataset/dataset-editor/dataset-name-dialog/dataset-name-dialog.component';
import {DatasetService} from '../../services/dataset.service';
import {Router} from '@angular/router';
import {SnapshotProfileDialogComponent} from '../data-explorer/snapshot-profile-dialog/snapshot-profile-dialog.component';

@Component({
    selector: 'ki-data-explorer',
    templateUrl: './data-explorer.component.html',
    styleUrls: ['./data-explorer.component.sass'],
    host: {class: 'configure-dialog'}
})
export class DataExplorerComponent implements OnInit {

    public showChart = false;
    public chartData;
    public datasetInstanceSummary: any;
    public filters: any;
    public admin: boolean;
    public showSnapshots = false;
    public snapshotProfiles: any = [];
    public editTitle = false;

    private columns: any = [];

    constructor(public dialogRef: MatDialogRef<DataExplorerComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dialog: MatDialog,
                private datasetService: DatasetService,
                private router: Router) {
    }

    ngOnInit(): void {
        this.chartData = !!this.data.showChart;
        this.datasetInstanceSummary = this.data.datasetInstanceSummary;
        this.admin = !!this.data.admin;

        this.chartData = [
            {data: [1000, 1400, 1999, 2500, 5000]},
        ];

    }

    public dataLoaded(data) {
        console.log('Data loaded explorer', data);
        this.columns = data.columns;
    }

    public viewSnapshots() {
        this.showSnapshots = true;
        this.loadSnapshotProfiles();
    }

    public editSnapshot(snapshot) {
        const dialogRef = this.dialog.open(SnapshotProfileDialogComponent, {
            width: '900px',
            height: '750px',
            data: {
                snapshot,
                datasetInstanceId: this.datasetInstanceSummary.id,
                columns: this.columns
            }
        });
        dialogRef.afterClosed().subscribe(res => {
            if (res) {
                this.loadSnapshotProfiles();
            }
        });
    }

    public deleteSnapshot(snapshot) {
        const message = 'Are you sure you would like to remove this snapshot?';
        if (window.confirm(message)) {
            this.datasetService.removeSnapshotProfile(snapshot.id, this.datasetInstanceSummary.id)
                .then(() => {
                    this.loadSnapshotProfiles();
                });
        }
    }

    public saveChanges() {
        if (!this.datasetInstanceSummary.id) {
            const dialogRef = this.dialog.open(DatasetNameDialogComponent, {
                width: '475px',
                height: '150px',
            });
            dialogRef.afterClosed().subscribe(res => {
                if (res) {
                    this.datasetInstanceSummary.title = res;
                    this.saveDataset();
                }
            });
        } else {
            this.saveDataset();
        }
    }

    private saveDataset() {
        this.datasetService.saveDataset(this.datasetInstanceSummary).then(() => {
            this.dialogRef.close();
            this.router.navigate(['/dataset']);
        });
    }

    private loadSnapshotProfiles() {
        this.datasetService.getSnapshotProfilesForDataset(this.datasetInstanceSummary.id)
            .then(snapshots => {
                console.log(snapshots);
                this.snapshotProfiles = snapshots;
            });
    }

}
