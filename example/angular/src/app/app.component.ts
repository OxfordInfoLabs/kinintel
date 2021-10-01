import {AfterViewInit, ChangeDetectorRef, Component, OnDestroy, OnInit, ViewChild} from '@angular/core';
import {MediaMatcher} from '@angular/cdk/layout';
import {MatSidenav} from '@angular/material/sidenav';
import {SidenavService} from './services/sidenav.service';
import {MatDialog} from '@angular/material/dialog';
import {ProjectPickerComponent, ProjectService, TagPickerComponent, TagService} from 'ng-kinintel';
import {Subscription} from 'rxjs';
import {environment} from '../environments/environment';
import {AuthenticationService, NotificationService} from 'ng-kiniauth';
import {Router} from '@angular/router';

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.sass']
})
export class AppComponent implements OnInit, AfterViewInit, OnDestroy {
    @ViewChild('snav', {static: false}) public snav: MatSidenav;

    public mobileQuery: MediaQueryList;
    public showFixedSidebar: boolean;
    public activeProject: any;
    public activeTag: any;
    public environment = environment;
    public loggedIn = false;
    public sessionUser: any = {};
    public isLoading: boolean;
    public notificationCount = 0;
    public notifications: any = [];
    public settingsOpen = false;

    private readonly mobileQueryListener: () => void;
    private projectSub: Subscription;
    private tagSub: Subscription;
    private authSub: Subscription;
    private loadingSub: Subscription;
    private notificationSub: Subscription;

    constructor(private changeDetectorRef: ChangeDetectorRef,
                private media: MediaMatcher,
                private sidenavService: SidenavService,
                private dialog: MatDialog,
                private projectService: ProjectService,
                private tagService: TagService,
                private authService: AuthenticationService,
                private router: Router,
                private notificationService: NotificationService) {

        this.mobileQuery = media.matchMedia('(max-width: 768px)');
        this.mobileQueryListener = () => changeDetectorRef.detectChanges();
        this.mobileQuery.addListener(this.mobileQueryListener);
    }

    ngOnInit() {
        this.projectSub = this.projectService.activeProject.subscribe(project => {
            this.activeProject = project;
            if (!project) {
                this.selectProject(true);
            }
        });
        this.tagSub = this.tagService.activeTag.subscribe(tag => {
            this.activeTag = tag;
        });
        this.authSub = this.authService.authUser.subscribe(user => {
            this.loggedIn = !!user;
            if (this.loggedIn) {
                this.sessionUser = this.authService.sessionData.getValue().user;
            }
        });
        this.loadingSub = this.authService.loadingRequests.subscribe(isLoading =>
            setTimeout(() => this.isLoading = isLoading, 0)
        );
        this.notificationService.getUnreadNotificationCount();
        this.notificationSub = this.notificationService.notificationCount
            .subscribe(count => {
                this.notificationCount = count;
                this.notificationService.getUserNotifications(this.activeProject.key, '5', '0').then(notifications => {
                    this.notifications = notifications;
                });
            });
    }

    ngAfterViewInit() {
        setTimeout(() => {
            this.showFixedSidebar = this.mobileQuery.matches;
        }, 0);

        this.sidenavService.setSidenav(this.snav);
        this.snav.closedStart.subscribe(opened => {
            this.showFixedSidebar = true;
        });
        this.snav.openedStart.subscribe(() => {
            this.showFixedSidebar = false;
        });
    }

    ngOnDestroy() {
        this.mobileQuery.removeListener(this.mobileQueryListener);
        this.projectSub.unsubscribe();
        this.authSub.unsubscribe();
        this.tagSub.unsubscribe();
    }

    public selectProject(disableClose = false) {
        const dialogRef = this.dialog.open(ProjectPickerComponent, {
            width: '700px',
            height: '500px',
            disableClose,
            data: {
                projectService: this.projectService
            }
        });
    }

    public selectTag() {
        const dialogRef = this.dialog.open(TagPickerComponent, {
            width: '700px',
            height: '500px',
            data: {
                tagService: this.tagService
            }
        });
    }

    public removeActiveTag() {
        this.tagService.resetActiveTag();
    }

    public logout() {
        this.authService.logout().then(() => {
            this.router.navigate(['/login']);
        });
    }
}
