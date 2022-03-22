import {Component, Input, OnDestroy, OnInit} from '@angular/core';
import {FeedService} from '../../services/feed.service';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {MatDialog} from '@angular/material/dialog';
import {KinintelModuleConfig} from '../../ng-kinintel.module';
import {debounceTime, map, switchMap} from 'rxjs/operators';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {DatasetService} from '../../services/dataset.service';
import {FeedComponent} from './feed/feed.component';

@Component({
    selector: 'ki-feeds',
    templateUrl: './feeds.component.html',
    styleUrls: ['./feeds.component.sass']
})
export class FeedsComponent implements OnInit, OnDestroy {

    @Input() admin: boolean;
    @Input() feedUrl: string;

    public feeds: any = [];
    public searchText = new BehaviorSubject('');
    public limit = new BehaviorSubject(10);
    public offset = new BehaviorSubject(0);
    public page = 1;
    public endOfResults = false;
    public loading = true;

    private reload = new Subject();

    constructor(private dialog: MatDialog,
                private feedService: FeedService,
                private datasetService: DatasetService,
                public config: KinintelModuleConfig) {
    }

    ngOnInit(): void {

        merge(this.searchText, this.limit, this.offset, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getFeeds()
                )
            ).subscribe((feeds: any) => {
            this.endOfResults = feeds.length < this.limit.getValue();
            this.feeds = feeds;
            this.loading = false;
        });
    }

    ngOnDestroy() {

    }

    public increaseOffset() {
        this.page = this.page + 1;
        this.offset.next((this.limit.getValue() * this.page) - this.limit.getValue());
    }

    public decreaseOffset() {
        this.page = this.page <= 1 ? 1 : this.page - 1;
        this.offset.next((this.limit.getValue() * this.page) - this.limit.getValue());
    }

    public pageSizeChange(value) {
        this.limit.next(value);
    }

    public editFeed(feed?) {
        const dialogRef = this.dialog.open(FeedComponent, {
            width: '800px',
            height: '650px',
            data: {feed, feedUrl: this.feedUrl}
        });
        dialogRef.afterClosed().subscribe(res => {
            this.reload.next(Date.now());
        });
    }

    public view(datasetId) {
        this.datasetService.getDataset(datasetId).then(datasetInstanceSummary => {
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
        });
    }

    public delete(feedId) {
        const message = 'Are you sure you would like to remove this Feed?';
        if (window.confirm(message)) {
            this.feedService.deleteFeed(feedId).then(() => {
                this.reload.next(Date.now());
            });
        }
    }

    private getFeeds() {
        return this.feedService.listFeeds(
            this.searchText.getValue() || '',
            this.limit.getValue().toString(),
            this.offset.getValue().toString()
        ).pipe(map((feeds: any) => {
                return feeds;
            })
        );
    }

}
