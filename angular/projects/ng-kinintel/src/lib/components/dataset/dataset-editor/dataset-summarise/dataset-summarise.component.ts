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
        if (this.data.config) {
            this.summariseFields = this.data.config.summariseFieldNames.map(field => {
                return _.find(this.availableColumns, {name: field});
            });
            this.summariseExpressions = this.data.config.expressions.map(expression => {
                const field = _.find(this.availableColumns, {name: expression.fieldName}) ||
                    _.find(this.availableColumns, {name: expression.expressionType + '(' + expression.fieldName + ')'});

                if (field) {
                    field.expressionType = expression.expressionType;
                    return field;
                }
                return expression;
            });
        }
    }

    drop(event: CdkDragDrop<string[]>) {
        if (event.previousContainer === event.container) {
            moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        } else {
            if (event.previousContainer.id === 'availableColumns') {
                copyArrayItem(event.previousContainer.data,
                    event.container.data,
                    event.previousIndex,
                    event.currentIndex);

                if (event.container.id === 'summariseExpressions') {
                    const item: any = event.container.data[event.currentIndex];
                    item.expressionType = 'SUM';
                }
            }
        }
    }

    public removeListItem(list, index) {
        list.splice(index, 1);
    }

    public createCustomExpression() {
        this.summariseExpressions.push({
            expressionType: 'CUSTOM'
        });
    }

    public applySettings() {
        console.log(this.summariseFields, this.summariseExpressions);
        const summariseTransformation: any = {
            summariseFieldNames: _.map(this.summariseFields, 'name'),
            expressions: _.map(this.summariseExpressions, expression => {
                const summariseExpression: any = {
                    expressionType: expression.expressionType
                };

                if (expression.expressionType !== 'CUSTOM') {
                    summariseExpression.fieldName = expression.name;
                } else {
                    summariseExpression.customExpression = expression.customExpression;
                }

                if (expression.customLabel) {
                    summariseExpression.customLabel = expression.customLabel;
                }

                return summariseExpression;
            })
        };

        this.dialogRef.close(summariseTransformation);
    }
}
