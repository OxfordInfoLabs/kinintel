import {Component, Inject, OnInit} from '@angular/core';
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';
import {AlertService} from '../../../../services/alert.service';

@Component({
    selector: 'ki-edit-dashboard-alert',
    templateUrl: './edit-dashboard-alert.component.html',
    styleUrls: ['./edit-dashboard-alert.component.sass'],
    host: { class: 'dialog-wrapper' },
    standalone: false
})
export class EditDashboardAlertComponent implements OnInit {

    public alert: any = {};
    public filterFields: any = [];
    public alertGroups: any = [];

    constructor(public dialogRef: MatDialogRef<EditDashboardAlertComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private alertService: AlertService) {
    }

    ngOnInit(): void {
        this.alertService.getAlertGroups().toPromise().then(groups => {
            this.alertGroups = groups;
        });

        this.alert = this.data.alert || {
            filterTransformation: {
                logic: 'AND',
                filters: [{
                    lhsExpression: '',
                    rhsExpression: '',
                    filterType: ''
                }],
                filterJunctions: []
            },
            matchRuleConfiguration: {
                matchType: 'equals',
                value: 1
            },
            enabled: true
        };


        this.filterFields = this.data.filterFields;
    }

}
