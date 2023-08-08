import {Component, Inject, OnInit} from '@angular/core';
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';

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
