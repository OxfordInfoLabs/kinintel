import {NgModule} from '@angular/core';
import {BrowserModule} from '@angular/platform-browser';

import {AppRoutingModule} from './app-routing.module';
import {AppComponent} from './app.component';
import {BrowserAnimationsModule} from '@angular/platform-browser/animations';
import {HomeComponent} from './views/home/home.component';
import {MatToolbarModule} from '@angular/material/toolbar';
import {MatSidenavModule} from '@angular/material/sidenav';
import {MatIconModule} from '@angular/material/icon';
import {MatButtonModule} from '@angular/material/button';
import { DashboardComponent } from './views/dashboard/dashboard.component';
import { DatasetComponent } from './views/dataset/dataset.component';
import { DatasourceComponent } from './views/datasource/datasource.component';
import {NgKinintelModule} from 'ng-kinintel';
import { LoginComponent } from './views/login/login.component';
import {NgKiniAuthModule} from 'ng-kiniauth';
import {environment} from '../environments/environment';
import {SessionInterceptor} from './session.interceptor';
import {HTTP_INTERCEPTORS} from '@angular/common/http';
import {MatChipsModule} from '@angular/material/chips';
import {MatSnackBarModule} from '@angular/material/snack-bar';
import { DashboardsComponent } from './views/dashboards/dashboards.component';
import {MatProgressBarModule} from '@angular/material/progress-bar';
import { RouterComponent } from './router/router.component';
import { NotificationsComponent } from './views/notifications/notifications.component';
import { NotificationGroupsComponent } from './views/notification-groups/notification-groups.component';
import { EditNotificationGroupComponent } from './views/notification-groups/edit-notification-group/edit-notification-group.component';
import { AlertGroupsComponent } from './views/alert-groups/alert-groups.component';
import { EditAlertGroupComponent } from './views/alert-groups/edit-alert-group/edit-alert-group.component';
import {MatBadgeModule} from '@angular/material/badge';
import {MatMenuModule} from '@angular/material/menu';
import { NotificationComponent } from './views/notifications/notification/notification.component';
import {MatDialogModule} from '@angular/material/dialog';
import { SnapshotsComponent } from './views/snapshots/snapshots.component';
import { ViewDashboardComponent } from './views/dashboards/view-dashboard/view-dashboard.component';
import { FeedsComponent } from './views/feeds/feeds.component';
import {QuillModule} from 'ngx-quill';

@NgModule({
    declarations: [
        AppComponent,
        HomeComponent,
        DashboardComponent,
        DatasetComponent,
        DatasourceComponent,
        LoginComponent,
        DashboardsComponent,
        RouterComponent,
        NotificationsComponent,
        NotificationGroupsComponent,
        EditNotificationGroupComponent,
        AlertGroupsComponent,
        EditAlertGroupComponent,
        NotificationComponent,
        SnapshotsComponent,
        ViewDashboardComponent,
        FeedsComponent
    ],
    imports: [
        BrowserModule,
        AppRoutingModule,
        BrowserAnimationsModule,
        MatToolbarModule,
        MatSidenavModule,
        MatIconModule,
        MatButtonModule,
        NgKinintelModule.forRoot({
            backendURL: environment.accountURL
        }),
        NgKiniAuthModule.forRoot({
            guestHttpURL: `${environment.backendURL}/guest`,
            accessHttpURL: `${environment.backendURL}/account`
        }),
        MatChipsModule,
        MatSnackBarModule,
        MatProgressBarModule,
        MatBadgeModule,
        MatMenuModule,
        MatDialogModule,
        QuillModule.forRoot()
    ],
    providers: [
        {
            provide: HTTP_INTERCEPTORS,
            useClass: SessionInterceptor,
            multi: true
        }
    ],
    bootstrap: [RouterComponent]
})
export class AppModule {
}
