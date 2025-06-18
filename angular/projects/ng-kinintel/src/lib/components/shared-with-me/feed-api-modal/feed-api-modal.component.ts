import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialog as MatDialog,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import {HttpClient} from '@angular/common/http';
import {MatSnackBar} from '@angular/material/snack-bar';

@Component({
    selector: 'ki-feed-api-modal',
    templateUrl: './feed-api-modal.component.html',
    styleUrls: ['./feed-api-modal.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class FeedApiModalComponent implements OnInit {

    public feed: any;
    public apiKeys: any;
    public feedUrl: string;

    constructor(public dialogRef: MatDialogRef<FeedApiModalComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dialog: MatDialog,
                private http: HttpClient,
                private snackBar: MatSnackBar) {
    }

    async ngOnInit() {
        this.feed = this.data.feed;
        this.feedUrl = this.data.feedUrl;

        this.apiKeys = await this.http.get('/account/apikey/first/feedaccess').toPromise();
    }

    public async copy(text: string) {
        await navigator.clipboard.writeText(text.trim());
        this.copied();
    }

    public copied() {
        this.snackBar.open('Copied to Clipboard', null, {
            duration: 2000,
            verticalPosition: 'top',
            panelClass: 'bg-white'
        });
    }

}
