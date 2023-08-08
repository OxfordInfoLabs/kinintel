import {
    AfterViewInit,
    ApplicationRef,
    Component,
    ComponentFactoryResolver,
    EmbeddedViewRef,
    HostBinding,
    Injector,
    Input,
    OnDestroy,
    OnInit,
    ViewChild,
    ViewContainerRef,
    ViewEncapsulation
} from '@angular/core';
import {GridItemHTMLElement, GridStack, GridStackNode} from 'gridstack';
// THEN to get HTML5 drag&drop
import 'gridstack/dist/h5/gridstack-dd-native';
import {ItemComponentComponent} from './item-component/item-component.component';
import {
    ActivatedRoute,
    Router
} from '@angular/router';
import {DashboardService} from '../../services/dashboard.service';
import * as lodash from 'lodash';

const _ = lodash.default;
import {
    DatasetAddParameterComponent
} from '../dataset/dataset-editor/dataset-parameter-values/dataset-add-parameter/dataset-add-parameter.component';
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {MatLegacySnackBar as MatSnackBar} from '@angular/material/legacy-snack-bar';
import {AlertService} from '../../services/alert.service';
import {BehaviorSubject, Observable, Subject} from 'rxjs';
import moment from 'moment';
import {ActionEvent} from '../../objects/action-event';
import {
    DashboardSettingsComponent
} from './dashboard-settings/dashboard-settings.component';

