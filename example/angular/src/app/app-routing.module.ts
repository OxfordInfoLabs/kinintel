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
                path: 'dataset',
                component: DatasetComponent
            },
            {
                path: 'datasource',
                component: DatasourceComponent
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
            }
        ]
    },
    {
        path: 'dashboards/:dashboard/full',
        component: DashboardComponent
    }
];

@NgModule({
    imports: [RouterModule.forRoot(routes)],
    exports: [RouterModule]
})
export class AppRoutingModule {
}
