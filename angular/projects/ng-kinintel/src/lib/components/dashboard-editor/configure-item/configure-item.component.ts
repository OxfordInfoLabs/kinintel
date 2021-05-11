import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';

@Component({
    selector: 'ki-configure-item',
    templateUrl: './configure-item.component.html',
    styleUrls: ['./configure-item.component.sass'],
    host: {class: 'configure-dialog'}
})
export class ConfigureItemComponent implements OnInit {

    public chartData;


    constructor(public dialogRef: MatDialogRef<ConfigureItemComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        this.chartData = [
            {data: [1000, 1400, 1999, 2500, 5000]},
        ];

    }


}
