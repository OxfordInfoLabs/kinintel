import {NgModule} from '@angular/core';
import {DashboardEditorComponent} from './components/dashboard-editor/dashboard-editor.component';
import {MatButtonModule} from '@angular/material/button';
import {MatIconModule} from '@angular/material/icon';
import {ChartsModule} from 'ng2-charts';
import { ItemComponentComponent } from './components/dashboard-editor/item-component/item-component.component';
import {BrowserModule} from '@angular/platform-browser';
import {CommonModule} from '@angular/common';
import { GridsterModule } from 'angular2gridster';
import { ConfigureItemComponent } from './components/dashboard-editor/configure-item/configure-item.component';
import {MatDialogModule} from '@angular/material/dialog';
import {NgxResizableModule} from '@3dgenomes/ngx-resizable';
import {MatMenuModule} from '@angular/material/menu';
import {MatExpansionModule} from '@angular/material/expansion';
import {MatFormFieldModule} from '@angular/material/form-field';
import {MatInputModule} from '@angular/material/input';
import {MatChipsModule} from '@angular/material/chips';
import { DatasourceComponent } from './components/datasource/datasource.component';
import { DatasetComponent } from './components/dataset/dataset.component';
import { DatasetEditorComponent } from './components/dataset/dataset-editor/dataset-editor.component';
import {MatTableModule} from '@angular/material/table';
import {MatPaginatorModule} from '@angular/material/paginator';
import {MatSortModule} from '@angular/material/sort';


@NgModule({
    declarations: [
        DashboardEditorComponent,
        ItemComponentComponent,
        ConfigureItemComponent,
        DatasourceComponent,
        DatasetComponent,
        DatasetEditorComponent
    ],
    imports: [
        BrowserModule,
        CommonModule,
        MatButtonModule,
        MatIconModule,
        ChartsModule,
        GridsterModule.forRoot(),
        MatDialogModule,
        NgxResizableModule,
        MatMenuModule,
        MatExpansionModule,
        MatFormFieldModule,
        MatInputModule,
        MatChipsModule,
        MatTableModule,
        MatPaginatorModule,
        MatSortModule
    ],
    exports: [
        DashboardEditorComponent,
        ItemComponentComponent,
        ConfigureItemComponent,
        DatasourceComponent,
        DatasetComponent,
        DatasetEditorComponent
    ]
})
export class NgKinintelModule {
}
