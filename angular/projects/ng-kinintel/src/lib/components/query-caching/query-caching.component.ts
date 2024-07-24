import {Component, Input, OnInit} from '@angular/core';
import {BehaviorSubject, interval, merge, Subject} from 'rxjs';
import {debounceTime, map, switchMap} from 'rxjs/operators';
import {DataProcessorService, } from '../../services/data-processor.service';
import * as lodash from 'lodash';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {Router} from '@angular/router';
import {DatasetService} from '../../services/dataset.service';
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {
    EditQueryCacheComponent
} from '../query-caching/edit-query-cache/edit-query-cache.component';
const _ = lodash.default;

@Component({
    selector: 'ki-query-caching',
    templateUrl: './query-caching.component.html',
    styleUrls: ['./query-caching.component.sass']
})
export class QueryCachingComponent implements OnInit {

    @Input() admin: boolean;
    @Input() reload = new Subject();
    @Input() showPager = true;
    @Input() limit = 10;
    @Input() datasetInstanceId: number = null;

    public queries: any = [];
    public searchText = new BehaviorSubject('');

    public offset = 0;
    public page = 1;
    public endOfResults = false;
    public loading = true;
    public _ = _;



    constructor(private dataProcessorService: DataProcessorService,
                private router: Router,
                private datasetService: DatasetService,
                private dialog: MatDialog) {
    }

    ngOnInit() {

        merge(this.searchText, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getQueries()
                )
            ).subscribe((queries: any) => {
            this.endOfResults = queries.length < this.limit;
            this.queries = queries;
            this.loading = false;
        });

        this.searchText.subscribe(() => {
            this.page = 1;
            this.offset = 0;
        });

        this.watchQueryChanges();
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

    public delete(cacheKey) {
        const message = 'Are you sure you would like to remove this Query Cache?';
        if (window.confirm(message)) {
            this.dataProcessorService.removeProcessor(cacheKey).then(() => {
                this.reload.next(Date.now());
            });
        }
    }

    public async editCache(cacheKey: string) {
        const cache: any = await this.dataProcessorService.getProcessor(cacheKey);

        const dataset = await this.datasetService.getDataset(cache.config.sourceQueryId);
        const data: any = await this.datasetService.evaluateDataset(dataset);
        const columns = data.columns;

        const dialogRef = this.dialog.open(EditQueryCacheComponent, {
            width: '900px',
            height: '900px',
            data: {
                cache,
                datasetInstanceId: cache.config.sourceQueryId,
                columns
            }
        });
        dialogRef.afterClosed().subscribe(res => {
            if (res) {
                this.reload.next(Date.now());
            }
        });
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
        dialogRef.afterClosed().subscribe(res => {
            if (res && res.breadcrumb) {
                return this.router.navigate([res.breadcrumb], {fragment: null});
            } else {
                this.router.navigate(['/query-caching'], {fragment: null});
            }
            this.reload.next(Date.now());
        });
    }

    private watchQueryChanges() {
        interval(3000)
            .pipe(
                switchMap(() =>
                    this.getQueries()
                )
            ).subscribe(queries => {
                this.queries = queries;
            });
    }

    private getQueries() {
        if (!this.datasetInstanceId) {
            return this.dataProcessorService.filterProcessorsByType(
                'querycaching',
                this.searchText.getValue() || '',
                this.limit.toString(),
                this.offset.toString()
            ).pipe(map((queries: any) => {
                    return queries;
                })
            );
        } else {
            return this.dataProcessorService.filterProcessorsByRelatedItem(
                'querycaching',
                'DatasetInstance',
                this.datasetInstanceId
            );
        }
    }

}
