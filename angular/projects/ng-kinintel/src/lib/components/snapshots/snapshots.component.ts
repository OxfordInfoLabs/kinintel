import {Component, Inject, Input, OnDestroy, OnInit} from '@angular/core';
import {BehaviorSubject, interval, merge, Subject, Subscription} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {TagService} from '../../services/tag.service';
import {ProjectService} from '../../services/project.service';
import {DatasetService} from '../../services/dataset.service';
import {KININTEL_CONFIG, KinintelModuleConfig} from '../../kinintel-config';
import * as lodash from 'lodash';
import {Router} from '@angular/router';
import {
    SnapshotProfileDialogComponent
} from '../data-explorer/snapshot-profile-dialog/snapshot-profile-dialog.component';
import {DataProcessorService} from '../../services/data-processor.service';
const _ = lodash.default;

@Component({
    selector: 'ki-snapshots',
    templateUrl: './snapshots.component.html',
    styleUrls: ['./snapshots.component.sass']
})
export class SnapshotsComponent implements OnInit, OnDestroy {

    @Input() headingLabel: string;
    @Input() shared: boolean;
    @Input() admin: boolean;
    @Input() environment: any;

    public snapshots: any = {
        project: {
            data: [],
            limit: 10,
            offset: 0,
            page: 1,
            endOfResults: false,
            shared: false,
            reload: new Subject(),
            categories: []
        },
        tag: {
            data: [],
            limit: 10,
            offset: 0,
            page: 1,
            endOfResults: false,
            shared: false,
            reload: new Subject(),
            categories: []
        }
    };
    public searchText = new BehaviorSubject('');
    public activeTagSub = new Subject();
    public activeTag: any;
    public _ = _;
    public canHaveSnapshots = false;

    private tagSub: Subscription;
    private reload = new Subject();
    private tagDataSub: Subscription;
    private projectChanges: Subscription;
    private tagChanges: Subscription;

    constructor(private dialog: MatDialog,
                private tagService: TagService,
                private projectService: ProjectService,
                private datasetService: DatasetService,
                private dataProcessorService: DataProcessorService,
                private router: Router,
                @Inject(KININTEL_CONFIG) public config: KinintelModuleConfig) {
    }

    ngOnInit(): void {
        this.canHaveSnapshots = this.projectService.doesActiveProjectHavePrivilege('snapshotaccess');

        if (this.canHaveSnapshots) {
            if (this.tagService) {
                this.activeTagSub = this.tagService.activeTag;
                this.tagSub = this.tagService.activeTag.subscribe(tag => {
                    if (tag) {
                        this.activeTag = tag;
                        this.tagDataSub = merge(this.searchText, this.snapshots.tag.reload, this.projectService.activeProject, this.reload)
                            .pipe(
                                debounceTime(300),
                                // distinctUntilChanged(),
                                switchMap(() =>
                                    this.getSnapshots(this.snapshots.tag)
                                )
                            ).subscribe((snapshots: any) => {
                                this.snapshots.tag.data = snapshots;
                            });

                        this.tagChanges = interval(3000)
                            .pipe(
                                switchMap(() =>
                                    this.dataProcessorService.filterProcessorsByType(
                                        'snapshot', this.searchText.getValue() || '', String(this.snapshots.tag.limit), String(this.snapshots.tag.offset)).pipe(
                                        map(result => {
                                            return result;
                                        }))
                                )
                            ).subscribe(snapshots => {
                                this.snapshots.tag.data = snapshots;
                            });
                    } else {
                        this.activeTag = null;
                        if (this.tagDataSub) {
                            this.tagDataSub.unsubscribe();
                        }
                    }

                });
            }

            merge(this.searchText, this.snapshots.project.reload, this.projectService.activeProject, this.reload)
                .pipe(
                    debounceTime(300),
                    // distinctUntilChanged(),
                    switchMap(() =>
                        this.getSnapshots(this.snapshots.project, 'NONE')
                    )
                ).subscribe((snapshots: any) => {
                this.snapshots.project.data = snapshots;
            });

            this.watchSnapshotChanges();

            this.searchText.subscribe(() => {
                _.forEach(this.snapshots, snapshot => {
                    snapshot.page = 1;
                    snapshot.offset = 0;
                });
            });
        }
    }

    ngOnDestroy() {
        if (this.tagSub) {
            this.tagSub.unsubscribe();
        }
        if (this.tagDataSub) {
            this.tagDataSub.unsubscribe();
        }
        this.stopSnapshotWatch();
    }

