import {Component, EventEmitter, Input, OnDestroy, OnInit, Output} from '@angular/core';
import {BehaviorSubject, merge, Subject, Subscription} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {MatDialog} from '@angular/material/dialog';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {DatasourceService} from '../../services/datasource.service';
import {ProjectService} from '../../services/project.service';
import {ActivatedRoute, Router} from '@angular/router';
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'ki-datasource',
    templateUrl: './datasource.component.html',
    styleUrls: ['./datasource.component.sass']
})
export class DatasourceComponent implements OnInit, OnDestroy {

    @Input() admin: boolean;
    @Input() title: string;
    @Input() description: string;
    @Input() exploreText: string;
    @Input() hideNew: boolean;
    @Input() newDocumentURL = '/document-datasource';
    @Input() newTabularURL = '/import-data';
    @Input() datasourceURL = '/datasource';
    @Input() noProject = false;
    @Input() filterResults: any;


    public datasources: any = [];
    public searchText = new BehaviorSubject('');
    public limit = 10;
    public offset = 0;
    public page = 1;
    public endOfResults = false;
    public reload = new Subject();
    public isProjectAdmin = false;

    private projectSub: Subscription;

    constructor(private dialog: MatDialog,
                private datasourceService: DatasourceService,
                private projectService: ProjectService,
                private router: Router,
                private route: ActivatedRoute) {
    }

    ngOnInit(): void {
        this.searchText.subscribe(() => {
            this.page = 1;
            this.offset = 0;
        });

        const pagingValues = this.projectService.getDataItemPagingValues();
        this.limit = pagingValues.limit || 10;
        this.offset = pagingValues.offset || 0;
        this.page = pagingValues.page || 1;

        this.isProjectAdmin = this.projectService.isActiveProjectAdmin();

        this.projectSub = this.projectService.activeProject.subscribe(() => {
            this.isProjectAdmin = this.projectService.isActiveProjectAdmin();
        });

        this.route.params.subscribe(async param => {
            if (param.key) {
                const datasource = await this.datasourceService.getDatasource(param.key);
                this.explore(datasource);
            }
        });

        merge(this.searchText, this.reload)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasources()
                )
            ).subscribe((sources: any) => {
                if (this.filterResults) {
                    this.datasources = _.filter(sources, this.filterResults);
                } else {
                    this.datasources = sources;
                }
        });

    }

    ngOnDestroy() {
        this.projectSub.unsubscribe();
    }

    public createDatasource() {

    }

    public explore(datasource) {
        this.router.navigate([this.datasourceURL], {fragment: datasource.key});
        const datasetInstanceSummary = {
            datasetInstanceId: null,
            datasourceInstanceKey: datasource.key,
            transformationInstances: [],
            parameterValues: {},
            parameters: [],
            originDataItemTitle: datasource.title
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
                admin: this.admin,
                breadcrumb: this.title || 'Datasources'
            }
        });
        dialogRef.afterClosed().subscribe(res => {
            if (res && res.breadcrumb) {
                return this.router.navigate([res.breadcrumb], {fragment: null});
            }
            this.router.navigate([this.datasourceURL], {fragment: null});
        });
    }

    public async delete(key) {
        const message = 'Are you sure you would like to delete this item?';
        if (window.confirm(message)) {
            await this.datasourceService.deleteDatasource(key);
            this.reload.next(Date.now());
        }
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

    private getDatasources() {
        this.projectService.setDataItemPagingValue(this.limit, this.offset, this.page);

        return this.datasourceService.getDatasources(
            this.searchText.getValue() || '',
            this.limit.toString(),
            this.offset.toString(),
            this.noProject
        ).pipe(map((sources: any) => {
                this.endOfResults = sources.length < this.limit;
                return sources;
            })
        );
    }

}
