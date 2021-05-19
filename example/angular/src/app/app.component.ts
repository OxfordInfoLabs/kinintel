import {AfterViewInit, ChangeDetectorRef, Component, OnDestroy, OnInit, ViewChild} from '@angular/core';
import {MediaMatcher} from '@angular/cdk/layout';
import {MatSidenav} from '@angular/material/sidenav';
import {SidenavService} from './services/sidenav.service';
import {MatDialog} from '@angular/material/dialog';
import {ProjectPickerComponent} from 'ng-kinintel';
import {ProjectService} from './services/project.service';
import {Subscription} from 'rxjs';

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.sass']
})
export class AppComponent implements OnInit, AfterViewInit, OnDestroy {
    @ViewChild('snav', { static: false }) public snav: MatSidenav;

    public mobileQuery: MediaQueryList;
    public showFixedSidebar: boolean;
    public activeProject: any;

    private readonly mobileQueryListener: () => void;
    private projectSub: Subscription;

    constructor(private changeDetectorRef: ChangeDetectorRef,
                private media: MediaMatcher,
                private sidenavService: SidenavService,
                private dialog: MatDialog,
                private projectService: ProjectService) {

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
    }

    public selectProject(disableClose = false) {
        const dialogRef = this.dialog.open(ProjectPickerComponent, {
            width: '700px',
            height:  '500px',
            disableClose,
            data: {
                projectService: this.projectService
            }
        });
    }
}
