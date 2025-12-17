import {Component, OnInit} from '@angular/core';
import {MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';

@Component({
    selector: 'ki-change-source-warning',
    templateUrl: './change-source-warning.component.html',
    styleUrls: ['./change-source-warning.component.sass'],
    standalone: false
})
export class ChangeSourceWarningComponent implements OnInit {

    constructor(public dialogRef: MatDialogRef<ChangeSourceWarningComponent>) {
    }

    ngOnInit(): void {
    }

    public close(res?) {
        this.dialogRef.close(res);
    }

}
