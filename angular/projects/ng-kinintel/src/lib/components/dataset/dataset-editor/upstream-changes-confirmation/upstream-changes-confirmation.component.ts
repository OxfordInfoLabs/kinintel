import {Component, OnInit} from '@angular/core';
import {MatDialogRef} from '@angular/material/dialog';

@Component({
    selector: 'ki-upstream-changes-confirmation',
    templateUrl: './upstream-changes-confirmation.component.html',
    styleUrls: ['./upstream-changes-confirmation.component.sass']
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
