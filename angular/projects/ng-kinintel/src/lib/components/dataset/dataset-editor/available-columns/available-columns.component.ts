import {Component, Inject, OnInit} from '@angular/core';
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';

@Component({
    selector: 'ki-available-columns',
    templateUrl: './available-columns.component.html',
    styleUrls: ['./available-columns.component.sass'],
    host: { class: 'dialog-wrapper' },
    standalone: false
})
export class AvailableColumnsComponent implements OnInit {

    public columns: any = [];

    constructor(public dialogRef: MatDialogRef<AvailableColumnsComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        this.columns = this.data.columns;
    }

}
