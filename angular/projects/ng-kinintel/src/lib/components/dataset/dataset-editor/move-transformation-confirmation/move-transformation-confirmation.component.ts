import {Component, OnInit} from '@angular/core';
import {MatDialogRef} from '@angular/material/dialog';

@Component({
    selector: 'ki-move-transformation-confirmation',
    templateUrl: './move-transformation-confirmation.component.html',
    styleUrls: ['./move-transformation-confirmation.component.sass'],
    standalone: false
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
