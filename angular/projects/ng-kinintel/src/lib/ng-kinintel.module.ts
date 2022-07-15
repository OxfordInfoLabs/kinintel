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
import {DatasetEditorComponent, DatasetEditorPopupComponent} from './components/dataset/dataset-editor/dataset-editor.component';
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
import { DatasetAddParameterComponent } from './components/dataset/dataset-editor/dataset-parameter-values/dataset-add-parameter/dataset-add-parameter.component';
import { DatasetAddJoinComponent } from './components/dataset/dataset-editor/dataset-add-join/dataset-add-join.component';
import {MatStepperModule} from '@angular/material/stepper';
import {MatCheckboxModule} from '@angular/material/checkbox';
import { DatasetCreateFormulaComponent } from './components/dataset/dataset-editor/dataset-create-formula/dataset-create-formula.component';
import {MatListModule} from '@angular/material/list';
import { DatasetColumnSettingsComponent } from './components/dataset/dataset-editor/dataset-column-settings/dataset-column-settings.component';
import { DatasetColumnEditorComponent } from './components/dataset/dataset-editor/dataset-column-settings/dataset-column-editor/dataset-column-editor.component';
import {MatProgressSpinnerModule} from '@angular/material/progress-spinner';
import { SourceSelectorDialogComponent } from './components/dashboard-editor/source-selector-dialog/source-selector-dialog.component';
import {MatSidenavModule} from '@angular/material/sidenav';
import { DashboardParameterComponent } from './components/dashboard-editor/dashboard-parameter/dashboard-parameter.component';
import {MatTabsModule} from '@angular/material/tabs';
import {RouterModule} from '@angular/router';
import {NotificationGroupsComponent} from './components/notification-groups/notification-groups.component';
import {EditNotificationGroupComponent} from './components/notification-groups/edit-notification-group/edit-notification-group.component';
import {MatAutocompleteModule} from '@angular/material/autocomplete';
import { AlertGroupsComponent } from './components/alert-groups/alert-groups.component';
import { EditAlertGroupComponent } from './components/alert-groups/edit-alert-group/edit-alert-group.component';
import { EditDashboardAlertComponent } from './components/dashboard-editor/configure-item/edit-dashboard-alert/edit-dashboard-alert.component';
import { SnapshotProfileDialogComponent } from './components/data-explorer/snapshot-profile-dialog/snapshot-profile-dialog.component';
import { TaskTimePeriodsComponent } from './components/task-time-periods/task-time-periods.component';
import { SnapshotsComponent } from './components/snapshots/snapshots.component';
import { ViewDashboardComponent } from './components/dashboards/view-dashboard/view-dashboard.component';
import {MatSnackBarModule} from '@angular/material/snack-bar';
import {MatRadioModule} from '@angular/material/radio';
import { FeedsComponent } from './components/feeds/feeds.component';
import { FeedComponent } from './components/feeds/feed/feed.component';
import { ExportDataComponent } from './components/data-explorer/export-data/export-data.component';
import {MatTooltipModule} from '@angular/material/tooltip';
import { MetadataComponent } from './components/metadata/metadata.component';
import { AvailableColumnsComponent } from './components/dataset/dataset-editor/available-columns/available-columns.component';
import { JobTasksComponent } from './components/job-tasks/job-tasks.component';
import { CreateDatasourceComponent } from './components/datasource/create-datasource/create-datasource.component';
import { ImportDataComponent } from './components/datasource/create-datasource/import-data/import-data.component';
import { WhitelistedSqlFunctionsComponent } from './components/whitelisted-sql-functions/whitelisted-sql-functions.component';
import { HtmlDocumentationComponent } from './components/dashboard-editor/configure-item/html-documentation/html-documentation.component';
import { DataPickerComponent } from './components/data-picker/data-picker.component';
import { CreateDatasetComponent } from './components/dataset/create-dataset/create-dataset.component';
import { TableCellFormatterComponent } from './components/dashboard-editor/configure-item/table-cell-formatter/table-cell-formatter.component';
import { DocumentDatasourceComponent } from './components/datasource/document-datasource/document-datasource.component';
import { AngularD3CloudModule } from 'angular-d3-cloud';
import {MatProgressBarModule} from '@angular/material/progress-bar';

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
        DatasetParameterTypeComponent,
        DatasetAddParameterComponent,
        DatasetAddJoinComponent,
        DatasetCreateFormulaComponent,
        DatasetColumnSettingsComponent,
        DatasetColumnEditorComponent,
        SourceSelectorDialogComponent,
        DashboardParameterComponent,
        NotificationGroupsComponent,
        EditNotificationGroupComponent,
        AlertGroupsComponent,
        EditAlertGroupComponent,
        EditDashboardAlertComponent,
        SnapshotProfileDialogComponent,
        TaskTimePeriodsComponent,
        SnapshotsComponent,
        ViewDashboardComponent,
        DatasetEditorPopupComponent,
        FeedsComponent,
        FeedComponent,
        ExportDataComponent,
        MetadataComponent,
        AvailableColumnsComponent,
        JobTasksComponent,
        CreateDatasourceComponent,
        ImportDataComponent,
        WhitelistedSqlFunctionsComponent,
        HtmlDocumentationComponent,
        DataPickerComponent,
        CreateDatasetComponent,
        TableCellFormatterComponent,
        DocumentDatasourceComponent
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
        MatSlideToggleModule,
        MatStepperModule,
        MatCheckboxModule,
        MatListModule,
        MatProgressSpinnerModule,
        MatSidenavModule,
        MatTabsModule,
        RouterModule,
        MatAutocompleteModule,
        MatSnackBarModule,
        MatRadioModule,
        MatTooltipModule,
        AngularD3CloudModule,
        MatProgressBarModule
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
        // DatasetFiltersComponent,
        NotificationGroupsComponent,
        EditNotificationGroupComponent,
        AlertGroupsComponent,
        EditAlertGroupComponent,
        SnapshotsComponent,
        ViewDashboardComponent,
        FeedsComponent,
        CreateDatasourceComponent,
        DocumentDatasourceComponent
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
