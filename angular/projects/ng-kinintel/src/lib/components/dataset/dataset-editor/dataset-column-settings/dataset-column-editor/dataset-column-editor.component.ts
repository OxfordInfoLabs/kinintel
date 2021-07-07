import {Component, Input, OnInit, Output, EventEmitter} from '@angular/core';
import {CdkDragDrop, moveItemInArray} from '@angular/cdk/drag-drop';
import * as _ from 'lodash';

@Component({
    selector: 'ki-dataset-column-editor',
    templateUrl: './dataset-column-editor.component.html',
    styleUrls: ['./dataset-column-editor.component.sass']
})
export class DatasetColumnEditorComponent implements OnInit {

    @Input() columns: any = [];
    @Input() toggleAll = true;

    @Output() columnsChange = new EventEmitter();

    public allColumns = true;

    constructor() {
    }

    ngOnInit(): void {
        this.columns.forEach(column => {
            const duplicate = _.filter(this.columns, {name: column.name, title: column.title}) > 1;
            column.duplicate = duplicate;
            column.selected = !duplicate;
        });
        this.allColumns = _.every(this.columns, 'selected');
    }

    public toggleAllColumns(event) {
        this.columns.map(column => {
            column.selected = event.checked;
            return column;
        });
    }

    public allSelected() {
        this.allColumns = _.every(this.columns, 'selected');
    }

    public drop(event: CdkDragDrop<string[]>) {
        moveItemInArray(this.columns, event.previousIndex, event.currentIndex);
        this.columnsChange.emit(this.columns);
    }

}
