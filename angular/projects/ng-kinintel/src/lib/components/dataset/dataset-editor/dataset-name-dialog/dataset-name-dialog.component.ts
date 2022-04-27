import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';

@Component({
    selector: 'ki-dataset-name-dialog',
    templateUrl: './dataset-name-dialog.component.html',
    styleUrls: ['./dataset-name-dialog.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class DatasetNameDialogComponent implements OnInit {

    public title: string;
    public description: string;

    constructor(public dialogRef: MatDialogRef<DatasetNameDialogComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        this.title = this.data.title;
        this.description = this.data.description;
    }

}
