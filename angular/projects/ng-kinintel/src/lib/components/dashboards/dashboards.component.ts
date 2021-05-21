import {Component, Input, OnInit} from '@angular/core';
import {BehaviorSubject, merge} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {Router} from '@angular/router';

@Component({
    selector: 'ki-dashboards',
    templateUrl: './dashboards.component.html',
    styleUrls: ['./dashboards.component.sass']
})
export class DashboardsComponent implements OnInit {

    @Input() dashboardService: any;

    public dashboards: any = [];
    public searchText = new BehaviorSubject('');
    public limit = new BehaviorSubject(10);
    public offset = new BehaviorSubject(0);

    constructor(private router: Router) {
    }

    ngOnInit(): void {
        merge(this.searchText, this.limit, this.offset)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
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
