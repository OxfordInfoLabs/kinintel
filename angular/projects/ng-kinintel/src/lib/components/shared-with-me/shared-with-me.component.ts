import {Component, Input, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Observable, of, Subject} from 'rxjs';
import {debounceTime, switchMap} from 'rxjs/operators';
import {DatasetService} from '../../services/dataset.service';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {Router} from '@angular/router';
import shortHash from 'short-hash';
import * as lodash from 'lodash';
import {FeedService} from '../../services/feed.service';
import {
    FeedApiModalComponent
} from '../shared-with-me/feed-api-modal/feed-api-modal.component';

const _ = lodash.default;

@Component({
    selector: 'ki-shared-with-me',
    templateUrl: './shared-with-me.component.html',
    styleUrls: ['./shared-with-me.component.sass']
})
export class SharedWithMeComponent implements OnInit {

    @Input() headingDescription: string;
    @Input() backendUrl: string;
    @Input() url: string;
    @Input() tableHeading: string;
    @Input() headingLabel: string;
    @Input() hideHeading = false;
    @Input() datasetEditorReadonly = false;
    @Input() nameReplaceString: string;
    @Input() feedUrl: string;
    @Input() apiDocsUrl: string = '';

    public datasets: any = [];
    public searchText = new BehaviorSubject('');
    public limit = 10;
    public offset = 0;
    public page = 1;
    public endOfResults = false;
    public loading = true;

    private reload = new Subject();

    constructor(private datasetService: DatasetService,
                private dialog: MatDialog,
                private router: Router,
                private feedService: FeedService) {
    }

    ngOnInit() {
        merge(this.searchText, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasets()
                )
            ).subscribe((datasets: any) => {
            this.endOfResults = datasets.length < this.limit;
            this.datasets = datasets;
            this.loading = false;
        });

        this.searchText.subscribe(() => {
            this.page = 1;
            this.offset = 0;
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

    public async apiAccess(dataset: any) {
        dataset._loadingAPI = true;
        const hash = shortHash(String(dataset.id));
        const feeds: any = await this.feedService.listFeeds(hash, '1', '0').toPromise();
        let feedId = null;
        if (!feeds.length) {
            const feedData = {
                exporterConfiguration: {includeHeaderRow: true, separator: ','},
                cacheTimeSeconds: 0,
                path: hash,
                datasetInstanceId: dataset.id,
                datasetLabel: dataset,
                exporterKey: 'json',
                advancedQuerying: true,
                adhocFiltering: true
            };

            feedId = await this.feedService.saveFeed(feedData);
        } else {
            feedId = feeds[0].id;
        }

        const feed = await this.feedService.getFeed(feedId);
        dataset._loadingAPI = false;

        if (this.apiDocsUrl) {
            window.location.href = this.apiDocsUrl + "?sharedDatasetId=" + dataset.id;
        } else {
            const dialog = this.dialog.open(FeedApiModalComponent, {
                width: '700px',
                height: '650px',
                data: {
                    feed,
                    feedUrl: this.feedUrl
                }
            });
        }
    }

    public getDatasets() {
        return this.datasetService.getAccountSharedDatasets(this.searchText.getValue(), this.limit, this.offset).toPromise();
    }

    // Create extended dataset from source id.
    public extend(id) {
        this.datasetService.getExtendedDataset(id).then(datasetInstanceSummary => {
            this.viewDataset(datasetInstanceSummary);
        });
    }

    // View dataset
    private viewDataset(datasetInstanceSummary) {
        this.router.navigate([this.url || '/dataset'], {fragment: _.kebabCase(datasetInstanceSummary.title)});
        const dialogRef = this.dialog.open(DataExplorerComponent, {
            width: '100vw',
            height: '100vh',
            maxWidth: '100vw',
            maxHeight: '100vh',
            hasBackdrop: false,
            closeOnNavigation: true,
            data: {
                datasetInstanceSummary,
                backendUrl: this.backendUrl,
                showChart: false,
                admin: false,
                breadcrumb: this.headingLabel || 'Datasets',
                url: this.url,
                datasetEditorReadonly: this.datasetEditorReadonly
            }
        });
        dialogRef.afterClosed().subscribe(res => {
            if (res) {
                if (res.breadcrumb) {
                    return this.router.navigate([res.breadcrumb], {fragment: null});
                }
                this.router.navigate([this.url || '/dataset'], {fragment: null});
                this.reload.next(Date.now());
            }
        });
    }


}
