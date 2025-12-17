import {Component, OnInit} from '@angular/core';
import {DatasetService} from '../../services/dataset.service';
import {ActivatedRoute} from '@angular/router';
import {AuthenticationService} from 'ng-kiniauth';

@Component({
    selector: 'ki-data-sharing-invite',
    templateUrl: './data-sharing-invite.component.html',
    styleUrls: ['./data-sharing-invite.component.sass'],
    standalone: false
})
export class DataSharingInviteComponent implements OnInit {

    public inviteAccepted = false;
    public inviteCancelled = false;
    public invitationError = false;
    public invitationCode: string;
    public details: any = {};

    constructor(private datasetService: DatasetService,
                private route: ActivatedRoute,
                private authService: AuthenticationService, ) {

    }

    async ngOnInit() {
        await this.authService.getSessionData();

        this.invitationCode = this.route.snapshot.queryParams.invitationCode;

        try {
            this.details = await this.datasetService.getSharableItemForInvitation(this.invitationCode);
        } catch (e) {
            this.invitationError = true;
        }
    }


    public async acceptInvitation() {
        try {
            await this.datasetService.acceptSharingInvitation(this.invitationCode);
            this.inviteAccepted = true;
        } catch (e){
            this.invitationError = true;
        }
    }

    public async cancelInvitation() {
        try {
            await this.datasetService.cancelSharingInvitation(this.invitationCode);
            this.inviteCancelled = true;
        } catch (e){
            this.invitationError = true;
        }
    }

}
