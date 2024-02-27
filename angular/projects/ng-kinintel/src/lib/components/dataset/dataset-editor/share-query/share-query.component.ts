import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import {AccountService, AuthenticationService} from 'ng-kiniauth';
import {MatAutocompleteSelectedEvent} from '@angular/material/autocomplete';

@Component({
    selector: 'ki-share-query',
    templateUrl: './share-query.component.html',
    styleUrls: ['./share-query.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class ShareQueryComponent implements OnInit {

    public shareWithAccount = false;
    public shareOtherAccounts = false;
    public otherSharedAccounts: any = [];
    public listOnMarketplace = false;
    public marketplaceData: any = {};
    public session: any;
    public accounts: any = [
        {name: 'Data Share Account'},
        {name: 'Scam Data'},
        {name: 'Design Consultants'},
    ];

    constructor(public dialogRef: MatDialogRef<ShareQueryComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private authService: AuthenticationService,
                private accountService: AccountService) {
    }

    async ngOnInit() {
        this.session = await this.authService.getSessionData();
        // this.accounts = await this.accountService.searchForAccounts().toPromise();
    }

    public selectAccount(event: MatAutocompleteSelectedEvent) {
        this.otherSharedAccounts.push({name: event.option.value});
    }
}
