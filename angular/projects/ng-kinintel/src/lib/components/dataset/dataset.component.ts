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
import {MetadataComponent} from '../metadata/metadata.component';
import {ActivatedRoute, Router} from '@angular/router';
import {CreateDatasetComponent} from './create-dataset/create-dataset.component';

@Component({
    selector: 'ki-dataset',
    templateUrl: './dataset.component.html',
    styleUrls: ['./dataset.component.sass']
})
export class DatasetComponent implements OnInit, OnDestroy {

    @Input() headingLabel: string;
    @Input() headingDescription: string;
    @Input() newTitle: string;
    @Input() newDescription: string;
    @Input() hideCreate: boolean;
    @Input() shared: boolean;
    @Input() admin: boolean;
    @Input() reload: Subject<any>;
    @Input() url: string;
    @Input() accountId: any;

    public datasets: any = [];
    public searchText = new BehaviorSubject('');
    public limit = 10;
    public offset = 0;
    public page = 1;
    public endOfResults = false;
    public categories: any = [];
    public filteredCategories: any = [];
    public Date = Date;
    public activeTagSub = new Subject();
    public projectSub = new Subject();
    public activeTag: any;
    public loading = true;

    private tagSub: Subscription;

    constructor(private dialog: MatDialog,
                private tagService: TagService,
                private projectService: ProjectService,
                private datasetService: DatasetService,
                private router: Router,
                private route: ActivatedRoute,
                public config: KinintelModuleConfig) {
    }

    ngOnInit(): void {
        this.route.params.subscribe(param => {
            if (param.id) {
                this.view(param.id);
            }
        });

        if (!this.reload) {
            this.reload = new Subject();
        }

        if (this.tagService) {
            this.activeTagSub = this.tagService.activeTag;
            this.tagSub = this.tagService.activeTag.subscribe(tag => this.activeTag = tag);
        }

        if (this.projectService) {
            this.projectSub = this.projectService.activeProject;
        }

        merge(this.searchText, this.activeTagSub, this.projectSub, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasets()
                )
            ).subscribe((datasets: any) => {
            this.datasets = datasets;
            this.loading = false;
        });

        this.getCategories();

        this.searchText.subscribe(() => {
            this.page = 1;
            this.offset = 0;
        });
    }

    ngOnDestroy() {
        if (this.tagSub) {
            this.tagSub.unsubscribe();
        }
    }

    public view(datasetId) {
        this.datasetService.getDataset(datasetId).then(datasetInstanceSummary => {
            this.viewDataset(datasetInstanceSummary);
        });
    }

    public extend(id) {
        this.datasetService.getExtendedDataset(id).then(datasetInstanceSummary => {
            this.viewDataset(datasetInstanceSummary);
        });
    }

    public removeActiveTag() {
        this.tagService.resetActiveTag();
    }

    public toggleCategory(event, category) {
        event.stopPropagation();
        category.checked = !category.checked;
        this.updateCategoryFilters();
    }

    public updateCategoryFilters() {
        this.offset = 0;
        this.page = 1;
        this.reload.next(Date.now());
    }

    public delete(datasetId) {
        const message = 'Are you sure you would like to remove this Dataset?';
        if (window.confirm(message)) {
            this.datasetService.removeDataset(datasetId).then(() => {
                this.reload.next(Date.now());
            });
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

    public editMetadata(searchResult) {
        const dialogRef = this.dialog.open(MetadataComponent, {
            width: '700px',
            height: '900px',
            data: {
                metadata: _.clone(searchResult),
                service: this.datasetService
            }
        });
        dialogRef.afterClosed().subscribe(res => {
            if (res) {
                this.reload.next(Date.now());
                this.getCategories();
            }
        });
    }

    public create() {
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
                this.viewDataset(res);
            }
        });
    }

    private viewDataset(datasetInstanceSummary) {
        this.router.navigate([this.url || '/dataset'], {fragment: _.kebabCase(datasetInstanceSummary.title)});
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
                newTitle: this.newTitle ? this.newTitle + ' Name' : null,
                newDescription: this.newDescription || null,
                accountId: this.accountId
            }
        });
        dialogRef.afterClosed().subscribe(res => {
            this.router.navigate([this.url || '/dataset'], {fragment: null});
            this.reload.next(Date.now());
        });
    }

    private getDatasets() {
        const checkedCategories = _.filter(this.categories, 'checked');
        return this.datasetService.getDatasets(
            this.searchText.getValue() || '',
            this.limit.toString(),
            this.offset.toString(),
            this.shared ? null : (!_.isNil(this.accountId) ? this.accountId : ''),
            '',
            _.map(checkedCategories, 'key')
        ).pipe(map((datasets: any) => {
                this.endOfResults = datasets.length < this.limit;
                return datasets;
            })
        );
    }

    private getCategories() {
        this.datasetService.getDatasetCategories(this.shared).then(categories => this.categories = categories);
    }

}
