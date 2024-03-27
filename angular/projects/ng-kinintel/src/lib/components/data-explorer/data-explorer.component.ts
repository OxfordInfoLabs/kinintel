import {Component, Inject, OnDestroy, OnInit} from '@angular/core';
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialog as MatDialog, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';
import {DatasetNameDialogComponent} from '../dataset/dataset-editor/dataset-name-dialog/dataset-name-dialog.component';
import {DatasetService} from '../../services/dataset.service';
import {Router} from '@angular/router';
import {SnapshotProfileDialogComponent} from '../data-explorer/snapshot-profile-dialog/snapshot-profile-dialog.component';
import {ExportDataComponent} from './export-data/export-data.component';
import {ProjectService} from '../../services/project.service';
import * as lodash from 'lodash';
import {DataProcessorService} from '../../services/data-processor.service';
import {
    SnapshotApiAccessComponent
} from './snapshot-api-access/snapshot-api-access.component';
const _ = lodash.default;

@Component({
    selector: 'ki-data-explorer',
    templateUrl: './data-explorer.component.html',
    styleUrls: ['./data-explorer.component.sass'],
    host: {class: 'configure-dialog'}
})
export class DataExplorerComponent implements OnInit, OnDestroy {

    public _ = _;
    public backendUrl: string;
    public showChart = false;
    public chartData;
    public datasetInstanceSummary: any;
    public filters: any;
    public admin: boolean;
    public showSnapshots = false;
    public snapshotProfiles: any = [];
    public editTitle = false;
    public accountId: any;
    public newTitle: string;
    public newDescription: string;
    public breadcrumb: string;
    public canHaveSnapshots = false;
    public canExportData = false;

    private columns: any = [];
    private datasetTitle: string;
    private timer: number;

    constructor(public dialogRef: MatDialogRef<DataExplorerComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dialog: MatDialog,
                private datasetService: DatasetService,
                private router: Router,
                private projectService: ProjectService,
                private dataProcessorService: DataProcessorService) {
    }

    ngOnInit(): void {

        this.chartData = !!this.data.showChart;
        this.datasetInstanceSummary = this.data.datasetInstanceSummary;
        this.admin = !!this.data.admin;
        this.accountId = this.data.accountId;
        this.breadcrumb = this.data.breadcrumb;
        this.backendUrl = this.data.backendUrl;

        if (!this.datasetInstanceSummary.id) {
            this.datasetTitle = this.datasetInstanceSummary.title;
        }

        this.newTitle = this.data.newTitle;
        this.newDescription = this.data.newDescription;

        this.chartData = [
            {data: [1000, 1400, 1999, 2500, 5000]},
        ];

        this.canHaveSnapshots = this.projectService.doesActiveProjectHavePrivilege('snapshotaccess');
        this.canExportData = this.projectService.doesActiveProjectHavePrivilege('exportdata');
    }

    ngOnDestroy() {
        if (this.timer) {
            clearInterval(this.timer);
        }
    }

    public dataLoaded(data) {
        this.columns = data.columns;
    }

    public async triggerSnapshot(snapshotKey) {
        await this.dataProcessorService.triggerProcessor(snapshotKey);
        this.loadSnapshotProfiles();
        this.timer = setInterval(() => {
            this.loadSnapshotProfiles();
        }, 3000);
    }

    public apiAccess() {
        const dialogRef = this.dialog.open(SnapshotApiAccessComponent, {
            width: '800px',
            height: '530px',
            data: {
                datasetInstanceSummary: this.datasetInstanceSummary,
                backendUrl: this.backendUrl
            }
        });
    }

    public exportData() {
        const dialogRef = this.dialog.open(ExportDataComponent, {
            width: '600px',
            height: '530px',
            data: {
                datasetInstanceSummary: Object.assign({}, this.datasetInstanceSummary)
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
            height: '900px',
            data: {
                snapshot,
                datasetInstanceId: this.datasetInstanceSummary.id,
                columns: this.columns,
                datasetInstance: this.datasetInstanceSummary
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
            this.dataProcessorService.removeProcessor(snapshot.key)
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
            this.dialogRef.close(true);
            // this.router.navigate(['/dataset']);
        });
    }

    private loadSnapshotProfiles() {
        this.dataProcessorService.filterProcessorsByRelatedItem('snapshot', 'DatasetInstance', this.datasetInstanceSummary.id)
            .then(snapshots => {
                this.snapshotProfiles = snapshots;
            });
    }

}
