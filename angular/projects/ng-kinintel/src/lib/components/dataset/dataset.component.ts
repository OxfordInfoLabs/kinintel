import {Component, Input, OnDestroy, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject, Subscription} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {MatDialog} from '@angular/material/dialog';

@Component({
    selector: 'ki-dataset',
    templateUrl: './dataset.component.html',
    styleUrls: ['./dataset.component.sass']
})
export class DatasetComponent implements OnInit, OnDestroy {

    @Input() datasetService: any;
    @Input() tagService: any;
    @Input() projectService: any;
    @Input() environment: any = {};

    public datasets: any = [];
    public searchText = new BehaviorSubject('');
    public limit = new BehaviorSubject(10);
    public offset = new BehaviorSubject(0);
    public activeTagSub = new Subject();
    public projectSub = new Subject();

    public activeTag: any;

    private tagSub: Subscription;

    constructor(private dialog: MatDialog) {
    }

    ngOnInit(): void {
        if (this.tagService) {
            this.activeTagSub = this.tagService.activeTag;
            this.tagSub = this.tagService.activeTag.subscribe(tag => this.activeTag = tag);
        }

        if (this.projectService) {
            this.projectSub = this.projectService.activeProject;
        }

        merge(this.searchText, this.limit, this.offset, this.activeTagSub, this.projectSub)
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
        this.tagSub.unsubscribe();
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
                    showChart: false,
                    datasetService: this.datasetService
                }
            });
            dialogRef.afterClosed().subscribe(res => {

            });
        });
    }

    public removeActiveTag() {
        this.tagService.resetActiveTag();
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
