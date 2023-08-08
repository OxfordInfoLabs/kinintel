import {Component, Inject, OnInit} from '@angular/core';
import {ActivatedRoute, Router} from '@angular/router';
import {NotificationService} from '../../../services/notification.service';
import {COMMA, ENTER} from '@angular/cdk/keycodes';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {UserService} from 'ng-kiniauth';
import {MatLegacyAutocompleteSelectedEvent as MatAutocompleteSelectedEvent} from '@angular/material/legacy-autocomplete';
import * as lodash from 'lodash';
const _ = lodash.default;
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';

@Component({
    selector: 'ki-edit-notification-group',
    templateUrl: './edit-notification-group.component.html',
    styleUrls: ['./edit-notification-group.component.sass']
})
export class EditNotificationGroupComponent implements OnInit {

    public notification: any = {communicationMethod: 'email'};
    public externalMembers: any = [];
    public users: any = [];
    public notificationGroupMembers: any = [];
    public testSent = false;
    public selectable = true;
    public removable = true;
    public searchText = new BehaviorSubject<string>('');
    public _ = _;

    public readonly separatorKeysCodes = [ENTER, COMMA] as const;

    private reset = new Subject();

    constructor(private notificationService: NotificationService,
                private route: ActivatedRoute,
                private router: Router,
                private userService: UserService,
                public dialogRef: MatDialogRef<EditNotificationGroupComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        const id = this.route.snapshot.params.id || this.data.groupId;
        this.notificationService.getNotificationGroup(id).then((notification: any) => {
            this.notification = notification || {communicationMethod: 'email'};
            this.notificationGroupMembers = this.notification.members || [];
        });

        merge(this.searchText, this.reset)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getUsers()
                )
            )
            .subscribe((users: any) => {
                this.users = users;
            });
    }

    public search(searchTerm: string) {
        this.searchText.next(searchTerm);
    }

    public userSelected(event: MatAutocompleteSelectedEvent, searchInput) {
        const user = event.option.value;
        this.notificationGroupMembers.push({
            user: {id: user.id, name: user.name},
        });

        searchInput.value = '';
        this.reset.next(Date.now());
    }

    public addExternalMember(event) {
        const value = (event.target.value || '').trim();

        if (value) {
            this.notificationGroupMembers.push({
                memberData: value
            });
        }

        // Clear the input value
        event.target.value = '';
    }

    public cancel() {
        if (!this.data) {
            this.router.navigate(['/notification-groups']);
        } else {
            this.dialogRef.close(false);
        }
    }

    public save() {
        this.notification.members = [];
        this.notificationGroupMembers.forEach(member => {
            this.notification.members.push({
                user: member.user,
                memberData: member.memberData
            });
        });
        this.notificationService.saveNotificationGroup(this.notification).then(() => {
            if (!this.data) {
                this.router.navigate(['/notification-groups']);
            } else {
                this.dialogRef.close(true);
            }
        });
    }

    private getUsers() {
        return this.userService.getAccountUsers(
            this.searchText.getValue(),
            '10',
            '0'
        ).pipe(map((data: any) => {
            return data.results;
        }));
    }
}
