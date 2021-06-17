import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {CdkDragDrop, copyArrayItem, moveItemInArray} from '@angular/cdk/drag-drop';
import * as _ from 'lodash';

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
            console.log(event);

            if (event.container.id === 'summariseExpressions') {
                const item: any = event.container.data[event.currentIndex];
                item.expressionType = 'SUM';
            }
        }
    }

    public removeListItem(list, index) {
        list.splice(index, 1);
    }

    public applySettings() {
        const summariseTransformation: any = {
            summariseFieldNames: _.map(this.summariseFields, 'value'),
            expressions: _.map(this.summariseExpressions, expression => {
                return {
                    expressionType: expression.expressionType,
                    fieldName: expression.value
                };
            })
        };

        console.log(summariseTransformation);
    }
}
