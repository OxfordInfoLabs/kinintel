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

@NgModule({
    declarations: [
        AppComponent,
        HomeComponent,
        DashboardComponent,
        DatasetComponent,
        DatasourceComponent,
        LoginComponent
    ],
    imports: [
        BrowserModule,
        AppRoutingModule,
        BrowserAnimationsModule,
        MatToolbarModule,
        MatSidenavModule,
        MatIconModule,
        MatButtonModule,
        NgKinintelModule,
        NgKiniAuthModule.forRoot({
            guestHttpURL: `${environment.backendURL}/guest`,
            accessHttpURL: `${environment.backendURL}/account`
        }),
    ],
    providers: [
        {
            provide: HTTP_INTERCEPTORS,
            useClass: SessionInterceptor,
            multi: true
        }
    ],
    bootstrap: [AppComponent]
})
export class AppModule {
}
