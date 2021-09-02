import {Component, OnInit} from '@angular/core';
import {Router} from '@angular/router';
import {NotificationService} from '../../services/notification.service';

@Component({
    selector: 'ki-notification-groups',
    templateUrl: './notification-groups.component.html',
    styleUrls: ['./notification-groups.component.sass']
})
export class NotificationGroupsComponent implements OnInit {

    public notificationGroups = [];

    constructor(private notificationService: NotificationService,
                private router: Router) {
    }

    ngOnInit(): void {
        this.loadNotificationGroups();
    }

    public editNotification(id) {
        this.router.navigate(['/notification-groups', id]);
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
