import {Component, Input, OnInit, Output, EventEmitter} from '@angular/core';
import * as lodash from 'lodash';
const _ = lodash.default;
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {DatasetAddParameterComponent} from './dataset-add-parameter/dataset-add-parameter.component';
import {Subject} from 'rxjs';

@Component({
    selector: 'ki-dataset-parameter-values',
    templateUrl: './dataset-parameter-values.component.html',
    styleUrls: ['./dataset-parameter-values.component.sass'],
    standalone: false
})
export class DatasetParameterValuesComponent implements OnInit {

    @Input() parameterValues: any = [];
    @Input() focusParameters = false;
    @Input() evaluatedDatasource: any;
    @Input() showNewOnOpen: boolean;
    @Input() hideNew = false;
    @Input() update: Subject<any>;

    @Output() changed = new EventEmitter();
    @Output() evaluatedDatasourceChange = new EventEmitter();

    public _ = _;

    constructor(private dialog: MatDialog) {
    }

    ngOnInit(): void {
        if (this.showNewOnOpen && !this.parameterValues.length) {
            this.addParameter();
        }

        if (this.update) {
            this.update.subscribe(() => {
                this.parameterChange();
            });
        }
    }

    public parameterChange(data?) {
        if (!_.every(this.parameterValues, 'value')) {
            this.focusParameters = true;
        } else {
            this.focusParameters = false;
            this.changed.emit(this.parameterValues);
        }
    }

    public addParameter() {
        const dialogRef = this.dialog.open(DatasetAddParameterComponent, {
            width: '600px',
            height: '600px'
        });
        dialogRef.afterClosed().subscribe(parameter => {
            if (parameter) {
                this.parameterValues.push(parameter);
                this.evaluatedDatasource.parameters.push(parameter);
            }
        });
    }

}
