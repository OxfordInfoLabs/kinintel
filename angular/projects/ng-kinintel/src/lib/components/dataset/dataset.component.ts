import {Component, Input, OnDestroy, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject, Subscription} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {MatDialog} from '@angular/material/dialog';
import {TagService} from '../../services/tag.service';
import {ProjectService} from '../../services/project.service';
import {DatasetService} from '../../services/dataset.service';
import {KinintelModuleConfig} from '../../ng-kinintel.module';

@Component({
    selector: 'ki-dataset',
    templateUrl: './dataset.component.html',
    styleUrls: ['./dataset.component.sass']
})
export class DatasetComponent implements OnInit, OnDestroy {

    public datasets: any = [];
    public searchText = new BehaviorSubject('');
    public limit = new BehaviorSubject(10);
    public offset = new BehaviorSubject(0);
    public activeTagSub = new Subject();
    public projectSub = new Subject();

    public activeTag: any;

    private tagSub: Subscription;
    private reload = new Subject();

    constructor(private dialog: MatDialog,
                private tagService: TagService,
                private projectService: ProjectService,
                private datasetService: DatasetService,
                public config: KinintelModuleConfig) {
    }

    ngOnInit(): void {
        if (this.tagService) {
            this.activeTagSub = this.tagService.activeTag;
            this.tagSub = this.tagService.activeTag.subscribe(tag => this.activeTag = tag);
        }

        if (this.projectService) {
            this.projectSub = this.projectService.activeProject;
        }

        merge(this.searchText, this.limit, this.offset, this.activeTagSub, this.projectSub, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasets()
                )
            ).subscribe((datasets: any) => {
            this.datasets = datasets;
        });
    }

    ngOnDestroy() {
        if (this.tagSub) {
            this.tagSub.unsubscribe();
        }
    }

    public view(datasetId) {
        this.datasetService.getDataset(datasetId).then(dataset => {
            const dialogRef = this.dialog.open(DataExplorerComponent, {
                width: '100vw',
                height: '100vh',
                maxWidth: '100vw',
                maxHeight: '100vh',
                hasBackdrop: false,
                data: {
                    dataset,
                    showChart: false
                }
            });
            dialogRef.afterClosed().subscribe(res => {

            });
        });
    }

    public removeActiveTag() {
        this.tagService.resetActiveTag();
    }

    public delete(datasetId) {
        const message = 'Are you sure you would like to remove this Dataset?';
        if (window.confirm(message)) {
            this.datasetService.removeDataset(datasetId).then(() => {
                this.reload.next(Date.now());
            });
        }
    }

    private getDatasets() {
        return this.datasetService.getDatasets(
            this.searchText.getValue() || '',
            this.limit.getValue().toString(),
            this.offset.getValue().toString()
        ).pipe(map((datasets: any) => {
                return datasets;
            })
        );
    }

}
