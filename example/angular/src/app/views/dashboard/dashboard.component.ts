import {Component, OnInit} from '@angular/core';
import {SidenavService} from '../../services/sidenav.service';

@Component({
    selector: 'app-dashboard',
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.sass']
})
export class DashboardComponent implements OnInit {

    constructor(public sidenavService: SidenavService) {
    }

    ngOnInit(): void {
    }

}
