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
import {ItemComponentComponent} from '../../dashboard-editor/item-component/item-component.component';
import {ActivatedRoute, Router} from '@angular/router';
import {DashboardService} from '../../../services/dashboard.service';
import * as _ from 'lodash';
import {MatDialog} from '@angular/material/dialog';
import {MatSnackBar} from '@angular/material/snack-bar';
import {AlertService} from '../../../services/alert.service';

@Component({
    selector: 'ki-view-dashboard',
    templateUrl: './view-dashboard.component.html',
    styleUrls: ['./view-dashboard.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class ViewDashboardComponent implements OnInit, AfterViewInit, OnDestroy {

    @ViewChild('viewContainer', {read: ViewContainerRef}) viewContainer: ViewContainerRef;

    @HostBinding('class.p-4') get t() {
        return this.dashboard.displaySettings && this.dashboard.displaySettings.fullScreen;
    }

    @Input() dashboardService: any;
    @Input() alertService: any;
    @Input() datasetService: any;
    @Input() datasourceService: any;
    @Input() editAlerts: boolean;
    @Input() sidenavService: any;
    @Input() dashboardId: number;
    @Input() gridOnly = false;

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


    constructor(private componentFactoryResolver: ComponentFactoryResolver,
                private applicationRef: ApplicationRef,
                private injector: Injector,
                private route: ActivatedRoute,
                private kiDashboardService: DashboardService,
                private dialog: MatDialog,
                private snackBar: MatSnackBar,
                private router: Router,
                private kiAlertService: AlertService) {
    }

    ngOnInit(): void {
        if (!this.dashboardService) {
            this.dashboardService = this.kiDashboardService;
        }

        if (!this.alertService) {
            this.alertService = this.kiAlertService;
        }


        this.route.queryParams.subscribe(params => {
            this.admin = !!params.a;
        });
    }

    ngAfterViewInit() {
        const options = {
            minRow: 1, // don't collapse when empty
            float: true,
            cellHeight: 20,
            minW: 768,
            disableOneColumnMode: false
        };
        this.grid = GridStack.init(options);
        this.grid.enableMove(false);
        this.grid.enableResize(false);

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
                    dashboardItemType, !!dashboardItemType);
            });
            if (this.dashboard.displaySettings.inset) {
                this.updateGridSpacing(this.dashboard.displaySettings.inset, false);
            }
        });

        const dashboardId = this.dashboardId || this.route.snapshot.params.dashboard;

        this.dashboardService.getDashboard(dashboardId).then(dashboard => {
            this.dashboard = dashboard;
            this.editDashboardTitle = !this.dashboard.title;
            if (this.dashboard.displaySettings) {
                this.darkMode = !!this.dashboard.displaySettings.darkMode;
                this.setDarkModeOnBody();
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

            if (this.sidenavService) {
                setTimeout(() => {
                    this.sidenavService.close();
                }, 0);
            }
        });
    }

    ngOnDestroy() {
        document.body.classList.remove('dark');
        if (this.sidenavService) {
            this.sidenavService.open();
        }
    }

    public updateGridSpacing(space, save = true) {
        this.dashboard.displaySettings.inset = space;
        document.querySelectorAll('.grid-stack-item-content')
            .forEach((el: any) => el.style.inset = space);
    }

    public setParameterValue(parameter, value) {
        parameter.value = value;
        this.grid.removeAll();
        this.grid.load(this.dashboard.layoutSettings.grid);
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

        element.firstChild.id = instanceId ? instanceId : 'i' + Date.now().toString();
        instanceId = element.firstChild.id;

        componentRef.instance.viewOnly = true;
        componentRef.instance.admin = this.admin;
        componentRef.instance.grid = this.grid;
        componentRef.instance.dashboard = this.dashboard;

        const chartDetails = this.dashboard.layoutSettings.charts ? this.dashboard.layoutSettings.charts[instanceId] : null;

        const dashboardDatasetInstance = _.find(this.dashboard.datasetInstances, {instanceKey: instanceId}) || null;
        componentRef.instance.dashboardDatasetInstance = dashboardDatasetInstance;
        componentRef.instance.dashboardItemType = chartDetails || (dashboardItemType || {});
        componentRef.instance.itemInstanceKey = instanceId;
        componentRef.instance.configureClass = !load;
        componentRef.instance.init();
        if (load) {
            if (this.dashboard.layoutSettings.dependencies) {
                const dependencies = this.dashboard.layoutSettings.dependencies[instanceId] || {};
                if (!dependencies.instanceKeys || !dependencies.instanceKeys.length) {
                    componentRef.instance.load();
                }
            } else {
                componentRef.instance.load();
            }
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

        element.classList.add('shadow');

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
