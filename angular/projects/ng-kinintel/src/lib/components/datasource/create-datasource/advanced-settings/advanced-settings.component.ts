import {CdkDragDrop, moveItemInArray, transferArrayItem} from '@angular/cdk/drag-drop';
import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import * as lodash from 'lodash';

const _ = lodash.default;

@Component({
    selector: 'ki-advanced-settings',
    templateUrl: './advanced-settings.component.html',
    styleUrls: ['./advanced-settings.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class AdvancedSettingsComponent implements OnInit {

    public columns: any = [];
    public advancedSettings: any = {
        showAutoIncrement: false,
        primaryKeys: []
    };

    constructor(public dialogRef: MatDialogRef<AdvancedSettingsComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit() {
        this.advancedSettings.showAutoIncrement = this.data.showAutoIncrement || false;
        this.advancedSettings.primaryKeys = _.clone(_.filter(this.data.columns, {keyField: true}));
        this.columns = _.clone(this.data.columns);
    }

    public drop(event: CdkDragDrop<string[]>) {
        if (event.previousContainer === event.container) {
            moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        } else {
            transferArrayItem(
                event.previousContainer.data,
                event.container.data,
                event.previousIndex,
                event.currentIndex,
            );
        }
    }

}
