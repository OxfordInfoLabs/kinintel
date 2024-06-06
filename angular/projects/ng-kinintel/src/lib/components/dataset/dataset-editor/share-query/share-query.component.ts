import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import {AccountService, AuthenticationService} from 'ng-kiniauth';
import {MatAutocompleteSelectedEvent} from '@angular/material/autocomplete';
import {DatasetService} from '../../../../services/dataset.service';
import moment from "moment";
import * as lodash from 'lodash';

const _ = lodash.default;

@Component({
    selector: 'ki-share-query',
    templateUrl: './share-query.component.html',
    styleUrls: ['./share-query.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class ShareQueryComponent implements OnInit {

    public shareWithAccount = false;
    public sharedAccounts: any = [];
    public invitedAccounts: any = [];
    public session: any;
    public accounts: any;
    public accountSearch: string = '';
    public enterSharingIdentifier: boolean = false;
    public sharingIdentifier: string = '';
    public sharingIdentifierError: boolean = false;
    public selectedAccount: any;
    public selectedExpiry: string;
    public moment: any = moment;
    public marketplaceData: any = {};
    public listOnMarketplace = false;
    protected readonly String = String;

    constructor(public dialogRef: MatDialogRef<ShareQueryComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private authService: AuthenticationService,
                private accountService: AccountService,
                private datasetService: DatasetService) {
    }

    async ngOnInit() {
        this.session = await this.authService.getSessionData();
        this.loadSharedAccounts();
        this.loadInvitedAccounts();
    }


    public async loadSharedAccounts() {
        this.sharedAccounts = await this.datasetService.getSharedAccessGroupsForDatasetInstance(this.data.datasetInstance.id);
        this.shareWithAccount = _.some(this.sharedAccounts, sharedAccount => {
            return _.find(sharedAccount.scopeAccesses, {itemIdentifier: String(this.session.account.accountId)});
        });
    }


    public async loadInvitedAccounts() {
        this.invitedAccounts = await this.datasetService.getInvitedAccessGroupsForDatasetInstance(this.data.datasetInstance.id);
    }


    public async filterSharableAccounts() {
        this.accounts = await this.accountService.searchForDiscoverableAccounts(this.accountSearch);
    }


    public async setLoggedInAccountShareStatus(event) {
        await this.datasetService.setSharedAccessForDatasetInstanceForLoggedInAccount(this.data.datasetInstance.id, event.checked);
        this.loadSharedAccounts();
    }


    public async lookupSharingIdentifier() {

        try {
            let account: any = await this.accountService.lookupDiscoverableAccountByExternalIdentifier(this.sharingIdentifier);
            this.selectAccount(account);
        } catch (e) {
            this.sharingIdentifierError = true;
        }
    }


    // Select account, and show additional options.
    public selectAccount(account: any) {

        this.accountSearch = '';
        this.sharingIdentifierError = false;
        this.sharingIdentifier = '';

        this.selectedAccount = account;

    }

    // Invite the selected account to share with the current query.
    public async inviteSelectedAccount() {
        await this.datasetService.inviteAccountToShareDatasetInstance(this.data.datasetInstance.id, this.selectedAccount.externalIdentifier, this.selectedExpiry);
        this.selectedAccount = null;
        this.loadInvitedAccounts();
    }


    // Revoke access to a group
    public async revokeAccessToGroup(accessGroup) {
        const message = 'Are you sure you would like to revoke access to this account?';
        if (window.confirm(message)) {
            await this.datasetService.revokeAccessToGroupForDatasetInstance(this.data.datasetInstance.id, accessGroup);
            this.loadSharedAccounts();
        }
    }

    public async cancelInvitation(accessGroup) {
        const message = 'Are you sure you would like to cancel this invitation?';
        if (window.confirm(message)) {
            await this.datasetService.cancelInvitationForAccessGroupForDatasetInstance(this.data.datasetInstance.id, accessGroup);
            this.loadInvitedAccounts();
        }
    }



}
