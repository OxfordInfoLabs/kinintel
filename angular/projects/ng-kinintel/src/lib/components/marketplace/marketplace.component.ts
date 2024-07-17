import {Component, OnInit} from '@angular/core';
import {BehaviorSubject, merge, of, Subject} from 'rxjs';
import {debounceTime, switchMap} from 'rxjs/operators';
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {ListMarketplaceItemComponent} from './list-marketplace-item/list-marketplace-item.component';

@Component({
    selector: 'ki-marketplace',
    templateUrl: './marketplace.component.html',
    styleUrls: ['./marketplace.component.sass']
})
export class MarketplaceComponent implements OnInit {

    public datasets: any = [];
    public searchText = new BehaviorSubject('');
    public limit = 10;
    public offset = 0;
    public page = 1;
    public endOfResults = false;
    public loading = true;

    private reload = new Subject();

    constructor(private dialog: MatDialog) {
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

    public listNewItem() {
        const dialogRef = this.dialog.open(ListMarketplaceItemComponent, {
            width: '1000px',
            height: '800px',
            disableClose: true,
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
            {title: 'Scam Abuse Feed 2024', description: 'Scam feed detailing all the abuse reported in 2024.', account: 'Abuse Metric House', price: '849.00', icon: '/assets/logoipsum-248.svg'},
            {title: 'Phishing Feed Latest', description: 'The latest phishing feed from Phishing "R" Us.', account: 'Phishing "R" Us', requiresValidation: true, icon: '/assets/logoipsum-247.svg'},
            {title: 'Daily Malware Updates', description: 'All malware reported to The Malware Police on a daily basis.', account: 'The Malware Police', icon: '/assets/logoipsum-245.svg'},
            {title: 'Passive DNS Feed', description: 'A live feed of all passive DNS entries obtained by the Internet Defenders.', account: 'Internet Defenders', icon: '/assets/logoipsum-299.svg'},
        ]);
    }

}
