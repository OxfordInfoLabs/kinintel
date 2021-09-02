import {Component, OnInit} from '@angular/core';
import {NotificationService} from '../../../../lib/services/notification.service';
import {AlertService} from '../../../../lib/services/alert.service';
import {MatDialog} from '@angular/material/dialog';
import {EditNotificationGroupComponent} from '../../notification-groups/edit-notification-group/edit-notification-group.component';

@Component({
    selector: 'ki-edit-alert-group',
    templateUrl: './edit-alert-group.component.html',
    styleUrls: ['./edit-alert-group.component.sass']
})
export class EditAlertGroupComponent implements OnInit {

    public alertGroup: any = {};
    public notificationGroups: any = [];

    constructor(private notificationService: NotificationService,
                private alertService: AlertService,
                private matDialog: MatDialog) {
    }

    ngOnInit(): void {
        this.loadNotificationGroups();
    }

    public addScheduleTime() {

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

    }

    private loadNotificationGroups() {
        this.notificationService.getNotificationGroups().then(groups => {
            this.notificationGroups = groups;
        });
    }
}
