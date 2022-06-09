import {NgModule} from '@angular/core';
import {RouterModule, Routes} from '@angular/router';
import {HomeComponent} from './views/home/home.component';
import {DashboardComponent} from './views/dashboard/dashboard.component';
import {DatasetComponent} from './views/dataset/dataset.component';
import {DatasourceComponent} from './views/datasource/datasource.component';
import {LoginComponent} from './views/login/login.component';
import {DashboardsComponent} from './views/dashboards/dashboards.component';
import {AppComponent} from './app.component';
import {NotificationsComponent} from './views/notifications/notifications.component';
import {NotificationGroupsComponent} from './views/notification-groups/notification-groups.component';
import {EditNotificationGroupComponent} from './views/notification-groups/edit-notification-group/edit-notification-group.component';
import {AlertGroupsComponent} from './views/alert-groups/alert-groups.component';
import {EditAlertGroupComponent} from './views/alert-groups/edit-alert-group/edit-alert-group.component';
import {NotificationComponent} from './views/notifications/notification/notification.component';
import {SnapshotsComponent} from './views/snapshots/snapshots.component';
import {ViewDashboardComponent} from './views/dashboards/view-dashboard/view-dashboard.component';
import {FeedsComponent} from './views/feeds/feeds.component';
import {CreateDatasourceComponent} from './views/datasource/create-datasource/create-datasource.component';

const routes: Routes = [
    {
        path: '',
        component: AppComponent,
        children: [
            {
                path: 'home',
                component: HomeComponent
            },
            {
                path: 'dashboards',
                component: DashboardsComponent
            },
            {
                path: 'dashboards/:dashboard',
                component: DashboardComponent
            },
            {
                path: 'dashboards/copy/:dashboard',
                component: DashboardComponent,
                data: {
                    type: 'copy'
                }
            },
            {
                path: 'dashboards/extend/:dashboard',
                component: DashboardComponent,
                data: {
                    type: 'extend'
                }
            },
            {
                path: 'dashboards/view/:dashboard',
                component: ViewDashboardComponent
            },
            {
                path: 'dataset',
                component: DatasetComponent
            },
            {
                path: 'snapshots',
                component: SnapshotsComponent
            },
            {
                path: 'datasource',
                component: DatasourceComponent
            },
            {
                path: 'import-data',
                component: CreateDatasourceComponent
            },
            {
                path: 'import-data/:key',
                component: CreateDatasourceComponent
            },
            {
                path: 'notifications',
                component: NotificationsComponent
            },
            {
                path: 'notifications/:id',
                component: NotificationComponent
            },
            {
                path: 'notification-groups',
                component: NotificationGroupsComponent
            },
            {
                path: 'notification-groups/:id',
                component: EditNotificationGroupComponent
            },
            {
                path: 'alert-groups',
                component: AlertGroupsComponent
            },
            {
                path: 'alert-groups/:id',
                component: EditAlertGroupComponent
            },
            {
                path: 'login',
                component: LoginComponent
            },
            {
                path: 'feeds',
                component: FeedsComponent
            }
        ]
    },
    {
        path: 'dashboards/:dashboard/full',
        component: ViewDashboardComponent,
        data: {
            full: true
        }
    }
];

@NgModule({
    imports: [RouterModule.forRoot(routes)],
    exports: [RouterModule]
})
export class AppRoutingModule {
}
