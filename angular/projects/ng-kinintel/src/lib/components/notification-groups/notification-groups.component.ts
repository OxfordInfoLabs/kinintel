import {Component, OnInit} from '@angular/core';
import {Router} from '@angular/router';
import {NotificationService} from '../../services/notification.service';
import {EditNotificationGroupComponent} from '../notification-groups/edit-notification-group/edit-notification-group.component';
import {MatDialog} from '@angular/material/dialog';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {debounceTime, map, switchMap} from 'rxjs/operators';
import * as _ from 'lodash';

@Component({
    selector: 'ki-notification-groups',
    templateUrl: './notification-groups.component.html',
    styleUrls: ['./notification-groups.component.sass']
})
export class NotificationGroupsComponent implements OnInit {

    public notificationGroups = [];
    public limit = new BehaviorSubject(10);
    public offset = new BehaviorSubject(0);
    public page = 1;
    public endOfResults = false;
    public loading = true;

    private reload = new Subject();

    constructor(private notificationService: NotificationService,
                private router: Router,
                private matDialog: MatDialog) {
    }

    ngOnInit(): void {
        merge(this.limit, this.offset, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getNotificationGroups()
                )
            ).subscribe((groups: any) => {
            this.endOfResults = groups.length < this.limit.getValue();
            this.notificationGroups = groups;
            this.loading = false;
        });
    }

    public editNotification(id) {
        const dialogRef = this.matDialog.open(EditNotificationGroupComponent, {
            width: '800px',
            height: '575px',
            data: {
                groupId: id
            }
        });

        dialogRef.afterClosed().subscribe(res => {
            if (res) {
                this.reload.next(Date.now());
            }
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

    public removeNotification(id) {
        const message = 'Are you sure you would like to remove this notification group?';
        if (window.confirm(message)) {
            this.notificationService.removeNotificationGroup(id).then(() => {
                this.reload.next(Date.now());
            });
        }
    }

    private getNotificationGroups() {
        return this.notificationService.getNotificationGroups(
            this.limit.getValue().toString(),
            this.offset.getValue().toString()
        ).pipe(map((snapshots: any) => {
                return _.filter(snapshots, snapshot => {
                    return snapshot.taskStatus !== 'PENDING';
                });
            })
        );
    }

}
