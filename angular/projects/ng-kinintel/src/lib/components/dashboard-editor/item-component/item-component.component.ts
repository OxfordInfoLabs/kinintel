import {AfterViewInit, Component, Input, OnInit} from '@angular/core';
import {MatDialog} from '@angular/material/dialog';
import {ConfigureItemComponent} from '../configure-item/configure-item.component';
import {DatasourceService} from '../../../services/datasource.service';
import 'gridstack/dist/gridstack.min.css';
import {GridStack, GridStackNode} from 'gridstack';
// THEN to get HTML5 drag&drop
import 'gridstack/dist/h5/gridstack-dd-native';
import * as _ from 'lodash';

@Component({
    selector: 'ki-item-component',
    templateUrl: './item-component.component.html',
    styleUrls: ['./item-component.component.sass']
})
export class ItemComponentComponent implements OnInit, AfterViewInit {

    @Input() dashboardItemType: any;
    @Input() itemInstanceKey: any;
    @Input() dashboard: any;
    @Input() dashboardItem: any;
    @Input() dragItem: boolean;
    @Input() grid: any;

    public dataset: any;
    public chartData: any;
    public dashboardDatasetInstance: any;
    public loadingItem = false;
    public filterFields: any = [];

    private itemGrid: GridStack;

    private static myClone(event) {
        return event.target.cloneNode(true);
    }

    constructor(private dialog: MatDialog,
                private datasourceService: DatasourceService) {
    }

    ngOnInit(): void {

    }

    ngAfterViewInit() {
        const options = {
            minRow: 1, // don't collapse when empty
            float: false,
            dragIn: '.draggable-toolbar .grid-stack-item', // add draggable to class
            dragInOptions: {
                revert: 'invalid',
                scroll: false,
                appendTo: 'body',
                helper: ItemComponentComponent.myClone
            },
            acceptWidgets: (el) => {
                el.className += ' grid-stack-item';
                return true;
            }
        };
        this.grid = GridStack.init(options);
    }

    public configure() {
        const dialogRef = this.dialog.open(ConfigureItemComponent, {
            width: '100vw',
            height: '100vh',
            maxWidth: '100vw',
            maxHeight: '100vh',
            hasBackdrop: false,
            data: {
                grid: this.grid,
                dashboard: this.dashboard,
                dashboardDatasetInstance: this.dashboardDatasetInstance,
                itemInstanceKey: this.itemInstanceKey,
                dashboardItemType: this.dashboardItemType
            }
        });
        dialogRef.afterClosed().subscribe(dashboardDatasetInstance => {
            this.dashboardDatasetInstance = dashboardDatasetInstance;
            this.load();
        });
    }

    public load() {
        this.loadingItem = true;
        return this.datasourceService.evaluateDatasource(
            this.dashboardDatasetInstance.datasourceInstanceKey,
            this.dashboardDatasetInstance.transformationInstances,
            this.dashboardDatasetInstance.parameterValues)
            .then(data => {
                this.dataset = data;
                this.filterFields = _.map(this.dataset.columns, column => {
                    return {
                        title: column.title,
                        name: column.name
                    };
                });
                this.loadingItem = false;
                this.setChartData();
            }).catch(err => {
            });
    }

    public removeWidget(event) {
        const message = 'Are your sure you would like to remove this item from your dashboard?';
        if (window.confirm(message)) {
            const widget = event.target.closest('.grid-stack-item');
            this.grid.removeWidget(widget);
        }
    }

    public setChartData() {
        if (this.dashboardItemType.xAxis && this.dashboardItemType.yAxis) {
            let data: any;

            if (this.dashboardItemType.type !== 'pie' && this.dashboardItemType.type !== 'doughnut') {
                data = _.map(this.dataset.allData, item => {
                    return {x: item[this.dashboardItemType.xAxis], y: item[this.dashboardItemType.yAxis]};
                });
            } else {
                data = _.map(this.dataset.allData, item => {
                    return item[this.dashboardItemType.yAxis];
                });
            }

            this.chartData = [
                {
                    data,
                    label: _.find(this.filterFields, {name: this.dashboardItemType.xAxis}).title,
                    fill: !!this.dashboardItemType.fill
                }
            ];
            this.dashboardItemType.labels = _.map(this.dataset.allData, item => {
                return item[this.dashboardItemType.xAxis];
            });
        }
    }
}
