import {Component, OnInit} from '@angular/core';
import {DashboardService} from '../../services/dashboard.service';

@Component({
    selector: 'app-dashboards',
    templateUrl: './dashboards.component.html',
    styleUrls: ['./dashboards.component.sass']
})
export class DashboardsComponent implements OnInit {

    constructor(public dashboardService: DashboardService) {
    }

    ngOnInit(): void {
    }

}
