import {ChangeDetectorRef, Component, ViewChild} from '@angular/core';
import {MediaMatcher} from '@angular/cdk/layout';
import {MatSidenav} from '@angular/material/sidenav';
import {SidenavService} from './services/sidenav.service';
import {MatDialog} from '@angular/material/dialog';
import {ProjectPickerComponent} from 'ng-kinintel';

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.sass']
})
export class AppComponent {
    @ViewChild('snav', { static: false }) public snav: MatSidenav;

    public mobileQuery: MediaQueryList;
    public showFixedSidebar: boolean;

    private _mobileQueryListener: () => void;

    constructor(private changeDetectorRef: ChangeDetectorRef,
                private media: MediaMatcher,
                private sidenavService: SidenavService,
                private dialog: MatDialog) {

        this.mobileQuery = media.matchMedia('(max-width: 768px)');
        this._mobileQueryListener = () => changeDetectorRef.detectChanges();
        this.mobileQuery.addListener(this._mobileQueryListener);
    }

    ngOnInit() {
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
        this.mobileQuery.removeListener(this._mobileQueryListener);
    }

    public selectProject() {
        const dialogRef = this.dialog.open(ProjectPickerComponent, {
            width: '700px',
            height:  '500px',
            data: {

            }
        });
    }
}
