import {CUSTOM_ELEMENTS_SCHEMA, ModuleWithProviders, NgModule} from '@angular/core';
import {DashboardEditorComponent} from './components/dashboard-editor/dashboard-editor.component';
import {MatButtonModule} from '@angular/material/button';
import {MatIconModule} from '@angular/material/icon';
import {BrowserModule} from '@angular/platform-browser';
import {CommonModule} from '@angular/common';
import {ConfigureItemComponent} from './components/dashboard-editor/configure-item/configure-item.component';
import {MatDialogModule} from '@angular/material/dialog';
import {NgxResizableModule} from '@3dgenomes/ngx-resizable';
import {MatMenuModule} from '@angular/material/menu';
import {MatExpansionModule} from '@angular/material/expansion';
import {MatFormFieldModule} from '@angular/material/form-field';
import {MatInputModule} from '@angular/material/input';
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
import {MatProgressBarModule} from '@angular/material/progress-bar';
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
import { QueryCachingComponent } from './components/query-caching/query-caching.component';
import { EditQueryCacheComponent } from './components/query-caching/edit-query-cache/edit-query-cache.component';
import {
    SnapshotApiAccessComponent
} from './components/data-explorer/snapshot-api-access/snapshot-api-access.component';
import { DataSharingInviteComponent } from './components/data-sharing-invite/data-sharing-invite.component';
import { DataSearchComponent } from './components/data-search/data-search.component';
import { ImportWizardComponent } from './components/datasource/create-datasource/import-data/import-wizard/import-wizard.component';
import { AdvancedSettingsComponent } from './components/datasource/create-datasource/advanced-settings/advanced-settings.component';
import {MatChipsModule} from '@angular/material/chips';
import { RemoveTransformationWarningComponent } from './components/dataset/dataset-editor/remove-transformation-warning/remove-transformation-warning.component';
import { ExportProjectComponent } from './components/export-project/export-project.component';
import { ChangeSourceWarningComponent } from './components/data-explorer/change-source-warning/change-source-warning.component';
import { DatasetFilterInclusionComponent } from './components/dataset/dataset-editor/dataset-filters/dataset-filter-inclusion/dataset-filter-inclusion.component';
import { FeedApiModalComponent } from './components/shared-with-me/feed-api-modal/feed-api-modal.component';
import {KININTEL_CONFIG, KinintelModuleConfig} from './kinintel-config';
import { QueryCacheViewComponent } from './components/query-caching/query-cache-view/query-cache-view.component';
import {
    ItemComponentComponent
} from './components/dashboard-editor/item-component/item-component.component';


@NgModule({
    declarations: [
        DashboardEditorComponent,
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
        MarketplaceComponent,
        QueryCachingComponent,
        EditQueryCacheComponent,
        SnapshotApiAccessComponent,
        DataSharingInviteComponent,
        DataSearchComponent,
        ImportWizardComponent,
        AdvancedSettingsComponent,
        RemoveTransformationWarningComponent,
        ExportProjectComponent,
        ChangeSourceWarningComponent,
        DatasetFilterInclusionComponent,
        FeedApiModalComponent,
        QueryCacheViewComponent
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
        MatProgressBarModule,
        CodemirrorModule,
        ClipboardModule,
        CdkOverlayOrigin,
        CdkConnectedOverlay,
        ItemComponentComponent
    ],
    exports: [
        DashboardEditorComponent,
        DatasourceComponent,
        DatasetComponent,
        ProjectPickerComponent,
        TagPickerComponent,
        DashboardsComponent,
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
        SharedWithMeComponent,
        SnapshotApiAccessComponent,
        QueryCachingComponent,
        DataSharingInviteComponent,
        ExportProjectComponent,
        ShareQueryComponent
    ],
    providers: [DashboardChangesGuard],
    schemas: [CUSTOM_ELEMENTS_SCHEMA]
})
export class NgKinintelModule {
    static forRoot(conf?: KinintelModuleConfig): ModuleWithProviders<NgKinintelModule> {
        return {
            ngModule: NgKinintelModule,
            providers: [
                {provide: KININTEL_CONFIG, useValue: conf || {}}
            ]
        };
    }
}

