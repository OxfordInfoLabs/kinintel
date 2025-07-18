import {Component, ElementRef, Inject, Input, OnDestroy, OnInit, ViewChild} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialog as MatDialog,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import {DatasetNameDialogComponent} from '../dataset/dataset-editor/dataset-name-dialog/dataset-name-dialog.component';
import {DatasetService} from '../../services/dataset.service';
import {Router} from '@angular/router';
import {
    SnapshotProfileDialogComponent
} from '../data-explorer/snapshot-profile-dialog/snapshot-profile-dialog.component';
import {ExportDataComponent} from './export-data/export-data.component';
import {ProjectService} from '../../services/project.service';
import * as lodash from 'lodash';
import {DataProcessorService} from '../../services/data-processor.service';
import {
    SnapshotApiAccessComponent
} from './snapshot-api-access/snapshot-api-access.component';
import {
    EditQueryCacheComponent
} from '../query-caching/edit-query-cache/edit-query-cache.component';
import {Subject} from 'rxjs';
import {CreateDatasetComponent} from '../dataset/create-dataset/create-dataset.component';
import {DatasetEditorComponent} from '../dataset/dataset-editor/dataset-editor.component';
import {ChangeSourceWarningComponent} from './change-source-warning/change-source-warning.component';


const _ = lodash.default;

@Component({
    selector: 'ki-data-explorer',
    templateUrl: './data-explorer.component.html',
    styleUrls: ['./data-explorer.component.sass'],
    host: {class: 'configure-dialog'}
})
export class DataExplorerComponent implements OnInit, OnDestroy {

    @ViewChild('datasetEditorComponent') datasetEditorComponent: DatasetEditorComponent;

    @Input() datasetEditorReadonly = false;
    @Input() datasetEditorSimpleMode = false;
    @Input() datasetEditorNoTools: boolean = false;

    public _ = _;
    public backendUrl: string;
    public showChart = false;
    public chartData;
    public datasetInstanceSummary: any;
    public filters: any;
    public admin: boolean;
    public showSnapshots = false;
    public snapshotProfiles: any = [];
    public showQueryCache = false;
    public editTitle = false;
    public accountId: any;
    public newTitle: string;
    public newDescription: string;
    public breadcrumb: string;
    public canHaveSnapshots = false;
    public canExportData = false;
    public reloadCache = new Subject();

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
        this.datasetEditorReadonly = !!this.data.datasetEditorReadonly;
        this.datasetEditorSimpleMode = !!this.data.datasetEditorSimpleMode;
        this.datasetEditorNoTools = !!this.data.datasetEditorNoTools;

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

    public changeSource() {
        const dialogRef = this.dialog.open(CreateDatasetComponent, {
            width: '1200px',
            height: '800px',
            data: {
                admin: this.admin,
                accountId: this.accountId
            }
        });
        dialogRef.afterClosed().subscribe(res => {
            if (res) {
                const dialogRef2 = this.dialog.open(ChangeSourceWarningComponent, {
                    width: '700px',
                    height: '275px'
                });
                dialogRef2.afterClosed().subscribe(proceed => {
                    if (proceed) {
                        this.datasetInstanceSummary.datasetInstanceId = res.datasetInstanceId;
                        this.datasetInstanceSummary.datasourceInstanceKey = res.datasourceInstanceKey;
                        this.datasetInstanceSummary.source = {
                            title: res.title,
                            datasetInstanceId: res.datasetInstanceId,
                            datasourceInstanceKey: res.datasourceInstanceKey,
                            type: null
                        };
                        const transformation = this.datasetInstanceSummary.transformationInstances[0];
                        if (transformation) {
                            this.datasetEditorComponent.excludeUpstreamTransformations(transformation, true);
                        } else {
                            this.datasetEditorComponent.evaluateDataset(true);
                        }
                    }
                });
            }
        });
    }

    public dataLoaded(data) {
        this.columns = data.columns;
    }

    public async triggerSnapshot(snapshotKey) {
        await this.dataProcessorService.triggerProcessor(snapshotKey);
        this.snapshotProfiles = await this.loadProcessorItems('snapshot');
        this.timer = setInterval(async () => {
            this.snapshotProfiles = await this.loadProcessorItems('snapshot');
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

    public async viewSnapshots() {
        this.showQueryCache = false;
        this.showSnapshots = !this.showSnapshots;
        if (this.showSnapshots) {
            this.snapshotProfiles = await this.loadProcessorItems('snapshot');
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
        dialogRef.afterClosed().subscribe(async res => {
            if (res) {
                this.snapshotProfiles = await this.loadProcessorItems('snapshot');
            }
        });
    }

    public async deleteSnapshot(snapshot) {
        const message = 'Are you sure you would like to remove this snapshot?';
        if (window.confirm(message)) {
            await this.dataProcessorService.removeProcessor(snapshot.key);
            this.snapshotProfiles = await this.loadProcessorItems('snapshot');
        }
    }

    public async viewQueryCaching() {
        this.showSnapshots = false;
        this.showQueryCache = !this.showQueryCache;
    }

    public editQueryCache(cache: any) {
        const dialogRef = this.dialog.open(EditQueryCacheComponent, {
            width: '900px',
            height: '900px',
            data: {
                cache,
                datasetInstanceId: this.datasetInstanceSummary.id,
                columns: this.columns,
                datasetInstance: this.datasetInstanceSummary
            }
        });
        dialogRef.afterClosed().subscribe(async res => {
            if (res) {
                this.reloadCache.next(Date.now());
            }
        });
    }

    public saveChanges() {
        if (!this.datasetInstanceSummary.id && (this.datasetInstanceSummary.title === this.datasetTitle)) {
            const dialogRef = this.dialog.open(DatasetNameDialogComponent, {
                width: '700px',
                height: '800px',
                data: {
                    datasetInstanceSummary: this.datasetInstanceSummary
                }
            });
            dialogRef.afterClosed().subscribe(datasetInstanceSummary => {
                if (datasetInstanceSummary) {
                    this.datasetInstanceSummary = datasetInstanceSummary;
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

    private async loadProcessorItems(type: string) {
        return await this.dataProcessorService.filterProcessorsByRelatedItem(
            type,
            'DatasetInstance',
            this.datasetInstanceSummary.id
        );
    }

}
