import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {CdkDragDrop, copyArrayItem, moveItemInArray} from '@angular/cdk/drag-drop';

@Component({
    selector: 'ki-dataset-summarise',
    templateUrl: './dataset-summarise.component.html',
    styleUrls: ['./dataset-summarise.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class DatasetSummariseComponent implements OnInit {

    public availableColumns: any = [];
    public summariseFields: any = [];
    public summariseExpressions: any = [];

    public readonly expressionTypes = [
        'COUNT', 'SUM', 'MIN', 'MAX', 'AVG'
    ];

    constructor(public dialogRef: MatDialogRef<DatasetSummariseComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        this.availableColumns = this.data.availableColumns;
        console.log(this.availableColumns);
    }

    drop(event: CdkDragDrop<string[]>) {
        if (event.previousContainer === event.container) {
            moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        } else {
            copyArrayItem(event.previousContainer.data,
                event.container.data,
                event.previousIndex,
                event.currentIndex);
        }
    }
}
