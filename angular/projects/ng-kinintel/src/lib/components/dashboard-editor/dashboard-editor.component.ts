import {
    AfterViewInit, ApplicationRef,
    ChangeDetectorRef,
    Component, ComponentFactoryResolver, EmbeddedViewRef, Injector, Input,
    NgZone,
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
import {ActivatedRoute} from '@angular/router';
import {DashboardService} from '../../services/dashboard.service';
import * as _ from 'lodash';
import {DatasetAddParameterComponent} from '../dataset/dataset-editor/dataset-parameter-values/dataset-add-parameter/dataset-add-parameter.component';
import {MatDialog} from '@angular/material/dialog';

@Component({
    selector: 'ki-dashboard-editor',
    templateUrl: './dashboard-editor.component.html',
    styleUrls: ['./dashboard-editor.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class DashboardEditorComponent implements OnInit, AfterViewInit {

    @ViewChild('viewContainer', {read: ViewContainerRef}) viewContainer: ViewContainerRef;

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
            height: 1
        }
    ];
    public dashboard: any = {};
    public activeSidePanel: string = null;
    public _ = _;

    private grid: GridStack;

    private static myClone(event) {
        return event.target.cloneNode(true);
    }

    constructor(private componentFactoryResolver: ComponentFactoryResolver,
                private applicationRef: ApplicationRef,
                private injector: Injector,
                private route: ActivatedRoute,
                private dashboardService: DashboardService,
                private dialog: MatDialog) {
    }

    ngOnInit(): void {
    }

    ngAfterViewInit() {
        const options = {
            minRow: 1, // don't collapse when empty
            float: false,
            cellHeight: 50,
            minW: 1024,
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
        });

        const dashboardId = this.route.snapshot.params.dashboard;
        if (dashboardId) {
            this.dashboardService.getDashboard(dashboardId).then(dashboard => {
                this.dashboard = dashboard;
                if (this.dashboard.displaySettings && this.dashboard.displaySettings.grid.length) {
                    this.grid.load(this.dashboard.displaySettings.grid);
                }
            });
        }

    }

    public addParameter() {
        const dialogRef = this.dialog.open(DatasetAddParameterComponent, {
            width: '600px',
            height: '600px'
        });
        dialogRef.afterClosed().subscribe(parameter => {
            if (parameter) {
                if (!this.dashboard.displaySettings) {
                    this.dashboard.displaySettings = {};
                }
                if (!this.dashboard.displaySettings.parameters) {
                    this.dashboard.displaySettings.parameters = {};
                }

                parameter.value = parameter.defaultValue || '';
                this.dashboard.displaySettings.parameters[parameter.name] = parameter;
            }
            this.dashboardService.saveDashboard(this.dashboard);
        });
    }

    public removeParameter(parameter) {
        const message = 'Are you sure you would like to remove this parameter. This may cause some dashboard items ' +
            'to fail.';
        if (window.confirm(message)) {
            delete this.dashboard.displaySettings.parameters[parameter.name];
            this.dashboardService.saveDashboard(this.dashboard);
        }
    }

    public setParameterValue() {
        const parameters = _.values(this.dashboard.displaySettings.parameters);
        parameters.forEach(parameter => {
            this.dashboard.datasetInstances.forEach(instance => {
                if (!_.values(instance.parameterValues).length) {
                    instance.parameterValues = {};
                }
                instance.parameterValues[parameter.name] = parameter.value;
            });
        });
        this.dashboardService.saveDashboard(this.dashboard);
        this.grid.removeAll();
        this.grid.load(this.dashboard.displaySettings.grid);
    }

    public save() {
        this.dashboard.displaySettings.grid = this.grid.save(true);
        this.dashboardService.saveDashboard(this.dashboard);
    }

    private addComponentToGridItem(element, instanceId?, dashboardItemType?, load?) {
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

        componentRef.instance.grid = this.grid;
        componentRef.instance.dashboard = this.dashboard;

        const chartDetails = this.dashboard.displaySettings.charts ? this.dashboard.displaySettings.charts[instanceId] : null;

        componentRef.instance.dashboardDatasetInstance = _.find(this.dashboard.datasetInstances, {instanceKey: instanceId}) || null;
        componentRef.instance.dashboardItemType = chartDetails || (dashboardItemType || {});
        componentRef.instance.itemInstanceKey = instanceId;
        if (load) {
            componentRef.instance.load();
        }

        // attach component to the appRef so that so that it will be dirty checked.
        this.applicationRef.attachView(componentRef.hostView);

        return componentRef;
    }

}

