import {Component, HostListener, OnInit, ViewChild} from '@angular/core';
import {SidenavService} from '../../services/sidenav.service';
import {DashboardCanDeactivate, DashboardEditorComponent, DashboardService, ActionEvent} from 'ng-kinintel';
import {Observable, Subject} from 'rxjs';
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'app-dashboard',
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.sass']
})
export class DashboardComponent implements OnInit, DashboardCanDeactivate {

    @ViewChild(DashboardEditorComponent) dashboardEditor!: DashboardEditorComponent;

    public actionEvents: any = [
        new ActionEvent({
            name: 'new-event',
            title: 'New Event',
            actionLabel: 'Add User',
            completeLabel: 'User Added',
            event: new Subject(),
            data: [
                {FirstName: 'Leonie'}
            ]
        })
    ];

    constructor(public sidenavService: SidenavService,
                private dashboardService: DashboardService) {
    }

    ngOnInit(): void {
        this.actionEvents[0].event.subscribe(res => {
            console.log('action fired', res, this.actionEvents[0]);
            const data = {};
            data[res.key] = res.value;
            this.actionEvents[0].data.push(data);
        });
    }

    @HostListener('window:beforeunload')
    canDeactivate(): Observable<boolean> | boolean {
        // insert logic to check if there are pending changes here;
        // returning true will navigate without confirmation
        // returning false will show a confirm dialog before navigating away
        return true;
    }

}
