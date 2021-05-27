import {Component, Input, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject, Subscription} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {Router} from '@angular/router';

@Component({
    selector: 'ki-dashboards',
    templateUrl: './dashboards.component.html',
    styleUrls: ['./dashboards.component.sass']
})
export class DashboardsComponent implements OnInit {

    @Input() dashboardService: any;
    @Input() tagService: any;
    @Input() projectService: any;
    @Input() environment: any = {};

    public dashboards: any = [];
    public searchText = new BehaviorSubject('');
    public limit = new BehaviorSubject(10);
    public offset = new BehaviorSubject(0);
    public activeTagSub = new Subject();
    public projectSub = new Subject();

    public activeTag: any;

    private tagSub: Subscription;

    constructor(private router: Router) {
    }

    ngOnInit(): void {
        if (this.tagService) {
            this.activeTagSub = this.tagService.activeTag;
            this.tagSub = this.tagService.activeTag.subscribe(tag => this.activeTag = tag);
        }

        if (this.projectService) {
            this.projectSub = this.projectService.activeProject;
        }

        merge(this.searchText, this.limit, this.offset, this.activeTagSub, this.projectSub)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDashboards()
                )
            ).subscribe((dashboards: any) => {
            this.dashboards = dashboards;
        });
    }

    public view(id) {
        this.router.navigate(['dashboards', id]);
    }

    public removeActiveTag() {
        this.tagService.resetActiveTag();
    }

    private getDashboards() {
        return this.dashboardService.getDashboards(
            this.searchText.getValue() || '',
            this.limit.getValue().toString(),
            this.offset.getValue().toString()
        ).pipe(map((dashboards: any) => {
                return dashboards;
            })
        );
    }
}
