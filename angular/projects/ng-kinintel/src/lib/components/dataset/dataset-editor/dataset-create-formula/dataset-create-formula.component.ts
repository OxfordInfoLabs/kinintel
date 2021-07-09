import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import * as _ from 'lodash';

@Component({
    selector: 'ki-dataset-create-formula',
    templateUrl: './dataset-create-formula.component.html',
    styleUrls: ['./dataset-create-formula.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class DatasetCreateFormulaComponent implements OnInit {

    public formulas: any = [{}];
    public allColumns: any = [];
    public _ = _;

    constructor(public dialogRef: MatDialogRef<DatasetCreateFormulaComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        this.allColumns = this.data.allColumns;
        this.formulas = this.data.formulas || [{}];
        this.formulas.map(formula => {
            if (formula.expression) {
                formula.expression.match(/\[\[(.*?)\]\]/g).forEach(exp => {
                    const name = _.find(this.allColumns, column => {
                        return `[[${column.name}]]` === exp;
                    });
                    formula.expression = formula.expression.replace(exp, name ? `[[${name.title}]]` : '');
                });
            }

            return formula;
        });
    }

    public addFormula() {
        this.formulas.push({});
        setTimeout(() => {
            const formulaScroll = document.getElementById('formula-scroll');
            formulaScroll.scrollTop = formulaScroll.scrollHeight;
        }, 0);
    }

    public removeFormula(i) {
        this.formulas.splice(i, 1);
    }

    public createFormula() {
        this.formulas.map(formula => {
            formula.expression.match(/\[\[(.*?)\]\]/g).forEach(exp => {
                const name = _.find(this.allColumns, column => {
                    return `[[${column.title}]]` === exp;
                });
                formula.expression = formula.expression.replace(exp, name ? `[[${name.name}]]` : '');
            });
            return formula;
        });

        this.dialogRef.close(this.formulas);
    }

}
