import {
    AfterViewInit, ApplicationRef,
    Component, ComponentFactoryResolver, EmbeddedViewRef, HostBinding, Injector, Input, OnDestroy,
    OnInit,
    ViewChild,
    ViewContainerRef,
    ViewEncapsulation
} from '@angular/core';
import {GridStack, GridStackNode} from 'gridstack';
// THEN to get HTML5 drag&drop
import 'gridstack/dist/h5/gridstack-dd-native';
import {ItemComponentComponent} from '../../dashboard-editor/item-component/item-component.component';
import {ActivatedRoute, Router} from '@angular/router';
import {DashboardService} from '../../../services/dashboard.service';
import * as lodash from 'lodash';
const _ = lodash.default;
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {MatLegacySnackBar as MatSnackBar} from '@angular/material/legacy-snack-bar';
import {AlertService} from '../../../services/alert.service';
import moment from 'moment';
import {Location} from '@angular/common';
import {ExternalService} from '../../../services/external.service';
import {Subject, Subscription} from 'rxjs';
import {DatasetService} from '../../../services/dataset.service';

@Component({
    selector: 'ki-view-dashboard',
    templateUrl: './view-dashboard.component.html',
    styleUrls: ['./view-dashboard.component.sass'],
    encapsulation: ViewEncapsulation.None,
    standalone: false
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
    @Input() hideToolbar = false;
    @Input() actionEvents: any = [];
    @Input() external = false;
    @Input() reload: Subject<any>;
    @Input() cssGridSelector = 'view-grid-stack';

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
    private itemComponents: ItemComponentComponent[] = [];
    private reloadSub: Subscription;

    constructor(private componentFactoryResolver: ComponentFactoryResolver,
                private applicationRef: ApplicationRef,
                private injector: Injector,
                private route: ActivatedRoute,
                private kiDashboardService: DashboardService,
                private dialog: MatDialog,
                private snackBar: MatSnackBar,
                private router: Router,
                private kiAlertService: AlertService,
                private location: Location,
                private externalService: ExternalService,
                private kiDatasetService: DatasetService) {
    }

    ngOnInit(): void {
        this.fullOnly = this.route.snapshot.data.full;
        if (this.fullOnly) {
            this.darkMode = localStorage.getItem('fullDashboardDarkMode') === 'true';
            this.refreshInterval = Number(localStorage.getItem('fullDashboardRefreshInterval') || 0);
            this.hideParameters = localStorage.getItem('fullDashboardHideParams') === 'true';
        }

        if (!this.dashboardService) {
            this.dashboardService = this.kiDashboardService;
        }

        if (!this.alertService) {
            this.alertService = this.kiAlertService;
        }

        if (!this.datasetService) {
            this.datasetService = this.kiDatasetService;
        }

        this.route.queryParams.subscribe(params => {
            const cloned = _.clone(params);
            this.admin = !!cloned.a;
            delete cloned.a;
            this.queryParams = cloned;
        });

        if (this.parameters) {
            _.forEach(this.parameters, (value, key) => {
                if (!this.queryParams[key]) {
                    this.queryParams[key] = value;
                }
            });
        }

        if (this.reload) {
            this.reloadSub = this.reload.subscribe(res => {
                if (this.parameters) {
                    _.forEach(this.parameters, (value, key) => {
                        this.queryParams[key] = value;
                    });

                    Object.keys(this.queryParams).forEach(key => {
                        if (Object.keys(this.dashboard.layoutSettings.parameters || {}).length) {
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
                }
                this.reloadDashboard();
            });
        }
    }

    async ngAfterViewInit() {
        const options = {
            minRow: 1, // don't collapse when empty
            float: true,
            cellHeight: 20,
            minW: 768,
            disableOneColumnMode: false
        };
        this.grid = GridStack.init(options, '.' + this.cssGridSelector);
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

        if (this.external) {
            this.dashboard = await this.externalService.getDashboard(dashboardId, this.queryParams);

            this.darkMode = this.dashboard.externalSettings.darkMode;
            this.refreshInterval = this.dashboard.externalSettings.refreshInterval;
            this.hideParameters = !this.dashboard.externalSettings.showParameters;

        } else {
            this.dashboard = await this.dashboardService.getDashboard(dashboardId);
        }

        if (this.dashboard.layoutSettings?.parameters && Object.keys(this.dashboard.layoutSettings.parameters).length) {
            _.forEach(this.dashboard.layoutSettings.parameters, param => {
                if (param.type === 'list') {
                    param.list = [];
                    this.loadListParameters(param);
                }
            });
        }


        if (this.refreshInterval) {
            let count = 0;
            setInterval(() => {
                if (count > 10) {
                    window.location.reload();
                } else {
                    this.reloadDashboard();
                    count++;
                }
            }, (this.refreshInterval * 1000));
        }

        Object.keys(this.queryParams).forEach(key => {
            if (Object.keys(this.dashboard.layoutSettings.parameters || {}).length) {
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

        if (Object.keys(this.dashboard.layoutSettings.parameters || {}).length) {
            _.forEach(this.dashboard.layoutSettings.parameters, item => {
                item.locked = !!item.locked;
            });
        }

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
        if (this.reloadSub) {
            this.reloadSub.unsubscribe();
        }
    }

    public back() {
        this.location.back();
    }

    public booleanUpdate(event, parameter) {
        parameter.value = event.checked;
        this.reloadDashboard();
    }

    public changeDateType(event, parameter, value) {
        event.stopPropagation();
        event.preventDefault();
        parameter._dateType = value;
    }

    public updatePeriodValue(value, period, parameter) {
        parameter.value = `${value}_${period}_AGO`;
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
    }

    public async loadListParameters(parameter: any) {
        if (parameter.settings && parameter.settings.datasetInstance) {
            return this.datasetService.evaluateDataset(parameter.settings.datasetInstance, '0', '100000')
                .then((data: any) => {
                    const list = _.map(data.allData, item => {
                        return {label: item[parameter.settings.labelColumn], value: item[parameter.settings.valueColumn]};
                    });
                    parameter.list = _.uniqWith(list, _.isEqual);
                });
        }
    }

    public async reloadDashboard() {
        for (const item of this.itemComponents) {
            if (item.dashboardDatasetInstance) {
                await item.init(true);
            }
        }
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
        componentRef.instance.actionEvents = this.actionEvents;
        componentRef.instance.external = !!this.external;
        componentRef.instance.queryParams = this.queryParams;
        componentRef.instance.optimise = false;

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

        if (this.dashboard.alertsEnabled && !this.external) {
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

        this.itemComponents.push(componentRef.instance);

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
