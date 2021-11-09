import {
    AfterViewInit, ApplicationRef,
    Component, ComponentFactoryResolver, EmbeddedViewRef, HostBinding, Injector, Input, OnDestroy,
    OnInit,
    ViewChild,
    ViewContainerRef,
    ViewEncapsulation
} from '@angular/core';
import 'gridstack/dist/gridstack.min.css';
import {GridStack, GridStackNode} from 'gridstack';
// THEN to get HTML5 drag&drop
import 'gridstack/dist/h5/gridstack-dd-native';
import {ItemComponentComponent} from './item-component/item-component.component';
import {ActivatedRoute, Router} from '@angular/router';
import {DashboardService} from '../../services/dashboard.service';
import * as _ from 'lodash';
import {DatasetAddParameterComponent} from '../dataset/dataset-editor/dataset-parameter-values/dataset-add-parameter/dataset-add-parameter.component';
import {MatDialog} from '@angular/material/dialog';
import {MatSnackBar} from '@angular/material/snack-bar';
import {AlertService} from '../../services/alert.service';

@Component({
    selector: 'ki-dashboard-editor',
    templateUrl: './dashboard-editor.component.html',
    styleUrls: ['./dashboard-editor.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class DashboardEditorComponent implements OnInit, AfterViewInit, OnDestroy {

    @ViewChild('viewContainer', {read: ViewContainerRef}) viewContainer: ViewContainerRef;

    @HostBinding('class.p-4') get t() {
        return this.dashboard.displaySettings && this.dashboard.displaySettings.fullScreen;
    }

    public itemTypes: any = [
        {
            type: 'line',
            label: 'Line Chart',
            icon: 'show_chart',
            width: 6,
            height: 4
        },
        {
            type: 'bar',
            label: 'Bar Chart',
            icon: 'stacked_bar_chart',
            width: 4,
            height: 3
        },
        {
            type: 'pie',
            label: 'Pie Chart',
            icon: 'pie_chart',
            width: 6,
            height: 5
        },
        {
            type: 'table',
            label: 'Table',
            icon: 'table_chart',
            width: 5,
            height: 7
        },
        {
            type: 'doughnut',
            label: 'Doughnut',
            icon: 'donut_large',
            width: 6,
            height: 5
        },
        {
            type: 'metric',
            label: 'Metric',
            icon: 'trending_up',
            width: 3,
            height: 2
        },
        {
            type: 'heading',
            label: 'Heading',
            icon: 'title',
            width: 4,
            height: 2
        },
        // {
        //     type: 'text',
        //     label: 'Text',
        //     icon: 'text_fields',
        //     width: 4,
        //     height: 4
        // },
        {
            type: 'image',
            label: 'Image',
            icon: 'image',
            width: 3,
            height: 5
        }
    ];
    public dashboard: any = {};
    public activeSidePanel: string = null;
    public _ = _;
    public editDashboardTitle = false;
    public darkMode = false;
    public fullScreen = false;
    public admin: boolean;
    public gridSpaces = [
        {
            label: 'Small',
            value: '2px'
        },
        {
            label: 'Medium',
            value: '5px'
        },
        {
            label: 'Large',
            value: '10px'
        }
    ];

    private grid: GridStack;
    private queryParams: any = {};

    public static myClone(event) {
        return event.target.cloneNode(true);
    }

    constructor(private componentFactoryResolver: ComponentFactoryResolver,
                private applicationRef: ApplicationRef,
                private injector: Injector,
                private route: ActivatedRoute,
                private dashboardService: DashboardService,
                private dialog: MatDialog,
                private snackBar: MatSnackBar,
                private router: Router,
                private alertService: AlertService) {
    }

    ngOnInit(): void {
        this.route.queryParams.subscribe(params => {
            const cloned = _.clone(params);
            this.admin = !!cloned.a;
            delete cloned.a;
            this.queryParams = cloned;
        });
    }

    ngAfterViewInit() {
        const options = {
            minRow: 1, // don't collapse when empty
            float: true,
            cellHeight: 50,
            minW: 1024,
            disableOneColumnMode: true,
            dragIn: '.draggable-toolbar .grid-stack-item', // add draggable to class
            dragInOptions: {
                revert: 'invalid',
                scroll: false,
                appendTo: 'body',
                helper: DashboardEditorComponent.myClone
            },
            acceptWidgets: (el) => {
                el.className += ' grid-stack-item';
                return true;
            }
        };
        this.grid = GridStack.init(options);

        this.grid.on('added', (event: Event, newItems: GridStackNode[]) => {
            newItems.forEach((item) => {
                let dashboardItemType = null;
                let itemElement: any = item.el.firstChild;
                let instanceId = null;

                if (item.content) {
                    itemElement = document.createRange().createContextualFragment(item.content);
                    dashboardItemType = itemElement.firstChild.dataset.dashboardItemType ?
                        JSON.parse(itemElement.firstChild.dataset.dashboardItemType) : null;

                    instanceId = itemElement.firstChild.id;
                }

                while (item.el.firstChild.firstChild) {
                    item.el.firstChild.firstChild.remove();
                }

                this.addComponentToGridItem(item.el.firstChild, instanceId,
                    dashboardItemType || this.itemTypes[item.el.dataset.index], !!dashboardItemType);
            });
            if (this.dashboard.displaySettings.inset) {
                this.updateGridSpacing(this.dashboard.displaySettings.inset, false);
            }
        });

        const dashboardId = this.route.snapshot.params.dashboard;

        this.dashboardService.getDashboard(dashboardId).then(dashboard => {
            this.dashboard = dashboard;

            // If we have any query params, check if they match any set out in the dashboard
            Object.keys(this.queryParams).forEach(key => {
                if (Object.keys(this.dashboard.layoutSettings.parameters).length) {
                    if (this.dashboard.layoutSettings.parameters[key]) {
                        this.dashboard.layoutSettings.parameters[key].value = this.queryParams[key];
                    }
                }

                this.dashboard.datasetInstances.forEach(instance => {
                    if (!_.values(instance.parameterValues).length) {
                        instance.parameterValues = {};
                    }
                    instance.parameterValues[key] = this.queryParams[key];
                });
            });

            this.editDashboardTitle = !this.dashboard.title;
            if (this.dashboard.displaySettings.length || Object.keys(this.dashboard.displaySettings).length) {
                this.darkMode = !!this.dashboard.displaySettings.darkMode;
                this.setDarkModeOnBody();
                if (this.dashboard.displaySettings.fullScreen) {
                    this.openFullScreen();
                    this.fullScreen = true;
                }
            } else {
                this.dashboard.displaySettings = {};
            }
            if (this.dashboard.layoutSettings) {
                if (this.dashboard.layoutSettings.grid && this.dashboard.layoutSettings.grid.length) {
                    this.grid.load(this.dashboard.layoutSettings.grid);
                }
            } else {
                this.dashboard.layoutSettings = {};
            }
        });

    }

    ngOnDestroy() {
        document.body.classList.remove('dark');
    }

    public openFullScreen() {
        this.dashboard.displaySettings.fullScreen = true;
        this.save(false).then(() => {
            this.router.navigate(['dashboards', this.dashboard.id, 'full']);
        });
    }

    public backToEditor() {
        this.dashboard.displaySettings.fullScreen = false;
        this.save(false).then(() => {
            this.router.navigate(['dashboards', this.dashboard.id]);
        });
    }

    public toggleDarkMode() {
        this.dashboard.displaySettings.darkMode = this.darkMode = !this.darkMode;
        this.setDarkModeOnBody();
        this.save(false);
    }

    public updateGridSpacing(space, save = true) {
        this.dashboard.displaySettings.inset = space;
        document.querySelectorAll('.grid-stack-item-content')
            .forEach((el: any) => el.style.inset = space);
        if (save) {
            this.save(false);
        }
    }

    public toggleNotifications() {
        this.dashboard.alertsEnabled = !this.dashboard.alertsEnabled;
        this.save(false);
        this.grid.removeAll();
        this.grid.load(this.dashboard.layoutSettings.grid);
    }

    public addParameter() {
        const dialogRef = this.dialog.open(DatasetAddParameterComponent, {
            width: '600px',
            height: '600px'
        });
        dialogRef.afterClosed().subscribe(parameter => {
            if (parameter) {
                if (!this.dashboard.layoutSettings) {
                    this.dashboard.layoutSettings = {};
                }
                if (!this.dashboard.layoutSettings.parameters) {
                    this.dashboard.layoutSettings.parameters = {};
                }

                parameter.value = parameter.defaultValue || '';
                this.dashboard.layoutSettings.parameters[parameter.name] = parameter;
            }
            this.dashboardService.saveDashboard(this.dashboard);
        });
    }

    public removeParameter(parameter) {
        const message = 'Are you sure you would like to remove this parameter. This may cause some dashboard items ' +
            'to fail.';
        if (window.confirm(message)) {
            delete this.dashboard.layoutSettings.parameters[parameter.name];
            this.dashboardService.saveDashboard(this.dashboard);
        }
    }

    public setParameterValue() {
        const parameters = _.values(this.dashboard.layoutSettings.parameters);
        parameters.forEach(parameter => {
            this.dashboard.datasetInstances.forEach(instance => {
                if (!_.values(instance.parameterValues).length) {
                    instance.parameterValues = {};
                }
                instance.parameterValues[parameter.name] = parameter.value;
            });
        });

        this.grid.removeAll();
        this.grid.load(this.dashboard.layoutSettings.grid);
    }

    public save(showSaved = true) {
        this.dashboard.layoutSettings.grid = this.grid.save(true);
        console.log(this.dashboard);
        return this.dashboardService.saveDashboard(this.dashboard).then((dashboardId) => {
            if (showSaved) {
                this.snackBar.open('Dashboard successfully saved.', 'Close', {
                    verticalPosition: 'top',
                    duration: 3000
                });
            }
            if (!this.dashboard.id) {
                this.dashboard.id = dashboardId;
                this.router.navigate([`/dashboards/${dashboardId}${this.admin ? '?a=true' : ''}`]);
            }
            return this.dashboard;
        });
    }

    private addComponentToGridItem(element, instanceId?, dashboardItemType?, load?) {
        if (!this.dashboard.layoutSettings) {
            this.dashboard.layoutSettings = {};
        }
        if (!this.dashboard.displaySettings) {
            this.dashboard.displaySettings = {};
        }
        // create a component reference
        const componentRef = this.componentFactoryResolver.resolveComponentFactory(ItemComponentComponent)
            .create(this.injector);

        // get DOM element from component
        const domElem = (componentRef.hostView as EmbeddedViewRef<any>)
            .rootNodes[0] as HTMLElement;

        domElem.dataset.dashboardItemType = JSON.stringify(dashboardItemType || {});

        element.appendChild(domElem);

        element.firstChild.id = instanceId ? instanceId : Date.now().toString();
        instanceId = element.firstChild.id;

        componentRef.instance.admin = this.admin;
        componentRef.instance.grid = this.grid;
        componentRef.instance.dashboard = this.dashboard;

        const chartDetails = this.dashboard.layoutSettings.charts ? this.dashboard.layoutSettings.charts[instanceId] : null;

        const dashboardDatasetInstance = _.find(this.dashboard.datasetInstances, {instanceKey: instanceId}) || null;
        componentRef.instance.dashboardDatasetInstance = dashboardDatasetInstance;
        componentRef.instance.dashboardItemType = chartDetails || (dashboardItemType || {});
        componentRef.instance.itemInstanceKey = instanceId;
        componentRef.instance.configureClass = !load;
        if (load) {
            componentRef.instance.load();
        }

        if (this.dashboard.alertsEnabled) {
            if (dashboardDatasetInstance && dashboardDatasetInstance.alerts && dashboardDatasetInstance.alerts.length) {
                this.alertService.processAlertsForDashboardDatasetInstance(dashboardDatasetInstance)
                    .then((res: any) => {
                        if (res && res.length) {
                            componentRef.instance.alert = true;
                            componentRef.instance.alertData = res;
                            const itemElement = document.getElementById(instanceId);
                            itemElement.classList.add('alert');
                            itemElement.parentElement.classList.add('alert');
                        }
                    });
            }
        }

        // attach component to the appRef so that so that it will be dirty checked.
        this.applicationRef.attachView(componentRef.hostView);

        return componentRef;
    }

    private setDarkModeOnBody() {
        if (this.darkMode) {
            document.body.classList.add('dark');
        } else {
            document.body.classList.remove('dark');
        }
    }

}

