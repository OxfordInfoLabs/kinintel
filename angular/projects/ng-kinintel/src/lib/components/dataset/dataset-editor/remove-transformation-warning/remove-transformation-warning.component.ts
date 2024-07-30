import {Component, OnInit} from '@angular/core';
import {MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';

@Component({
    selector: 'ki-remove-transformation-warning',
    templateUrl: './remove-transformation-warning.component.html',
    styleUrls: ['./remove-transformation-warning.component.sass']
})
export class RemoveTransformationWarningComponent implements OnInit {
    constructor(public dialogRef: MatDialogRef<RemoveTransformationWarningComponent>) {
    }

    ngOnInit(): void {
    }

    public close(res?) {
        this.dialogRef.close(res);
    }
}
