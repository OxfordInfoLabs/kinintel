import {Component, Inject, OnInit} from '@angular/core';
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';
import {DashboardService} from '../../../services/dashboard.service';

@Component({
    selector: 'ki-dashboard-settings',
    templateUrl: './dashboard-settings.component.html',
    styleUrls: ['./dashboard-settings.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class DashboardSettingsComponent implements OnInit {

    public dashboard: any;
    public refreshInterval = 0;
    public externalURL: string;
    public apiKeys: any;

    constructor(public dialogRef: MatDialogRef<DashboardSettingsComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dashboardService: DashboardService) {
    }

    ngOnInit(): void {
        this.dashboard = this.data.dashboard;

        if (!this.dashboard.externalSettings || Array.isArray(this.dashboard.externalSettings)) {
            this.dashboard.externalSettings = {};
        }

        this.refreshInterval = this.dashboard.externalSettings.refreshInterval || 0;
    }

    public setRefreshInterval(interval) {
        this.dashboard.externalSettings.refreshInterval = interval;
        this.refreshInterval = interval;
    }

    public async saveSettings() {
        this.dashboardService.saveDashboard(this.dashboard);
        this.dialogRef.close();
    }

}
