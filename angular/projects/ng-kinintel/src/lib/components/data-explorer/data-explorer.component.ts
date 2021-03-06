import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialog, MatDialogRef} from '@angular/material/dialog';
import {DatasetNameDialogComponent} from '../dataset/dataset-editor/dataset-name-dialog/dataset-name-dialog.component';
import {DatasetService} from '../../services/dataset.service';
import {Router} from '@angular/router';
import {SnapshotProfileDialogComponent} from '../data-explorer/snapshot-profile-dialog/snapshot-profile-dialog.component';
import {ExportDataComponent} from './export-data/export-data.component';

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
    public accountId: any;

    private columns: any = [];
    private newTitle: string;
    private newDescription: string;
    private datasetTitle: string;

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
        this.accountId = this.data.accountId;

        if (!this.datasetInstanceSummary.id) {
            this.datasetTitle = this.datasetInstanceSummary.title;
        }

        this.newTitle = this.data.newTitle;
        this.newDescription = this.data.newDescription;

        this.chartData = [
            {data: [1000, 1400, 1999, 2500, 5000]},
        ];

    }

    public dataLoaded(data) {
        this.columns = data.columns;
    }

    public exportData() {
        const dialogRef = this.dialog.open(ExportDataComponent, {
            width: '600px',
            height: '530px',
            data: {
                datasetInstanceSummary: Object.assign({}, this.datasetInstanceSummary)
            }
        });
        dialogRef.afterClosed().subscribe(exportDataset => {
            if (exportDataset) {
                this.loadSnapshotProfiles();
            }
        });
    }

    public viewSnapshots() {
        this.showSnapshots = !this.showSnapshots;
        if (this.showSnapshots) {
            this.loadSnapshotProfiles();
        }
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
        if (!this.datasetInstanceSummary.id && (this.datasetInstanceSummary.title === this.datasetTitle)) {
            const dialogRef = this.dialog.open(DatasetNameDialogComponent, {
                width: '475px',
                height: '150px',
                data: {
                    title: this.newTitle,
                    description: this.newDescription
                }
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
        this.datasetService.saveDataset(this.datasetInstanceSummary, this.accountId).then(() => {
            this.dialogRef.close();
            this.router.navigate(['/dataset']);
        });
    }

    private loadSnapshotProfiles() {
        this.datasetService.getSnapshotProfilesForDataset(this.datasetInstanceSummary.id)
            .then(snapshots => {
                this.snapshotProfiles = snapshots;
            });
    }

}
