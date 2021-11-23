import {Component, OnInit} from '@angular/core';
import {SidenavService} from '../../../services/sidenav.service';

@Component({
    selector: 'app-view-dashboard',
    templateUrl: './view-dashboard.component.html',
    styleUrls: ['./view-dashboard.component.sass']
})
export class ViewDashboardComponent implements OnInit {

    constructor(public sidenavService: SidenavService) {
    }

    ngOnInit(): void {
    }

}
