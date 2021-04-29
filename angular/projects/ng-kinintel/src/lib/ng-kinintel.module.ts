import {NgModule} from '@angular/core';
import {DashboardEditorComponent} from './components/dashboard-editor/dashboard-editor.component';
import {MatButtonModule} from '@angular/material/button';
import {MatIconModule} from '@angular/material/icon';
import {ChartsModule} from 'ng2-charts';
import { ItemComponentComponent } from './components/dashboard-editor/item-component/item-component.component';
import {BrowserModule} from '@angular/platform-browser';
import {CommonModule} from '@angular/common';
import { GridsterModule } from 'angular2gridster';


@NgModule({
    declarations: [
        DashboardEditorComponent,
        ItemComponentComponent
    ],
    imports: [
        BrowserModule,
        CommonModule,
        MatButtonModule,
        MatIconModule,
        ChartsModule,
        GridsterModule.forRoot()
    ],
    exports: [
        DashboardEditorComponent,
        ItemComponentComponent
    ]
})
export class NgKinintelModule {
}
