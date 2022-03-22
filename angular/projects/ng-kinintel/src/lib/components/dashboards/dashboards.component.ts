import {Component, Input, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject, Subscription} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {ActivatedRoute, Router} from '@angular/router';
import {TagService} from '../../services/tag.service';
import {ProjectService} from '../../services/project.service';
import {DashboardService} from '../../services/dashboard.service';
import {KinintelModuleConfig} from '../../ng-kinintel.module';
import {MatDialog} from '@angular/material/dialog';
import {MetadataComponent} from '../metadata/metadata.component';
import * as _ from 'lodash';

@Component({
    selector: 'ki-dashboards',
    templateUrl: './dashboards.component.html',
    styleUrls: ['./dashboards.component.sass']
})
export class DashboardsComponent implements OnInit {

    @Input() headingLabel: string;
    @Input() shared: boolean;
    @Input() allowNew: boolean;
    @Input() admin: boolean;

    public dashboards: any = [];
    public searchText = new BehaviorSubject('');
    public limit = 10;
    public offset = 0;
    public page = 1;
    public endOfResults = false;
    public activeTagSub = new Subject();
    public projectSub = new Subject();
    public categories: any = [];
    public filteredCategories: any = [];
    public Date = Date;
    public activeTag: any;
    public reload = new Subject();
    public loading = true;

    private tagSub: Subscription;

    constructor(private router: Router,
                private tagService: TagService,
                private projectService: ProjectService,
                private dashboardService: DashboardService,
                private dialog: MatDialog,
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

        merge(this.searchText, this.activeTagSub, this.projectSub, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDashboards()
                )
            ).subscribe((dashboards: any) => {
            this.dashboards = dashboards;
            this.loading = false;
        });

        this.getCategories();
    }

    public view(id) {
        this.router.navigateByUrl(`dashboards/${id}${this.admin ? '?a=true' : ''}`);
    }

    public delete(id) {
        const message = 'Are you sure you would like to completely delete this Dashboard?';
        if (window.confirm(message)) {
            this.dashboardService.removeDashboard(id).then(() => {
                this.reload.next(Date.now());
            });
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

    public editMetadata(searchResult) {
        const dialogRef = this.dialog.open(MetadataComponent, {
            width: '700px',
            height: '900px',
            data: {
                metadata: _.clone(searchResult),
                service: this.dashboardService
            }
        });
        dialogRef.afterClosed().subscribe(res => {
            if (res) {
                this.reload.next(Date.now());
                this.getCategories();
            }
        });
    }

    public removeActiveTag() {
        this.tagService.resetActiveTag();
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

    private getDashboards() {
        const checkedCategories = _.filter(this.categories, 'checked');
        return this.dashboardService.getDashboards(
            this.searchText.getValue() || '',
            this.limit.toString(),
            this.offset.toString(),
            this.shared ? null : '',
            _.map(checkedCategories, 'key')
        ).pipe(map((dashboards: any) => {
                this.endOfResults = dashboards.length < this.limit;
                return dashboards;
            })
        );
    }

    private getCategories(){
        this.dashboardService.getDashboardCategories(this.shared).then(categories => this.categories = categories);
    }
}
