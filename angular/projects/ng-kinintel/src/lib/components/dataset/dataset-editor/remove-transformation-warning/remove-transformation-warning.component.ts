import {Component, OnInit} from '@angular/core';
import {MatDialogRef} from '@angular/material/dialog';

@Component({
    selector: 'ki-remove-transformation-warning',
    templateUrl: './remove-transformation-warning.component.html',
    styleUrls: ['./remove-transformation-warning.component.sass'],
    standalone: false
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
