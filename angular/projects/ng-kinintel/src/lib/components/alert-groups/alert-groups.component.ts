import {Component, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {debounceTime, map, switchMap} from 'rxjs/operators';
import {ProjectService} from '../../../lib/services/project.service';
import {AlertService} from '../../../lib/services/alert.service';
import * as moment from 'moment';

@Component({
    selector: 'ki-alert-groups',
    templateUrl: './alert-groups.component.html',
    styleUrls: ['./alert-groups.component.sass']
})
export class AlertGroupsComponent implements OnInit {

    public alertGroups: any = [];
    public searchText = new BehaviorSubject('');
    public limit = new BehaviorSubject(10);
    public offset = new BehaviorSubject(0);
    public projectSub = new Subject();
    public moment = moment;

    constructor(private projectService: ProjectService,
                private alertService: AlertService) {

    }

    ngOnInit(): void {
        if (this.projectService) {
            this.projectSub = this.projectService.activeProject;
        }

        merge(this.searchText, this.limit, this.offset, this.projectSub)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getAlertGroups()
                )
            ).subscribe((alertGroups: any) => {
            this.alertGroups = alertGroups;
        });

    }

    private getAlertGroups() {
        return this.alertService.getAlertGroups(
            this.searchText.getValue() || '',
            this.limit.getValue().toString(),
            this.offset.getValue().toString()
        ).pipe(map((alertGroups: any) => {
                return alertGroups;
            })
        );
    }

}
