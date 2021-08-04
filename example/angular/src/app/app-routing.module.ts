import {NgModule} from '@angular/core';
import {RouterModule, Routes} from '@angular/router';
import {HomeComponent} from './views/home/home.component';
import {DashboardComponent} from './views/dashboard/dashboard.component';
import {DatasetComponent} from './views/dataset/dataset.component';
import {DatasourceComponent} from './views/datasource/datasource.component';
import {LoginComponent} from './views/login/login.component';
import {AuthGuard} from './guards/auth.guard';
import {DashboardsComponent} from './views/dashboards/dashboards.component';
import {AppComponent} from './app.component';

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