@Component({
    selector: 'ki-dashboard-editor',
    templateUrl: './dashboard-editor.component.html',
    styleUrls: ['./dashboard-editor.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class DashboardEditorComponent implements OnInit, AfterViewInit, OnDestroy {

    @Input() sidenavService: any;
    @Input() accountId: any;
    @Input() actionEvents: ActionEvent[] = [];
    @Input() dashboardId: number;
    @Input() gridOnly = false;
    @Input() externalURL: string;
    @Input() apiKeys: any;

    @ViewChild('viewContainer', {read: ViewContainerRef}) viewContainer: ViewContainerRef;

    @HostBinding('class.p-4') get t() {
        return this.dashboard.displaySettings && this.dashboard.displaySettings.fullScreen;
    }

    public itemTypes: any = [
        {
            type: 'line',
            label: 'Line Chart',
            icon: 'show_chart',
            width: 4,
            height: 16
        },
        {
            type: 'bar',
            label: 'Bar Chart',
            icon: 'stacked_bar_chart',
            width: 4,
            height: 16
        },
        {
            type: 'pie',
            label: 'Pie Chart',
            icon: 'pie_chart',
            width: 4,
            height: 16
        },
        {
            type: 'table',
            label: 'Table',
            icon: 'table_chart',
            width: 4,
            height: 20
        },
        {
            type: 'doughnut',
            label: 'Doughnut',
            icon: 'donut_large',
            width: 4,
            height: 16
        },
        {
            type: 'metric',
            label: 'Metric',
            icon: 'trending_up',
            width: 3,
            height: 10
        },
        {
            type: 'heading',
            label: 'Heading',
            icon: 'title',
            width: 4,
            height: 8
        },
        {
            type: 'text',
            label: 'Template',
            icon: 'wysiwyg',
            width: 4,
            height: 16
        },
        {
            type: 'image',
            label: 'Image',
            icon: 'image',
            width: 3,
            height: 20
        },
        {
            type: 'words',
            label: 'Word Cloud',
            icon: 'language',
            width: 3,
            height: 20
        }
    ];
    public dashboard: any = {};
    public activeSidePanel: string = null;
    public _ = _;
    public editDashboardTitle = false;
    public dashboardParameters = new BehaviorSubject(null);
    public darkMode = false;
    public fullScreen = false;
    public admin: boolean;
    public showEditPanel = false;
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
    public clonedDashboard: any;
    public grid: GridStack;
    public optimise = true;
    public optimiseSubject = new Subject<boolean>();

    private queryParams: any = {};
    private routeURL: string;
    private initialGrid: any;
    private itemComponents: ItemComponentComponent[] = [];

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
        this.routeURL = _.filter(this.router.url.split('/'))[0];
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
            float: false,
            cellHeight: 20,
            minW: 768,
            disableOneColumnMode: false,
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
                    dashboardItemType || _.clone(this.itemTypes[item.el.dataset.index]), !!dashboardItemType);
            });
            if (this.dashboard.displaySettings.inset) {
                this.updateGridSpacing(this.dashboard.displaySettings.inset, false);
            }
        });

        const dashboardId = this.dashboardId || this.route.snapshot.params.dashboard;
        const routeData: any = this.route.snapshot.data;
        const editType = routeData ? routeData.type : null;

        if (editType === 'copy') {
            this.dashboard = await this.dashboardService.copyDashboard(dashboardId);
            this.dashboard.title = this.dashboard.title + ' copy';
        } else if (editType === 'extend') {
            this.dashboard = await this.dashboardService.extendDashboard(dashboardId);
        } else {
            this.dashboard = await this.dashboardService.getDashboard(dashboardId);
        }

        if (!this.dashboard.title) {
            this.dashboard.title = 'New Dashboard';
            this.editDashboardTitle = true;
        }

        // If we have any query params, check if they match any set out in the dashboard
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

        if (this.dashboard.displaySettings && (this.dashboard.displaySettings.length || Object.keys(this.dashboard.displaySettings).length)) {
            this.darkMode = !!this.dashboard.displaySettings.darkMode;
            this.optimise = this.dashboard.displaySettings.optimise !== undefined ? this.dashboard.displaySettings.optimise : true;
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

        setTimeout(() => {
            this.showEditPanel = true;
            this.sidenavService.close();
        }, 0);
    }

    ngOnDestroy() {
        document.body.classList.remove('dark');
        this.sidenavService.open();
    }

    public booleanUpdate(event, parameter) {
        parameter.value = event.checked;
    }

    public togglePerformance() {
        this.optimise = !this.optimise;

        setTimeout(() => {
            this.dashboard.displaySettings.optimise = this.optimise;
            this.optimiseSubject.next(this.optimise);
        }, 0);

    }

    public changeDateType(event, parameter, value) {
        event.stopPropagation();
        event.preventDefault();
        parameter._dateType = value;
    }

    public updatePeriodValue(value, period, parameter) {
        parameter.value = `${value}_${period}_AGO`;
    }

    public editDashboardItems() {
        this.showEditPanel = !this.showEditPanel;
        if (this.showEditPanel) {
            this.sidenavService.close();
        } else {
            this.sidenavService.open();
        }
    }

    public openSettings() {
        const dialogRef = this.dialog.open(DashboardSettingsComponent, {
            width: '800px',
            height: '800px',
            data: {
                dashboard: this.dashboard
            }
        });
    }

    public openFullScreen() {
        this.save(false).then(() => {
            window.location.href = `/dashboards/${this.dashboard.id}/full${this.admin ? '?a=true&' : '?'}`;
        });
    }

    public updateGridSpacing(space, save = true) {
        this.dashboard.displaySettings.inset = space;
        document.querySelectorAll('.grid-stack-item-content')
            .forEach((el: any) => el.style.inset = space);
    }

    public toggleNotifications() {
        this.dashboard.alertsEnabled = !this.dashboard.alertsEnabled;
        this.reloadDashboard();
    }

    public async reloadDashboard() {
        for (const item of this.itemComponents) {
            await item.init(true);
        }
    }

    public addParameter(existingParameter?, parameterValueIndex?) {
        let clonedParameter = null;
        if (existingParameter) {
            clonedParameter = _.clone(existingParameter);
        }
        const dialogRef = this.dialog.open(DatasetAddParameterComponent, {
            width: '650px',
            height: '650px',
            data: {
                parameter: clonedParameter
            }
        });
        dialogRef.afterClosed().subscribe(parameter => {
            if (parameter) {
                if (!this.dashboard.layoutSettings) {
                    this.dashboard.layoutSettings = {};
                }
                if (!this.dashboard.layoutSettings.parameters) {
                    this.dashboard.layoutSettings.parameters = {};
                }
                if (!clonedParameter) {
                    parameter.value = parameter.defaultValue || '';
                } else {
                    if (!clonedParameter.value) {
                        parameter.value = parameter.defaultValue || '';
                    }
                }

                this.dashboard.layoutSettings.parameters[parameter.name] = parameter;
            }
        });
    }

    public removeParameter(parameter) {
        const message = 'Are you sure you would like to remove this parameter. This may cause some dashboard items ' +
            'to fail.';
        if (window.confirm(message)) {
            delete this.dashboard.layoutSettings.parameters[parameter.name];
        }
    }

    public save(showSaved = true) {
        this.dashboard.layoutSettings.grid = this.grid.save(true);
        this.dashboard.layoutSettings.grid.forEach(gridItem => {
            const itemElement = document.createRange().createContextualFragment(gridItem.content);
            try {
                itemElement.children.item(0).innerHTML = '';
            } catch (e) {
            }
            gridItem.content = itemElement.children.item(0).outerHTML;
        });
        return this.dashboardService.saveDashboard(this.dashboard, this.accountId).then((dashboardId) => {
            if (showSaved) {
                this.snackBar.open('Dashboard successfully saved.', 'Close', {
                    verticalPosition: 'top',
                    duration: 3000
                });
            }
            if (!this.dashboard.id) {
                window.location.href = `/${this.routeURL}/${dashboardId}${this.admin ? '?a=true' : ''}`;
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

        element.firstChild.id = instanceId ? instanceId : 'i' + Date.now().toString();
        instanceId = element.firstChild.id;

        componentRef.instance.admin = this.admin;
        componentRef.instance.grid = this.grid;
        componentRef.instance.dashboard = this.dashboard;
        componentRef.instance.actionEvents = this.actionEvents;
        componentRef.instance.optimiseSubject = this.optimiseSubject;
        componentRef.instance.optimise = this.optimise;

        const chartDetails = this.dashboard.layoutSettings.charts ? this.dashboard.layoutSettings.charts[instanceId] : null;

        componentRef.instance.dashboardDatasetInstance = _.find(this.dashboard.datasetInstances, {instanceKey: instanceId}) || null;
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

