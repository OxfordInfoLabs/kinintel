import {Component, OnInit} from '@angular/core';
import {NotificationService} from '../../../../lib/services/notification.service';
import {AlertService} from '../../../../lib/services/alert.service';
import {MatDialog} from '@angular/material/dialog';
import {EditNotificationGroupComponent} from '../../notification-groups/edit-notification-group/edit-notification-group.component';
import * as _ from 'lodash';
import {ActivatedRoute, Router} from '@angular/router';

@Component({
    selector: 'ki-edit-alert-group',
    templateUrl: './edit-alert-group.component.html',
    styleUrls: ['./edit-alert-group.component.sass']
})
export class EditAlertGroupComponent implements OnInit {

    public alertGroup: any = {};
    public notificationGroups: any = [];
    public showNewTaskTimePeriod = false;
    public newTaskTimePeriod: any = {};
    public _ = _;
    public Object = Object;

    constructor(private notificationService: NotificationService,
                private alertService: AlertService,
                private matDialog: MatDialog,
                private router: Router,
                private route: ActivatedRoute) {
    }

    ngOnInit(): void {
        const id = this.route.snapshot.params.id;
        this.alertService.getAlertGroup(id).then(alertGroup => {
            if (alertGroup) {
                this.alertGroup = alertGroup;
            }
            if (!this.alertGroup.taskTimePeriods || !this.alertGroup.taskTimePeriods.length) {
                if (!this.alertGroup.taskTimePeriods) {
                    this.alertGroup.taskTimePeriods = [];
                }
                this.showNewTaskTimePeriod = true;
            }
        });
        this.loadNotificationGroups();
    }

    public notificationGroupDisplay(v1, v2) {
        return v1 && v2 && (v2.id === v2.id);
    }

    public addScheduleTime() {
        if (!this.alertGroup.taskTimePeriods) {
            this.alertGroup.taskTimePeriods = [];
        }
        this.showNewTaskTimePeriod = true;
    }

    public addTimePeriod() {
        this.alertGroup.taskTimePeriods.push(this.newTaskTimePeriod);
        this.showNewTaskTimePeriod = false;
        this.newTaskTimePeriod = {};
    }

    public removeTime(index) {
        this.alertGroup.taskTimePeriods.splice(index, 1);
        if (!this.alertGroup.taskTimePeriods.length) {
            this.showNewTaskTimePeriod = true;
            this.newTaskTimePeriod = {};
        }
    }

    public createNotificationGroup() {
        const dialogRef = this.matDialog.open(EditNotificationGroupComponent, {
            width: '800px',
            height: '575px',
            data: {
                groupId: 0
            }
        });

        dialogRef.afterClosed().subscribe(res => {
            if (res) {
                this.loadNotificationGroups();
            }
        });
    }

    public save() {
        this.alertService.saveAlertGroup(this.alertGroup).then(() => {
            this.router.navigate(['/alert-groups']);
        });
    }

    private loadNotificationGroups() {
        this.notificationService.getNotificationGroups().then(groups => {
            this.notificationGroups = groups;
        });
    }
}
