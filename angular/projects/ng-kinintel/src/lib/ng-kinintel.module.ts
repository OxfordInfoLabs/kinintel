import {CUSTOM_ELEMENTS_SCHEMA, ModuleWithProviders, NgModule} from '@angular/core';
import {DashboardEditorComponent} from './components/dashboard-editor/dashboard-editor.component';
import {MatLegacyButtonModule as MatButtonModule} from '@angular/material/legacy-button';
import {MatIconModule} from '@angular/material/icon';
import {ItemComponentComponent} from './components/dashboard-editor/item-component/item-component.component';
import {BrowserModule} from '@angular/platform-browser';
import {CommonModule} from '@angular/common';
import {ConfigureItemComponent} from './components/dashboard-editor/configure-item/configure-item.component';
import {MatLegacyDialogModule as MatDialogModule} from '@angular/material/legacy-dialog';
import {NgxResizableModule} from '@3dgenomes/ngx-resizable';
import {MatLegacyMenuModule as MatMenuModule} from '@angular/material/legacy-menu';
import {MatExpansionModule} from '@angular/material/expansion';
import {MatLegacyFormFieldModule as MatFormFieldModule} from '@angular/material/legacy-form-field';
import {MatLegacyInputModule as MatInputModule} from '@angular/material/legacy-input';
import {MatLegacyChipsModule as MatChipsModule} from '@angular/material/legacy-chips';
import {DatasourceComponent} from './components/datasource/datasource.component';
import {DatasetComponent} from './components/dataset/dataset.component';
import {DatasetEditorComponent, DatasetEditorPopupComponent} from './components/dataset/dataset-editor/dataset-editor.component';
import {MatLegacyTableModule as MatTableModule} from '@angular/material/legacy-table';
import {MatLegacyPaginatorModule as MatPaginatorModule} from '@angular/material/legacy-paginator';
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
import {MatLegacySelectModule as MatSelectModule} from '@angular/material/legacy-select';
import {DatasetFiltersComponent} from './components/dataset/dataset-editor/dataset-filters/dataset-filters.component';
import {DatasetParameterValuesComponent} from './components/dataset/dataset-editor/dataset-parameter-values/dataset-parameter-values.component';
import {DatasetParameterTypeComponent} from './components/dataset/dataset-editor/dataset-parameter-values/dataset-parameter-type/dataset-parameter-type.component';
import {MatLegacySlideToggleModule as MatSlideToggleModule} from '@angular/material/legacy-slide-toggle';
import { DatasetAddParameterComponent } from './components/dataset/dataset-editor/dataset-parameter-values/dataset-add-parameter/dataset-add-parameter.component';
import { DatasetAddJoinComponent } from './components/dataset/dataset-editor/dataset-add-join/dataset-add-join.component';
import {MatStepperModule} from '@angular/material/stepper';
import {MatLegacyCheckboxModule as MatCheckboxModule} from '@angular/material/legacy-checkbox';
import { DatasetCreateFormulaComponent } from './components/dataset/dataset-editor/dataset-create-formula/dataset-create-formula.component';
import {MatLegacyListModule as MatListModule} from '@angular/material/legacy-list';
import { DatasetColumnSettingsComponent } from './components/dataset/dataset-editor/dataset-column-settings/dataset-column-settings.component';
import { DatasetColumnEditorComponent } from './components/dataset/dataset-editor/dataset-column-settings/dataset-column-editor/dataset-column-editor.component';
import {MatLegacyProgressSpinnerModule as MatProgressSpinnerModule} from '@angular/material/legacy-progress-spinner';
import { SourceSelectorDialogComponent } from './components/dashboard-editor/source-selector-dialog/source-selector-dialog.component';
import {MatSidenavModule} from '@angular/material/sidenav';
import { DashboardParameterComponent } from './components/dashboard-editor/dashboard-parameter/dashboard-parameter.component';
import {MatLegacyTabsModule as MatTabsModule} from '@angular/material/legacy-tabs';
import {RouterModule} from '@angular/router';
import {NotificationGroupsComponent} from './components/notification-groups/notification-groups.component';
import {EditNotificationGroupComponent} from './components/notification-groups/edit-notification-group/edit-notification-group.component';
import {MatLegacyAutocompleteModule as MatAutocompleteModule} from '@angular/material/legacy-autocomplete';
import { AlertGroupsComponent } from './components/alert-groups/alert-groups.component';
import { EditAlertGroupComponent } from './components/alert-groups/edit-alert-group/edit-alert-group.component';
import { EditDashboardAlertComponent } from './components/dashboard-editor/configure-item/edit-dashboard-alert/edit-dashboard-alert.component';
import { SnapshotProfileDialogComponent } from './components/data-explorer/snapshot-profile-dialog/snapshot-profile-dialog.component';
import { TaskTimePeriodsComponent } from './components/task-time-periods/task-time-periods.component';
import { SnapshotsComponent } from './components/snapshots/snapshots.component';
import { ViewDashboardComponent } from './components/dashboards/view-dashboard/view-dashboard.component';
import {MatLegacySnackBarModule as MatSnackBarModule} from '@angular/material/legacy-snack-bar';
import {MatLegacyRadioModule as MatRadioModule} from '@angular/material/legacy-radio';
import { FeedsComponent } from './components/feeds/feeds.component';
import { FeedComponent } from './components/feeds/feed/feed.component';
import { ExportDataComponent } from './components/data-explorer/export-data/export-data.component';
import {MatLegacyTooltipModule as MatTooltipModule} from '@angular/material/legacy-tooltip';
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
import {MatLegacyProgressBarModule as MatProgressBarModule} from '@angular/material/legacy-progress-bar';
import {CodemirrorModule} from '@ctrl/ngx-codemirror';
import { ProjectSettingsComponent } from './components/project-settings/project-settings.component';
import { ProjectLinkSelectionComponent } from './components/project-settings/project-link-selection/project-link-selection.component';
import { UpstreamChangesConfirmationComponent } from './components/dataset/dataset-editor/upstream-changes-confirmation/upstream-changes-confirmation.component';
import {DashboardChangesGuard} from './guards/dashboard-changes.guard';
import { DashboardSettingsComponent } from './components/dashboard-editor/dashboard-settings/dashboard-settings.component';
import { ApiAccessComponent } from './components/datasource/create-datasource/api-access/api-access.component';
import {ClipboardModule} from '@angular/cdk/clipboard';
import { TabularDatasourceComponent } from './components/datasource/create-datasource/tabular-datasource/tabular-datasource.component';
import { MoveTransformationConfirmationComponent } from './components/dataset/dataset-editor/move-transformation-confirmation/move-transformation-confirmation.component';
import {CdkConnectedOverlay, CdkOverlayOrigin} from '@angular/cdk/overlay';
import {NgChartsModule} from 'ng2-charts';
import { SaveAsQueryComponent } from './components/dataset/dataset-editor/save-as-query/save-as-query.component';
import { ShareQueryComponent } from './components/dataset/dataset-editor/share-query/share-query.component';
import { SharedWithMeComponent } from './components/shared-with-me/shared-with-me.component';
import { MarketplaceComponent } from './components/marketplace/marketplace.component';

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
        DocumentDatasourceComponent,
        ProjectSettingsComponent,
        ProjectLinkSelectionComponent,
        UpstreamChangesConfirmationComponent,
        DashboardSettingsComponent,
        ApiAccessComponent,
        TabularDatasourceComponent,
        MoveTransformationConfirmationComponent,
        SaveAsQueryComponent,
        ShareQueryComponent,
        SharedWithMeComponent,
        MarketplaceComponent
    ],
    imports: [
        BrowserModule,
        CommonModule,
        MatButtonModule,
        MatIconModule,
        NgChartsModule,
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
        MatProgressBarModule,
        CodemirrorModule,
        ClipboardModule,
        CdkOverlayOrigin,
        CdkConnectedOverlay
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
        DocumentDatasourceComponent,
        ProjectSettingsComponent,
        UpstreamChangesConfirmationComponent,
        TabularDatasourceComponent,
        MarketplaceComponent,
        SharedWithMeComponent
    ],
    providers: [DashboardChangesGuard],
    schemas: [CUSTOM_ELEMENTS_SCHEMA]
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
    externalURL?: string;
    tagLabel?: string;
    tagMenuLabel?: string;
}
