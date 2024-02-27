import {Component, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Observable, of, Subject} from 'rxjs';
import {debounceTime, switchMap} from 'rxjs/operators';

@Component({
    selector: 'ki-shared-with-me',
    templateUrl: './shared-with-me.component.html',
    styleUrls: ['./shared-with-me.component.sass']
})
export class SharedWithMeComponent implements OnInit {

    public datasets: any = [];
    public searchText = new BehaviorSubject('');
    public limit = 10;
    public offset = 0;
    public page = 1;
    public endOfResults = false;
    public loading = true;

    private reload = new Subject();

    constructor() {
    }

    ngOnInit() {
        merge(this.searchText, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasets()
                )
            ).subscribe((datasets: any) => {
            this.endOfResults = datasets.length < this.limit;
            this.datasets = datasets;
            this.loading = false;
        });

        this.searchText.subscribe(() => {
            this.page = 1;
            this.offset = 0;
        });
    }

    public increaseOffset() {
        this.page = this.page + 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.reload.next(Date.now());
    }

    public decreaseOffset() {
        this.page = this.page <= 1 ? 1 : this.page - 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.reload.next(Date.now());
    }

    public pageSizeChange(value) {
        this.page = 1;
        this.offset = 0;
        this.limit = value;
        this.reload.next(Date.now());
    }

    public getDatasets() {
        return of([
            {title: 'Scam Abuse Feed 2024', account: 'Abuse Metric House'},
            {title: 'Phishing Feed Latest', account: 'Phishing "R" Us'},
            {title: 'Daily Malware Updates', account: 'The Malware Police'},
        ]);
    }
}
