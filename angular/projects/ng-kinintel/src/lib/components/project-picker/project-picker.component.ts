import {Component, OnInit} from '@angular/core';

@Component({
    selector: 'ki-project-picker',
    templateUrl: './project-picker.component.html',
    styleUrls: ['./project-picker.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class ProjectPickerComponent implements OnInit {

    constructor() {
    }

    ngOnInit(): void {
    }

}
