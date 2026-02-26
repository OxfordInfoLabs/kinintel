import {Component, Inject, Input, OnDestroy, OnInit} from '@angular/core';
import {FeedService} from '../../services/feed.service';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {MatDialog} from '@angular/material/dialog';
import {KININTEL_CONFIG, KinintelModuleConfig} from '../../kinintel-config';
import {debounceTime, map, switchMap} from 'rxjs/operators';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {DatasetService} from '../../services/dataset.service';
import {FeedComponent} from './feed/feed.component';
import {Router} from '@angular/router';
import {MatSnackBar} from '@angular/material/snack-bar';
import { HttpClient } from '@angular/common/http';
import {ProjectService} from '../../services/project.service';

@Component({
    selector: 'ki-feeds',
    templateUrl: './feeds.component.html',
    styleUrls: ['./feeds.component.sass'],
    standalone: false
})
export class FeedsComponent implements OnInit, OnDestroy {

    @Input() admin: boolean;
    @Input() feedUrl: string;

    public feeds: any = [];
    public searchText = new BehaviorSubject('');
    public limit = 10;
    public offset = 0;
    public page = 1;
    public endOfResults = false;
    public loading = true;
    public apiKeys: any;
    public canHaveAPIConnections = false;

    private reload = new Subject();

    constructor(private dialog: MatDialog,
                private feedService: FeedService,
                private datasetService: DatasetService,
                private router: Router,
                @Inject(KININTEL_CONFIG) public config: KinintelModuleConfig,
                private snackBar: MatSnackBar,
                private http: HttpClient,
                private projectService: ProjectService) {
    }

    async ngOnInit() {

        this.canHaveAPIConnections = this.projectService.doesActiveProjectHavePrivilege('feedaccess');

        if (this.canHaveAPIConnections) {

            try {
                this.apiKeys = await this.http.get('/account/apikey/first/feedaccess').toPromise();
            } catch (e) {
                // Ignore
            }

            merge(this.searchText, this.reload)
                .pipe(
                    debounceTime(300),
                    // distinctUntilChanged(),
                    switchMap(() =>
                        this.getFeeds()
                    )
                ).subscribe((feeds: any) => {
                this.endOfResults = feeds.length < this.limit;
                this.feeds = feeds;
                this.loading = false;
            });

            this.searchText.subscribe(() => {
                this.page = 1;
                this.offset = 0;
            });
        }
    }

    ngOnDestroy() {

    }

    public async copy(text: string) {
        await navigator.clipboard.writeText(text.trim());
        this.copied();
    }

    public copied() {
        this.snackBar.open('Copied to Clipboard', null, {
            duration: 2000,
            verticalPosition: 'top'
        });
    }

    public increaseOffset() {
        this.page = this.page + 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.reload.next(Date.now());
    }

    public decreaseOffset() {
        this.page = this.page <= 1 ? 1 : this.page - 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.reload.next(Date.now());
    }

    public pageSizeChange(value) {
        this.page = 1;
        this.offset = 0;
        this.limit = value;
        this.reload.next(Date.now());
    }

    public editFeed(feed?) {
        const dialogRef = this.dialog.open(FeedComponent, {
            width: '900px',
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
                    admin: this.admin,
                    breadcrumb: 'Feeds'
                }
            });
            dialogRef.afterClosed().subscribe(res => {
                if (res && res.breadcrumb) {
                    return this.router.navigate([res.breadcrumb], {fragment: null});
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
            this.limit.toString(),
            this.offset.toString()
        ).pipe(map((feeds: any) => {
                return feeds;
            })
        );
    }

}
