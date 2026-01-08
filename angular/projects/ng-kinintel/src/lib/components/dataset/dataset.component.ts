import {Component, Inject, Input, OnDestroy, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject, Subscription} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {MatDialog} from '@angular/material/dialog';
import {TagService} from '../../services/tag.service';
import {ProjectService} from '../../services/project.service';
import {DatasetService} from '../../services/dataset.service';
import {KININTEL_CONFIG, KinintelModuleConfig} from '../../kinintel-config';
import * as lodash from 'lodash';
const _ = lodash.default;
import {MetadataComponent} from '../metadata/metadata.component';
import {ActivatedRoute, Router} from '@angular/router';
import {CreateDatasetComponent} from './create-dataset/create-dataset.component';
import {Location} from '@angular/common';

@Component({
    selector: 'ki-dataset',
    templateUrl: './dataset.component.html',
    styleUrls: ['./dataset.component.sass'],
    standalone: false
})
export class DatasetComponent implements OnInit, OnDestroy {

    @Input() headingLabel: string;
    @Input() headingDescription: string;
    @Input() backendUrl: string;
    @Input() newTitle: string;
    @Input() newDescription: string;
    @Input() hideCreate: boolean;
    @Input() shared: boolean;
    @Input() admin: boolean;
    @Input() reload: Subject<any>;
    @Input() url: string;
    @Input() accountId: any;
    @Input() contactUs: string;
    @Input() tableHeading: string;
    @Input() listStyle = 'LIST';

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
    public gridItems: any = {};
    public projectSettings: any = {};

    private tagSub: Subscription;
    private activeProject: any;
    private activeProjectSub: Subscription;

    constructor(private dialog: MatDialog,
                private tagService: TagService,
                private projectService: ProjectService,
                private datasetService: DatasetService,
                private router: Router,
                private route: ActivatedRoute,
                @Inject(KININTEL_CONFIG) public config: KinintelModuleConfig,
                private location: Location) {
    }

    async ngOnInit() {
        this.activeProject = this.projectService.activeProject.getValue();

        this.activeProjectSub = this.projectService.activeProject.subscribe(activeProject => {
            this.activeProject = activeProject;

            this.projectSettings = (this.activeProject && this.activeProject.settings) ? (Array.isArray(this.activeProject.settings) ? {
                hideExisting: false, shortcutPosition: 'after', homeDashboard: {}, shortcutsMenu: []
            } : this.activeProject.settings) : {
                hideExisting: false, shortcutPosition: 'after', homeDashboard: {}, shortcutsMenu: []
            };

            const listKey = this.getCurrentPathListKey();
            if (this.projectSettings[listKey]) {
                this.listStyle = this.projectSettings[listKey];
            }
        });

        this.searchText.subscribe(() => {
            this.page = 1;
            this.offset = 0;
        });

        const pagingValues = this.projectService.getDataItemPagingValues();
        this.limit = pagingValues.limit || 10;
        this.offset = pagingValues.offset || 0;
        this.page = pagingValues.page || 1;

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

        this.getCategories();

        merge(this.searchText, this.activeTagSub, this.projectSub, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasets()
                )
            ).subscribe((datasets: any) => {
            this.datasets = datasets;

            if (this.categories.length) {
                this.gridItems = {};
                this.categories.forEach(category => {
                    const items = _.filter(datasets, dataset => {
                        return !!_.find(dataset.categories, {key: category.key});
                    });
                    this.gridItems[category.key] = {
                        key: category.key,
                        title: category.category,
                        list: items
                    };
                });

                const otherItems = _.filter(datasets, dataset => {
                    return !dataset.categories || !dataset.categories.length;
                });
                this.gridItems.other = {
                    key: 'other',
                    title: 'Other',
                    list: otherItems
                };
            } else {
                this.listStyle = 'LIST';
            }
            this.loading = false;
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

    public updateListStyle() {
        if (!this.admin) {
            this.datasets = [];
            const listKey = this.getCurrentPathListKey();
            this.projectSettings[listKey] = this.listStyle;
            this.projectService.updateProjectSettings(this.activeProject.projectKey, this.projectSettings);
        }
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
            closeOnNavigation: true,
            data: {
                datasetInstanceSummary,
                backendUrl: this.backendUrl,
                showChart: false,
                admin: this.admin,
                newTitle: this.newTitle ? this.newTitle + ' Name' : null,
                newDescription: this.newDescription || null,
                accountId: this.accountId,
                breadcrumb: this.headingLabel || 'Datasets',
                url: this.url
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

    private getDatasets() {
        this.projectService.setDataItemPagingValue(this.limit, this.offset, this.page);

        const checkedCategories = _.filter(this.categories, 'checked');
        return this.datasetService.getDatasets(
            this.searchText.getValue() || '',
            this.listStyle === 'GRID' ? '1000' : this.limit.toString(),
            this.listStyle === 'GRID' ? '0' : this.offset.toString(),
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
        return this.datasetService.getDatasetCategories(this.shared).then(categories => this.categories = categories);
    }

    private getCurrentPathListKey() {
        const currentPath = this.location.path().replace('/', '-');
        return _.camelCase('listStyle' + currentPath);
    }

    protected readonly Object = Object;
}
