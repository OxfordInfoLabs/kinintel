import {Component, Input, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject, Subscription} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {Router} from '@angular/router';
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
    public limit = new BehaviorSubject(10);
    public offset = new BehaviorSubject(0);
    public activeTagSub = new Subject();
    public projectSub = new Subject();
    public categories: any = [];
    public filteredCategories: any = [];
    public Date = Date;
    public activeTag: any;
    public reload = new Subject();

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

        merge(this.searchText, this.limit, this.offset, this.activeTagSub, this.projectSub, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDashboards()
                )
            ).subscribe((dashboards: any) => {
            this.dashboards = dashboards;
        });

        this.getCategories();
    }

    public view(id) {
        this.router.navigateByUrl(`dashboards/${id}${this.admin ? '?a=true' : ''}`);
    }

    public delete(id) {

    }

    public addCategoryToFilter(category) {

    }

    public removeCategory(index) {
        this.filteredCategories = _.filter(this.filteredCategories, (value, key) => {
            return key !== index;
        });
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

    private getDashboards() {
        return this.dashboardService.getDashboards(
            this.searchText.getValue() || '',
            this.limit.getValue().toString(),
            this.offset.getValue().toString(),
            this.shared ? null : '',
            _.map(this.filteredCategories, 'key')
        ).pipe(map((dashboards: any) => {
                return dashboards;
            })
        );
    }

    private getCategories(){
        this.dashboardService.getDashboardCategories().then(categories => this.categories = categories);
    }
}