    public async triggerSnapshot(snapshotKey, snapshot) {
        await this.dataProcessorService.triggerProcessor(snapshotKey);
        snapshot.reload.next(Date.now());
    }

    public view(datasourceKey) {
        const datasetInstanceSummary = {
            datasetInstanceId: null,
            datasourceInstanceKey: datasourceKey,
            transformationInstances: [],
            parameterValues: {},
            parameters: []
        };
        this.openDialogEditor(datasetInstanceSummary);
    }

    public viewParent(parentDatasetInstanceId) {
        this.datasetService.getDataset(parentDatasetInstanceId).then(datasetInstanceSummary => {
            this.openDialogEditor(datasetInstanceSummary);
        });
    }

    public removeActiveTag() {
        this.tagService.resetActiveTag();
    }

    public async editSnapshot(snapshotKey: string, project: any) {
        const snapshot: any = await this.dataProcessorService.getProcessor(snapshotKey);
        const datasetInstance = await this.datasetService.getDataset(snapshot.relatedObjectPrimaryKey);
        const dialogRef = this.dialog.open(SnapshotProfileDialogComponent, {
            width: '900px',
            height: '900px',
            data: {
                snapshot,
                datasetInstanceId: snapshot.relatedObjectPrimaryKey,
                datasetInstance
            }
        });
        dialogRef.afterClosed().subscribe(res => {
            if (res) {
                project.reload.next(Date.now());
            }
        });
    }

    public delete(snapshotProfileId, snapshot) {
        const message = 'Are you sure you would like to remove this Snapshot?';
        if (window.confirm(message)) {
            this.dataProcessorService.removeProcessor(snapshotProfileId).then(() => {
                snapshot.reload.next(Date.now());
            });
        }
    }

    public increaseOffset(snapshot) {
        snapshot.page = snapshot.page + 1;
        snapshot.offset = (snapshot.limit * snapshot.page) - snapshot.limit;
        snapshot.reload.next(Date.now());
    }

    public decreaseOffset(snapshot) {
        snapshot.page = snapshot.page <= 1 ? 1 : snapshot.page - 1;
        snapshot.offset = (snapshot.limit * snapshot.page) - snapshot.limit;
        snapshot.reload.next(Date.now());
    }

    public pageSizeChange(value, snapshot) {
        snapshot.page = 1;
        snapshot.offset = 0;
        snapshot.limit = value;
        snapshot.reload.next(Date.now());
    }

    private openDialogEditor(datasetInstanceSummary) {
        this.router.navigate(['/snapshots'], {fragment: _.kebabCase(datasetInstanceSummary.title || datasetInstanceSummary.datasourceInstanceKey)});
        const dialogRef = this.dialog.open(DataExplorerComponent, {
            width: '100vw',
            height: '100vh',
            maxWidth: '100vw',
            maxHeight: '100vh',
            hasBackdrop: false,
            data: {
                datasetInstanceSummary,
                showChart: false,
                admin: this.admin,
                breadcrumb: 'Snapshots'
            }
        });
        this.stopSnapshotWatch();
        dialogRef.afterClosed().subscribe(res => {
            if (res && res.breadcrumb) {
                return this.router.navigate([res.breadcrumb], {fragment: null});
            } else {
                this.router.navigate(['/snapshots'], {fragment: null});
            }
            this.reload.next(Date.now());
            this.watchSnapshotChanges();
        });
    }

    private getSnapshots(snapshot, tags?) {
        return this.dataProcessorService.filterProcessorsByType(
            'snapshot',
            this.searchText.getValue() || '',
            snapshot.limit.toString(),
            snapshot.offset.toString(),
            tags
        ).pipe(map((snapshots: any) => {
                snapshot.endOfResults = snapshots.length < snapshot.limit;
                return snapshots;
            })
        );
    }

    private watchSnapshotChanges() {
        this.projectChanges = interval(3000)
            .pipe(
                switchMap(() =>
                    this.dataProcessorService.filterProcessorsByType(
                        'snapshot',
                        this.searchText.getValue() || '', String(this.snapshots.project.limit), String(this.snapshots.project.offset), 'NONE').pipe(
                        map(result => {
                            return result;
                        }))
                )
            ).subscribe(snapshots => {
                this.snapshots.project.data = snapshots;
            });
    }

    private stopSnapshotWatch() {
        if (this.projectChanges) {
            this.projectChanges.unsubscribe();
        }
        if (this.tagChanges) {
            this.tagChanges.unsubscribe();
        }
    }

}
