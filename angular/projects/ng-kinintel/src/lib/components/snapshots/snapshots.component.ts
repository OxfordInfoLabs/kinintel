import {Component, Input, OnDestroy, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject, Subscription} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {MatDialog} from '@angular/material/dialog';
import {TagService} from '../../services/tag.service';
import {ProjectService} from '../../services/project.service';
import {DatasetService} from '../../services/dataset.service';
import {KinintelModuleConfig} from '../../ng-kinintel.module';
import * as lodash from 'lodash';
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

    private tagSub: Subscription;
    private reload = new Subject();
    private tagDataSub: Subscription;

    constructor(private dialog: MatDialog,
                private tagService: TagService,
                private projectService: ProjectService,
                private datasetService: DatasetService,
                public config: KinintelModuleConfig) {
    }

    ngOnInit(): void {
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
                        ).subscribe((dashboards: any) => {
                            this.snapshots.tag.data = dashboards;
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

        this.searchText.subscribe(() => {
            _.forEach(this.snapshots, snapshot => {
                snapshot.page = 1;
                snapshot.offset = 0;
            });
        });
    }

    ngOnDestroy() {
        if (this.tagSub) {
            this.tagSub.unsubscribe();
        }
        if (this.tagDataSub) {
            this.tagDataSub.unsubscribe();
        }
    }

    public view(datasourceKey) {
        const datasetInstanceSummary = {
            datasetInstanceId: null,
            datasourceInstanceKey: datasourceKey,
            transformationInstances: [],
            parameterValues: {},
            parameters: []
        };
        const dialogRef = this.dialog.open(DataExplorerComponent, {
            width: '100vw',
            height: '100vh',
            maxWidth: '100vw',
            maxHeight: '100vh',
            hasBackdrop: false,
            data: {
                datasetInstanceSummary,
                showChart: false,
                admin: this.admin
            }
        });
        dialogRef.afterClosed().subscribe(res => {
            this.reload.next(Date.now());
        });
    }

    public viewParent(parentDatasetInstanceId) {
        this.datasetService.getDataset(parentDatasetInstanceId).then(datasetInstanceSummary => {
            const dialogRef = this.dialog.open(DataExplorerComponent, {
                width: '100vw',
                height: '100vh',
                maxWidth: '100vw',
                maxHeight: '100vh',
                hasBackdrop: false,
                data: {
                    datasetInstanceSummary,
                    showChart: false,
                    admin: this.admin
                }
            });
            dialogRef.afterClosed().subscribe(res => {
                this.reload.next(Date.now());
            });
        });
    }

    public removeActiveTag() {
        this.tagService.resetActiveTag();
    }

    public delete(datasetId, snapshot) {
        const message = 'Are you sure you would like to remove this Snapshot?';
        if (window.confirm(message)) {
            this.datasetService.removeDataset(datasetId).then(() => {
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

    private getSnapshots(snapshot, tags?) {
        return this.datasetService.listSnapshotProfiles(
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

}
