import {Component, Input, OnInit} from '@angular/core';
import {ProjectService} from '../../services/project.service';
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {
    ProjectLinkSelectionComponent
} from '../project-settings/project-link-selection/project-link-selection.component';
import * as _ from 'lodash';
import {moveItemInArray, transferArrayItem} from '@angular/cdk/drag-drop';
import {Subscription} from 'rxjs';
import {DashboardService} from '../../services/dashboard.service';


@Component({
    selector: 'ki-project-settings',
    templateUrl: './project-settings.component.html',
    styleUrls: ['./project-settings.component.sass']
})
export class ProjectSettingsComponent implements OnInit {

    @Input() dashboardURL: string;
    @Input() queryURL: string;
    @Input() datasourceURLs: any;

    public projectSettings: any = {};
    public categories: any = [];
    public newShortcut: any = {};
    public showNewShortcut = false;
    public shortcuts: any = {};
    public Object = Object;
    public dashboards: any = [];
    public sharedDashboards: any = [];

    private activeProject: any;
    private activeProjectSub: Subscription;

    constructor(private projectService: ProjectService,
                private dialog: MatDialog,
                private dashboardService: DashboardService) {
    }

    async ngOnInit(): Promise<any> {
        this.activeProject = this.projectService.activeProject.getValue();

        this.dashboards = await this.dashboardService.getDashboards(
            '', '100', '0'
        ).toPromise();

        this.sharedDashboards = await this.dashboardService.getDashboards(
            '', '100', '0', null
        ).toPromise();

        this.activeProjectSub = this.projectService.activeProject.subscribe(activeProject => {
            this.activeProject = activeProject;

            this.projectSettings = this.activeProject.settings ? (Array.isArray(this.activeProject.settings) ? {
                hideExisting: false, shortcutPosition: 'after', homeDashboard: {}, shortcutsMenu: []
            } : this.activeProject.settings) : {
                hideExisting: false, shortcutPosition: 'after', homeDashboard: {}, shortcutsMenu: []
            };

            this.getCategories();
        });
    }

    public selectOption(c1: any, c2: any) {
        if (!c2) {
            return true;
        }
        return c1.value === c2.value;
    }

    public selectLink() {
        const dialogRef = this.dialog.open(ProjectLinkSelectionComponent, {
            width: '1200px',
            height: '800px',
            data: {
                dashboardURL: this.dashboardURL,
                queryURL: this.queryURL,
                datasourceURLs: this.datasourceURLs || []
            }
        });

        dialogRef.afterClosed().subscribe(res => {
            this.newShortcut.link = res;
        });
    }

    public saveNewShortcut() {
        if (this.newShortcut.newCategory) {
            this.newShortcut.category = this.newShortcut.newCategory;
        }

        if (!this.projectSettings.shortcutsMenu || !this.projectSettings.shortcutsMenu.length) {
            this.projectSettings.shortcutsMenu = [];
        }

        const existingCategory = _.find(this.projectSettings.shortcutsMenu, {category: this.newShortcut.category});
        if (existingCategory) {
            existingCategory.items.push(this.newShortcut);
        } else {
            this.projectSettings.shortcutsMenu.push({
                category: this.newShortcut.category,
                items: [this.newShortcut]
            });
        }

        this.cancelNew();
        this.getCategories();
    }

    public cancelNew() {
        this.newShortcut = {};
        this.showNewShortcut = false;
    }

    public removeShortcut(menus, index) {
        menus.splice(index, 1);
        this.getCategories();
    }

    public removeShortcutItem(items, index) {
        items.splice(index, 1);
    }

    public moveCategory(currentIndex, change) {
        const item = this.projectSettings.shortcutsMenu.splice(currentIndex, 1)[0];
        this.projectSettings.shortcutsMenu.splice(currentIndex + change, 0, item);
    }

    public drop(event) {
        if (event.previousContainer === event.container) {
            moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        } else {
            transferArrayItem(
                event.previousContainer.data,
                event.container.data,
                event.previousIndex,
                event.currentIndex,
            );
        }
    }

    public async saveChanges() {
        await this.projectService.updateProjectSettings(this.activeProject.projectKey, this.projectSettings);
    }

    private getCategories() {
        if (this.projectSettings.shortcutsMenu) {
            this.categories = this.projectSettings.shortcutsMenu.map(shortcut => {
                return shortcut.category;
            });
        }
    }
}
