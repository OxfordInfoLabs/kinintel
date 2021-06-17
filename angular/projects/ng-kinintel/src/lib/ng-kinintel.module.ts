import {ModuleWithProviders, NgModule} from '@angular/core';
import {DashboardEditorComponent} from './components/dashboard-editor/dashboard-editor.component';
import {MatButtonModule} from '@angular/material/button';
import {MatIconModule} from '@angular/material/icon';
import {ChartsModule} from 'ng2-charts';
import {ItemComponentComponent} from './components/dashboard-editor/item-component/item-component.component';
import {BrowserModule} from '@angular/platform-browser';
import {CommonModule} from '@angular/common';
import {ConfigureItemComponent} from './components/dashboard-editor/configure-item/configure-item.component';
import {MatDialogModule} from '@angular/material/dialog';
import {NgxResizableModule} from '@3dgenomes/ngx-resizable';
import {MatMenuModule} from '@angular/material/menu';
import {MatExpansionModule} from '@angular/material/expansion';
import {MatFormFieldModule} from '@angular/material/form-field';
import {MatInputModule} from '@angular/material/input';
import {MatChipsModule} from '@angular/material/chips';
import {DatasourceComponent} from './components/datasource/datasource.component';
import {DatasetComponent} from './components/dataset/dataset.component';
import {DatasetEditorComponent} from './components/dataset/dataset-editor/dataset-editor.component';
import {MatTableModule} from '@angular/material/table';
import {MatPaginatorModule} from '@angular/material/paginator';
import {MatSortModule} from '@angular/material/sort';
import {ProjectPickerComponent} from './components/project-picker/project-picker.component';
import {DragDropModule} from '@angular/cdk/drag-drop';
import {FormsModule} from '@angular/forms';
import {DatasetFilterComponent} from './components/dataset/dataset-editor/dataset-filters/dataset-filter/dataset-filter.component';
import {DataExplorerComponent} from './components/data-explorer/data-explorer.component';
import {TagPickerComponent} from './components/tag-picker/tag-picker.component';
import {DashboardsComponent} from './components/dashboards/dashboards.component';
import {DatasetFilterJunctionComponent} from './components/dataset/dataset-editor/dataset-filters/dataset-filter-junction/dataset-filter-junction.component';
import {MatButtonToggleModule} from '@angular/material/button-toggle';
import {DatasetNameDialogComponent} from './components/dataset/dataset-editor/dataset-name-dialog/dataset-name-dialog.component';
import {DatasetSummariseComponent} from './components/dataset/dataset-editor/dataset-summarise/dataset-summarise.component';
import {MatSelectModule} from '@angular/material/select';
import {DatasetFiltersComponent} from './components/dataset/dataset-editor/dataset-filters/dataset-filters.component';
import {DatasetParameterValuesComponent} from './components/dataset/dataset-editor/dataset-parameter-values/dataset-parameter-values.component';
import {DatasetParameterTypeComponent} from './components/dataset/dataset-editor/dataset-parameter-values/dataset-parameter-type/dataset-parameter-type.component';
import {MatSlideToggleModule} from '@angular/material/slide-toggle';


@NgModule({
    declarations: [
        DashboardEditorComponent,
        ItemComponentComponent,
        ConfigureItemComponent,
        DatasourceComponent,
        DatasetComponent,
        DatasetEditorComponent,
        ProjectPickerComponent,
        DatasetFilterComponent,
        DataExplorerComponent,
        TagPickerComponent,
        DashboardsComponent,
        DatasetFilterJunctionComponent,
        DatasetNameDialogComponent,
        DatasetSummariseComponent,
        DatasetFiltersComponent,
        DatasetParameterValuesComponent,
        DatasetParameterTypeComponent
    ],
    imports: [
        BrowserModule,
        CommonModule,
        MatButtonModule,
        MatIconModule,
        ChartsModule,
        MatDialogModule,
        NgxResizableModule,
        MatMenuModule,
        MatExpansionModule,
        MatFormFieldModule,
        MatInputModule,
        MatChipsModule,
        MatTableModule,
        MatPaginatorModule,
        MatSortModule,
        DragDropModule,
        FormsModule,
        MatButtonToggleModule,
        MatSelectModule,
        MatSlideToggleModule
    ],
    exports: [
        DashboardEditorComponent,
        // ItemComponentComponent,
        // ConfigureItemComponent,
        DatasourceComponent,
        DatasetComponent,
        // DatasetEditorComponent,
        ProjectPickerComponent,
        // DatasetFilterComponent,
        // DataExplorerComponent,
        TagPickerComponent,
        DashboardsComponent,
        // DatasetFilterJunctionComponent,
        // DatasetNameDialogComponent,
        // DatasetFiltersComponent
    ]
})
export class NgKinintelModule {
    static forRoot(conf?: KinintelModuleConfig): ModuleWithProviders<NgKinintelModule> {
        return {
            ngModule: NgKinintelModule,
            providers: [
                {provide: KinintelModuleConfig, useValue: conf || {}}
            ]
        };
    }
}

export class KinintelModuleConfig {
    backendURL: string;
    tagLabel?: string;
    tagMenuLabel?: string;
}
