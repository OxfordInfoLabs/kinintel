import {AfterViewInit, Component, HostBinding, Input, OnInit} from '@angular/core';
import {MatDialog} from '@angular/material/dialog';
import {ConfigureItemComponent} from '../configure-item/configure-item.component';
import {DatasourceService} from '../../../services/datasource.service';
import 'gridstack/dist/gridstack.min.css';
import {GridStack} from 'gridstack';
// THEN to get HTML5 drag&drop
import 'gridstack/dist/h5/gridstack-dd-native';
import * as _ from 'lodash';
import {DataExplorerComponent} from '../../data-explorer/data-explorer.component';
import {DatasetService} from '../../../services/dataset.service';

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

    @HostBinding('class.justify-center') configureClass = false;

    public dataset: any;
    public chartData: any;
    public dashboardDatasetInstance: any;
    public loadingItem = false;
    public filterFields: any = [];
    public metricData: any = {};
    public general: any = {};
    public currencies = [
        {
            name: 'British Pound (£)',
            value: 'GBP',
            symbol: '£'
        },
        {
            name: 'US Dollar ($)',
            value: 'USD',
            symbol: '$'
        },
        {
            name: 'Euro (€)',
            value: 'EUR',
            symbol: '€'
        }
    ];

    private itemGrid: GridStack;

    private static myClone(event) {
        return event.target.cloneNode(true);
    }

    constructor(private dialog: MatDialog,
                private datasourceService: DatasourceService,
                private datasetService: DatasetService) {
    }

    ngOnInit(): void {
        if (this.dashboard &&
            this.dashboard.displaySettings.heading &&
            this.dashboard.displaySettings.heading[this.itemInstanceKey]) {
            this.dashboardItemType.headingValue = this.dashboard.displaySettings.heading[this.itemInstanceKey];
        }
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
        if (this.dashboardItemType.type === 'heading') {
            this.dashboardItemType._editing = true;
        } else {
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
                if (dashboardDatasetInstance) {
                    this.dashboardDatasetInstance = dashboardDatasetInstance;
                    this.load();
                }
            });
        }

    }

    public load() {
        if (this.dashboardDatasetInstance) {
            this.loadingItem = true;
            this.configureClass = true;
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
                    if (this.dashboard.layoutSettings.metric) {
                        this.metricData = this.dashboard.layoutSettings.metric[this.dashboardDatasetInstance.instanceKey] || {};
                        this.updateMetricDataValues();
                    }
                    if (this.dashboard.layoutSettings.general) {
                        this.general = this.dashboard.layoutSettings.general[this.dashboardDatasetInstance.instanceKey] || {};
                    }
                    this.loadingItem = false;
                    this.configureClass = false;
                    this.setChartData();
                }).catch(err => {
                });
        }
    }

    public removeWidget(event) {
        const message = 'Are your sure you would like to remove this item from your dashboard?';
        if (window.confirm(message)) {
            const widget = event.target.closest('.grid-stack-item');
            this.grid.removeWidget(widget);
        }
    }

    public updateHeading(event) {
        if (!this.dashboard.displaySettings.heading) {
            this.dashboard.displaySettings.heading = {};
        }

        this.dashboard.displaySettings.heading[this.itemInstanceKey] = this.dashboardItemType.headingValue;
        this.dashboardItemType._editing = false;
    }

    public callToAction() {
        if (this.general.cta.type === 'edit') {
            this.configure();
        } else if (this.general.cta.type === 'dataset') {
            this.datasetService.getDataset(this.general.cta.value).then(dataset => {
                const dialogRef = this.dialog.open(DataExplorerComponent, {
                    width: '100vw',
                    height: '100vh',
                    maxWidth: '100vw',
                    maxHeight: '100vh',
                    hasBackdrop: false,
                    data: {
                        dataset,
                        showChart: false
                    }
                });
                dialogRef.afterClosed().subscribe(res => {

                });
            });
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

    public updateMetricDataValues() {
        if (this.metricData.main) {
            this.metricData.mainValue = this.dataset.allData[0][this.metricData.main];
        }

        if (this.metricData.mainFormat) {
            if (this.metricData.mainFormatDecimals) {
                this.metricData.mainValue = Number(this.metricData.mainValue).toFixed(this.metricData.mainFormatDecimals);
            }
            if (this.metricData.mainFormat === 'Currency' && this.metricData.mainFormatCurrency) {
                const currency = _.find(this.currencies, {value: this.metricData.mainFormatCurrency});
                if (currency) {
                    this.metricData.mainValue = currency.symbol + '' + this.metricData.mainValue;
                }
            }
            if (this.metricData.mainFormat === 'Percentage') {
                this.metricData.mainValue = this.metricData.mainValue + '%';
            }
        }

        if (this.metricData.subMetric) {
            this.metricData.subValue = this.dataset.allData[0][this.metricData.subMetric];
        }

        if (this.metricData.subMetricFormat) {
            if (this.metricData.subMetricFormatDecimals) {
                this.metricData.subValue = Number(this.metricData.subValue).toFixed(this.metricData.subMetricFormatDecimals);
            }
            if (this.metricData.subMetricFormat === 'Currency' && this.metricData.subMetricFormatCurrency) {
                const currency = _.find(this.currencies, {value: this.metricData.subMetricFormatCurrency});
                if (currency) {
                    this.metricData.subValue = currency.symbol + '' + this.metricData.subValue;
                }
            }
            if (this.metricData.subMetricFormat === 'Percentage') {
                this.metricData.subValue = this.metricData.subValue + '%';
            }
        }

        if (this.metricData.showSubChange) {
            const changeClass = `${parseInt(this.metricData.subValue, 10) > 0 ? 'up' : 'down'}`;
            const icon = `${parseInt(this.metricData.subValue, 10) > 0 ? '&#8593;' : '&#8595;'}`;
            this.metricData.subValue = `<span class="sub-change ${changeClass}">${icon}&nbsp;${this.metricData.subValue}</span>`;
        }
    }
}
