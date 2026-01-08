import {Component, Input, OnInit, Output, EventEmitter, ViewChild} from '@angular/core';
import * as lodash from 'lodash';
import {NgForm} from '@angular/forms';
const _ = lodash.default;

@Component({
    selector: 'ki-task-time-periods',
    templateUrl: './task-time-periods.component.html',
    styleUrls: ['./task-time-periods.component.sass'],
    standalone: false
})
export class TaskTimePeriodsComponent implements OnInit {

    @ViewChild('timeForm') timeForm: NgForm;

    @Input() taskTimePeriods: any = [];
    @Input() showTitle = true;

    @Output() taskTimePeriodsChange = new EventEmitter();

    public showNewTaskTimePeriod = false;
    public newTaskTimePeriod: any = {};
    public _ = _;
    public Object = Object;


    constructor() {
    }

    ngOnInit(): void {
    }

    public addScheduleTime() {
        this.showNewTaskTimePeriod = true;
    }

    public addTimePeriod() {
        this.taskTimePeriods.push(this.newTaskTimePeriod);
        this.showNewTaskTimePeriod = false;
        this.newTaskTimePeriod = {};
        this.taskTimePeriodsChange.next(this.taskTimePeriods);
    }

    public removeTime(index) {
        this.taskTimePeriods.splice(index, 1);
        if (!this.taskTimePeriods.length) {
            this.showNewTaskTimePeriod = true;
            this.newTaskTimePeriod = {};
        }
    }
}
