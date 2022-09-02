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
import * as lodash from 'lodash';
const _ = lodash.default;
import {MatDialog} from '@angular/material/dialog';
import {MatSnackBar} from '@angular/material/snack-bar';
import {AlertService} from '../../../services/alert.service';
import moment_ from 'moment';

@Component({
    selector: 'ki-view-dashboard',
    templateUrl: './view-dashboard.component.html',
    styleUrls: ['./view-dashboard.component.sass'],
    encapsulation: ViewEncapsulation.None,
    host: {
        class: 'block absolute inset-0 bg-gray-50'
    }
})
export class ViewDashboardComponent implements OnInit, AfterViewInit, OnDestroy {

    @ViewChild('viewContainer', {read: ViewContainerRef}) viewContainer: ViewContainerRef;

    @HostBinding('class.p-4') get t() {
        return this.dashboard.displaySettings && this.dashboard.displaySettings.fullScreen;
    }
    @HostBinding('class.dark-mode-dashboard') darkMode = false;

    @Input() dashboardService: any;
    @Input() alertService: any;
    @Input() datasetService: any;
    @Input() datasourceService: any;
    @Input() editAlerts: boolean;
    @Input() sidenavService: any;
    @Input() dashboardId: number;
    @Input() gridOnly = false;
    @Input() parameters: any;

    public dashboard: any = {};
    public activeSidePanel: string = null;
    public _ = _;
    public editDashboardTitle = false;
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
    public refreshInterval = 0;
    public hideParameters = false;
    public fullOnly = false;

    private grid: GridStack;
    private queryParams: any = {};

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
        this.fullOnly = this.route.snapshot.data.full;
        if (this.fullOnly) {
            this.darkMode = localStorage.getItem('fullDashboardDarkMode') === 'true';
            this.refreshInterval = Number(localStorage.getItem('fullDashboardRefreshInterval') || 0);
            this.hideParameters = localStorage.getItem('fullDashboardHideParams') === 'true';
        }

        if (this.refreshInterval) {
            let count = 0;
            setInterval(() => {
                if (count > 10) {
                    window.location.reload();
                } else {
                    this.reload();
                    count++;
                }
            }, (this.refreshInterval * 1000));
        }

        if (!this.dashboardService) {
            this.dashboardService = this.kiDashboardService;
        }

        if (!this.alertService) {
            this.alertService = this.kiAlertService;
        }

        if (this.parameters) {
            this.queryParams = this.parameters;
        }

        this.route.queryParams.subscribe(params => {
            const cloned = _.clone(params);
            this.admin = !!cloned.a;
            delete cloned.a;
            this.queryParams = cloned;
        });
    }

    async ngAfterViewInit() {
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

        this.dashboard = await this.dashboardService.getDashboard(dashboardId);

        Object.keys(this.queryParams).forEach(key => {
            if (Object.keys(this.dashboard.layoutSettings.parameters).length) {
                if (this.dashboard.layoutSettings.parameters[key]) {
                    if (this.dashboard.layoutSettings.parameters[key].type === 'date' ||
                        this.dashboard.layoutSettings.parameters[key].type === 'datetime') {
                        this.queryParams[key] = moment(this.queryParams[key]).format('YYYY-MM-DDTHH:mm');
                    }
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

        if (!this.dashboard.displaySettings) {
            this.dashboard.displaySettings = {};
        }

        if (this.dashboard.layoutSettings) {
            if (this.dashboard.layoutSettings.grid && this.dashboard.layoutSettings.grid.length) {
                this.grid.load(this.dashboard.layoutSettings.grid);
            }
        } else {
            this.dashboard.layoutSettings = {};
        }

        if (this.sidenavService && !this.fullOnly) {
            setTimeout(() => {
                this.sidenavService.close();
            }, 0);
        }
    }

    ngOnDestroy() {
        document.body.classList.remove('dark');
        if (this.sidenavService) {
            this.sidenavService.open();
        }
    }

    public booleanUpdate(event, parameter) {
        parameter.value = event.checked;
        this.grid.removeAll();
        this.grid.load(this.dashboard.layoutSettings.grid);
    }

    public changeDateType(event, parameter, value) {
        event.stopPropagation();
        event.preventDefault();
        parameter._dateType = value;
    }

    public updatePeriodValue(value, period, parameter) {
        parameter.value = `${value}_${period}_AGO`;
        this.grid.removeAll();
        this.grid.load(this.dashboard.layoutSettings.grid);
    }

    public editNotifications() {
        this.editAlerts = !this.editAlerts;
        this.grid.removeAll();
        this.grid.load(this.dashboard.layoutSettings.grid);
    }

    public viewFullscreen() {
        const dashboardId = this.dashboardId || this.route.snapshot.params.dashboard;
        window.location.href = `/dashboards/${dashboardId}/full${this.admin ? '?a=true&' : '?'}`;
    }

    public toggleDarkMode() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('fullDashboardDarkMode', String(this.darkMode));
    }

    public setRefreshInterval(intervalSeconds) {
        if (intervalSeconds) {
            localStorage.setItem('fullDashboardRefreshInterval', String(intervalSeconds));
        } else {
            localStorage.removeItem('fullDashboardRefreshInterval');
        }
        window.location.reload();
    }

    public toggleParameters() {
        this.hideParameters = !this.hideParameters;
        localStorage.setItem('fullDashboardHideParams', String(this.hideParameters));
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
        componentRef.instance.editAlerts = this.editAlerts;

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

        // attach component to the appRef so that it will be dirty checked.
        this.applicationRef.attachView(componentRef.hostView);

        const generalSettings = this.dashboard.layoutSettings.general ? this.dashboard.layoutSettings.general[instanceId] : null;
        if (generalSettings && generalSettings.transparent) {
            element.classList.add('bg-transparent');
            element.classList.remove('bg-white', 'shadow');
        } else {
            element.classList.add('bg-white', 'shadow');
            element.classList.remove('bg-transparent');
        }

        return componentRef;
    }

    private setDarkModeOnBody() {
        if (this.darkMode) {
            document.body.classList.add('dark');
        } else {
            document.body.classList.remove('dark');
        }
    }

    private reload() {
        this.grid.removeAll();
        this.grid.load(this.dashboard.layoutSettings.grid);
    }

}
