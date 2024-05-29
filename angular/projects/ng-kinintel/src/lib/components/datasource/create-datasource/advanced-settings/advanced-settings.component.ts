import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';

@Component({
    selector: 'ki-advanced-settings',
    templateUrl: './advanced-settings.component.html',
    styleUrls: ['./advanced-settings.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class AdvancedSettingsComponent implements OnInit {

    public advancedSettings: any = {
        showAutoIncrement: false
    };

    constructor(public dialogRef: MatDialogRef<AdvancedSettingsComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit() {
        this.advancedSettings.showAutoIncrement = this.data.showAutoIncrement || false;
    }

}
