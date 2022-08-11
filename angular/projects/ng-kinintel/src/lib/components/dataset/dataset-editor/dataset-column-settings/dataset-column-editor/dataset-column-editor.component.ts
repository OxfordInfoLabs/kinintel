import {Component, Input, OnInit, Output, EventEmitter, OnDestroy} from '@angular/core';
import {CdkDragDrop, moveItemInArray} from '@angular/cdk/drag-drop';
import * as lodash from 'lodash';
const _ = lodash.default;
import {Subject} from 'rxjs';

@Component({
    selector: 'ki-dataset-column-editor',
    templateUrl: './dataset-column-editor.component.html',
    styleUrls: ['./dataset-column-editor.component.sass']
})
export class DatasetColumnEditorComponent implements OnInit, OnDestroy {

    @Input() columns: any = [];
    @Input() toggleAll = true;
    @Input() titleOnly = false;
    @Input() resetTrigger: Subject<any>;

    @Output() columnsChange = new EventEmitter();

    public allColumns = true;

    constructor() {
    }

    ngOnInit(): void {
        if (this.resetTrigger) {
            this.resetTrigger.subscribe(reset => {
                this.setColumns();
            });
        }

        this.setColumns();
    }

    ngOnDestroy() {
        if (this.resetTrigger) {
            this.resetTrigger.unsubscribe();
        }
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

    private setColumns() {
        const previouslySelected = _.some(this.columns, 'selected');
        this.columns.forEach(column => {
            const filterObject: any = {title: column.title};
            if (!this.titleOnly) {
                filterObject.name = column.name;
            }
            const duplicate = _.filter(this.columns, filterObject) > 1;
            column.duplicate = duplicate;
            if (!previouslySelected) {
                column.selected = !duplicate;
            }
            if (duplicate) {
                column.selected = false;
            }
        });

        this.allColumns = _.every(this.columns, 'selected');
    }

}
