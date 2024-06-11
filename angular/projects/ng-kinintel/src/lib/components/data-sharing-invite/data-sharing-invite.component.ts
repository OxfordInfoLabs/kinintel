import {Component, OnInit} from '@angular/core';
import {DatasetService} from "../../services/dataset.service";
import {ActivatedRoute} from "@angular/router";

@Component({
    selector: 'ki-data-sharing-invite',
    templateUrl: './data-sharing-invite.component.html',
    styleUrls: ['./data-sharing-invite.component.sass']
})
export class DataSharingInviteComponent {

    public inviteAccepted = false;
    public inviteCancelled = false;
    public invitationError = false;
    public invitationCode: string;
    public details: any = {};

    constructor(private datasetService: DatasetService,
                private route: ActivatedRoute) {

    }

    async ngOnInit() {

        this.invitationCode = this.route.snapshot.queryParams.invitationCode;

        try {
            this.details = await this.datasetService.getSharableItemForInvitation(this.invitationCode)
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
