import {Component, OnInit} from '@angular/core';
import {MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';

@Component({
    selector: 'ki-move-transformation-confirmation',
    templateUrl: './move-transformation-confirmation.component.html',
    styleUrls: ['./move-transformation-confirmation.component.sass']
})
export class MoveTransformationConfirmationComponent implements OnInit {

    constructor(public dialogRef: MatDialogRef<MoveTransformationConfirmationComponent>) {
    }

    ngOnInit(): void {
    }

    public close(res?) {
        this.dialogRef.close(res);
    }
}
