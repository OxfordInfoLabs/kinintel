import {Component, Input, OnDestroy, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject, Subscription} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {MatDialog} from '@angular/material/dialog';
import {TagService} from '../../services/tag.service';
import {ProjectService} from '../../services/project.service';
import {DatasetService} from '../../services/dataset.service';
import {KinintelModuleConfig} from '../../ng-kinintel.module';
import * as _ from 'lodash';
import {MetadataComponent} from '../metadata/metadata.component';

@Component({
    selector: 'ki-dataset',
    templateUrl: './dataset.component.html',
    styleUrls: ['./dataset.component.sass']
})
export class DatasetComponent implements OnInit, OnDestroy {

    @Input() headingLabel: string;
    @Input() shared: boolean;
    @Input() admin: boolean;
    @Input() reload: Subject<any>;

    public datasets: any = [];
    public searchText = new BehaviorSubject('');
    public limit = new BehaviorSubject(10);
    public offset = new BehaviorSubject(0);
    public page = 1;
    public endOfResults = false;
    public categories: any = [];
    public filteredCategories: any = [];
    public Date = Date;
    public activeTagSub = new Subject();
    public projectSub = new Subject();
    public activeTag: any;

    private tagSub: Subscription;

    constructor(private dialog: MatDialog,
                private tagService: TagService,
                private projectService: ProjectService,
                private datasetService: DatasetService,
                public config: KinintelModuleConfig) {
    }

    ngOnInit(): void {
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

        this.getCategories();
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

    public removeCategory(index) {
        this.filteredCategories = _.filter(this.filteredCategories, (value, key) => {
            return key !== index;
        });
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
        this.offset.next((this.limit.getValue() * this.page) - this.limit.getValue());
    }

    public decreaseOffset() {
        this.page = this.page <= 1 ? 1 : this.page - 1;
        this.offset.next((this.limit.getValue() * this.page) - this.limit.getValue());
    }

    public pageSizeChange(value) {
        this.limit.next(value);
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

    private viewDataset(datasetInstanceSummary) {
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

    private getDatasets() {
        return this.datasetService.getDatasets(
            this.searchText.getValue() || '',
            this.limit.getValue().toString(),
            this.offset.getValue().toString(),
            this.shared ? null : '',
            '',
            _.map(this.filteredCategories, 'key')
        ).pipe(map((datasets: any) => {
                this.endOfResults = datasets.length < this.limit.getValue();
                return datasets;
            })
        );
    }

    private getCategories() {
        this.datasetService.getDatasetCategories().then(categories => this.categories = categories);
    }

}
