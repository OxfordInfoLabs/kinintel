import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';

@Component({
    selector: 'ki-job-tasks',
    templateUrl: './job-tasks.component.html',
    styleUrls: ['./job-tasks.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class JobTasksComponent implements OnInit {

    constructor(public dialogRef: MatDialogRef<JobTasksComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
    }

}
