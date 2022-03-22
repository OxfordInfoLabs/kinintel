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
    public page = 1;
    public endOfResults = false;

    private reload = new Subject();

    constructor(private projectService: ProjectService,
                private alertService: AlertService) {

    }

    ngOnInit(): void {
        if (this.projectService) {
            this.projectSub = this.projectService.activeProject;
        }

        merge(this.searchText, this.limit, this.offset, this.projectSub, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getAlertGroups()
                )
            ).subscribe((alertGroups: any) => {
            this.endOfResults = alertGroups.length < this.limit.getValue();
            this.alertGroups = alertGroups;
        });

    }

    public increaseOffset() {
        this.page = this.page + 1;
        this.offset.next((this.limit.getValue() * this.page) - this.limit.getValue());
    }

    public decreaseOffset() {
        this.page = this.page <= 1 ? 1 : this.page - 1;
        this.offset.next((this.limit.getValue() * this.page) - this.limit.getValue());
    }

    public pageSizeChange(value) {
        this.limit.next(value);
    }

    public delete(id) {
        const message = 'Are you sure you would like to delete this Alert Group?';
        if (window.confirm(message)) {
            this.alertService.deleteAlertGroup(id).then(() => {
                this.reload.next(Date.now());
            });
        }
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
