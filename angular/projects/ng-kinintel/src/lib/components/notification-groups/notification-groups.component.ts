import {Component, OnInit} from '@angular/core';
import {Router} from '@angular/router';
import {NotificationService} from '../../services/notification.service';
import {EditNotificationGroupComponent} from '../notification-groups/edit-notification-group/edit-notification-group.component';
import {MatDialog} from '@angular/material/dialog';

@Component({
    selector: 'ki-notification-groups',
    templateUrl: './notification-groups.component.html',
    styleUrls: ['./notification-groups.component.sass']
})
export class NotificationGroupsComponent implements OnInit {

    public notificationGroups = [];

    constructor(private notificationService: NotificationService,
                private router: Router,
                private matDialog: MatDialog) {
    }

    ngOnInit(): void {
        this.loadNotificationGroups();
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
                this.loadNotificationGroups();
            }
        });
    }

    public removeNotification(id) {
        const message = 'Are you sure you would like to remove this notification group?';
        if (window.confirm(message)) {
            this.notificationService.removeNotificationGroup(id).then(() => {
                this.loadNotificationGroups();
            });
        }
    }

    private loadNotificationGroups() {
        this.notificationService.getNotificationGroups().then((groups: any) => {
            this.notificationGroups = groups;
        });
    }

}
