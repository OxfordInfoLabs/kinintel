import {Component, OnInit} from '@angular/core';
import {MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';

@Component({
    selector: 'ki-upstream-changes-confirmation',
    templateUrl: './upstream-changes-confirmation.component.html',
    styleUrls: ['./upstream-changes-confirmation.component.sass'],
    standalone: false
})
export class UpstreamChangesConfirmationComponent implements OnInit {

    constructor(public dialogRef: MatDialogRef<UpstreamChangesConfirmationComponent>) {
    }

    ngOnInit(): void {
    }

    public close(res?) {
        this.dialogRef.close(res);
    }

}
