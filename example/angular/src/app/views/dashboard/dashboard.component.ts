import {Component, HostListener, OnInit, ViewChild} from '@angular/core';
import {SidenavService} from '../../services/sidenav.service';
import {DashboardCanDeactivate, DashboardEditorComponent, DashboardService} from 'ng-kinintel';
import {Observable} from 'rxjs';
import * as lodash from 'lodash';
const _ = lodash.default;
import * as deepEqual from 'deep-equal';

@Component({
    selector: 'app-dashboard',
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.sass']
})
export class DashboardComponent implements OnInit, DashboardCanDeactivate {

    @ViewChild(DashboardEditorComponent) dashboardEditor!: DashboardEditorComponent;

    constructor(public sidenavService: SidenavService,
                private dashboardService: DashboardService) {
    }

    ngOnInit(): void {
    }

    @HostListener('window:beforeunload')
    canDeactivate(): Observable<boolean> | boolean {
        // insert logic to check if there are pending changes here;
        // returning true will navigate without confirmation
        // returning false will show a confirm dialog before navigating away
        return true;
    }

}
