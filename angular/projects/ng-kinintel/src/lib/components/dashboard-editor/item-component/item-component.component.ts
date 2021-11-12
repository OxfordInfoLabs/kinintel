import {
    AfterViewInit, Compiler,
    Component, ElementRef,
    HostBinding, Injector,
    Input,
    NgModule, NgModuleRef,
    OnInit,
    ViewChild,
    ViewContainerRef
} from '@angular/core';
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
import {AlertService} from '../../../services/alert.service';
import {Router} from '@angular/router';
declare var window: any;

@Component({
    selector: 'ki-item-component',
    templateUrl: './item-component.component.html',
    styleUrls: ['./item-component.component.sass']
})
export class ItemComponentComponent implements AfterViewInit {

    @Input() dashboardItemType: any;
    @Input() itemInstanceKey: any;
    @Input() dashboard: any;
    @Input() dashboardItem: any;
    @Input() dragItem: boolean;
    @Input() grid: any;

    @HostBinding('class.justify-center') configureClass = false;

    @ViewChild('textTemplate') textTemplate: ElementRef;

    public datasourceService: any;
    public datasetService: any;
    public alertService: any;

    public imageError = false;
    public Object = Object;
    public dataset: any;
    public chartData: any;
    public dashboardDatasetInstance: any;
    public loadingItem = false;
    public filterFields: any = [];
    public metricData: any = {};
    public textData: any = {};
    public imageData: any = {};
    public tabularData: any = {};
    public general: any = {};
    public callToAction: any = {};
    public alert = false;
    public alertData: any = [];
    public showAlertData = false;
    public admin: boolean;
    public viewOnly: boolean;
    public editAlerts: boolean;
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
                private kiDatasourceService: DatasourceService,
                private kiDatasetService: DatasetService,
                private kiAlertService: AlertService) {
    }

    ngAfterViewInit() {
    }

    public init(): void {
        if (!this.datasourceService) {
            this.datasourceService = this.kiDatasourceService;
        }

        if (!this.datasetService) {
            this.datasetService = this.kiDatasetService;
        }

        if (!this.alertService) {
            this.alertService = this.kiAlertService;
        }

        if (this.dashboard &&
            this.dashboard.displaySettings.heading &&
            this.dashboard.displaySettings.heading[this.itemInstanceKey]) {
            this.dashboardItemType.headingValue = this.dashboard.displaySettings.heading[this.itemInstanceKey];
        }
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
                    dashboardItemType: this.dashboardItemType,
                    admin: this.admin
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
        this.init();
        if (this.dashboardDatasetInstance) {
            this.loadingItem = true;
            this.configureClass = true;

            const mappedParams = {};
            _.forEach(this.dashboardDatasetInstance.parameterValues, (value, key) => {
                if (_.isString(value) && value.includes('{{')) {
                    const globalKey = value.replace('{{', '').replace('}}', '');
                    if (this.dashboard.layoutSettings.parameters && this.dashboard.layoutSettings.parameters[globalKey]) {
                        mappedParams[key] = this.dashboard.layoutSettings.parameters[globalKey].value;
                    }
                } else {
                    mappedParams[key] = value;
                }
            });

            return this.datasourceService.evaluateDatasource(
                this.dashboardDatasetInstance.datasourceInstanceKey,
                this.dashboardDatasetInstance.transformationInstances,
                mappedParams,
                '0', '10')
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
                    if (this.dashboard.layoutSettings.imageData) {
                        this.imageData = this.dashboard.layoutSettings.imageData[this.dashboardDatasetInstance.instanceKey] || {};
                        this.updateImageData();
                    }

                    if (this.dashboard.layoutSettings.tabular) {
                        this.tabularData = this.dashboard.layoutSettings.tabular[this.dashboardDatasetInstance.instanceKey] || {};
                    }

                    if (this.dashboard.layoutSettings.textData) {
                        this.textData = this.dashboard.layoutSettings.textData[this.dashboardDatasetInstance.instanceKey] || {};
                        if (Object.keys(this.textData).length) {
                            this.evaluateTextData();
                        }
                    }

                    if (this.dashboard.layoutSettings.callToAction) {
                        this.callToAction = this.dashboard.layoutSettings.callToAction[this.dashboardDatasetInstance.instanceKey] || {};
                    }
                    this.loadingItem = false;
                    this.configureClass = false;
                    this.setChartData();

                    const itemElement = document.getElementById(this.dashboardDatasetInstance.instanceKey);

                    if (this.dashboard.alertsEnabled) {
                        if (this.dashboardDatasetInstance.alerts && this.dashboardDatasetInstance.alerts.length) {
                            this.alertService.processAlertsForDashboardDatasetInstance(this.dashboardDatasetInstance)
                                .then((res: any) => {
                                    if (res && res.length) {
                                        this.alert = true;
                                        this.alertData = res;
                                        if (itemElement) {
                                            itemElement.classList.add('alert');
                                            itemElement.parentElement.classList.add('alert');
                                        }
                                    }
                                });
                        } else {
                            this.alert = false;
                            this.alertData = [];
                            if (itemElement) {
                                itemElement.classList.remove('alert');
                                itemElement.parentElement.classList.remove('alert');
                            }
                        }
                    } else {
                        this.alert = false;
                        this.alertData = [];
                        if (itemElement) {
                            itemElement.classList.remove('alert');
                            itemElement.parentElement.classList.remove('alert');
                        }
                    }

                }).catch(err => {
                });
        }
    }

    public removeWidget(event) {
        const message = 'Are your sure you would like to remove this item from your dashboard?';
        if (window.confirm(message)) {
            const itemElement = document.getElementById(this.itemInstanceKey);
            const widget = itemElement.closest('.grid-stack-item');
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

    public evaluateTextData() {
        setTimeout(() => {
            const element = document.getElementById(this.dashboardItemType.type + this.itemInstanceKey);
            const data: any = {
                dataSet: this.dataset.allData
            };
            _.forEach(this.dataset.allData[0] || [], (value, key) => {
                data[key] = value;
            });

            const Kinibind = window.Kinibind;
            Kinibind.config = {
                prefix: 'd',
                templateDelimiters: ['[[', ']]']
            };
            const bind = new Kinibind(element, data);
        }, 0);
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

    public updateImageData() {
        this.imageData.source = this.imageData.column ? this.dataset.allData[0][this.imageData.column] : null;
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

    public callToActionLink(cta, dataItem?) {
        if (cta.type === 'dashboard') {
            const params = _.pickBy(cta.parameters, (value, key) => {
                return !_.startsWith(key, 'custom-');
            });

            const data = dataItem || this.dashboard.layoutSettings.parameters;

            Object.keys(params).forEach(paramKey => {
                params[paramKey] = this.bindParametersInString(params[paramKey], data);
            });
            const urlParams = new URLSearchParams(params).toString();
            window.location.href = `/dashboards/${cta.value}${this.admin ? '?a=true&' : '?'}${urlParams}`;
        } else if (cta.type === 'custom') {
            let url = cta.link;

            const data = dataItem || this.dashboard.layoutSettings.parameters;

            url = this.bindParametersInString(url, data);
            window.location.href = url;
        }
    }

    public bindParametersInString(searchString, data?) {
        if (!data) {
            data = this.dashboard.layoutSettings.parameters;
        }
        if (searchString) {
            const matches = searchString.match(/(?<=\{\{).+?(?=\}\})/g) || [];
            matches.forEach(exp => {
                const parameter = data ? data[exp] : null;
                if (parameter) {
                    const value = _.isPlainObject(parameter) ? parameter.value : parameter;
                    searchString = searchString.replace(`{{${exp}}}`, value);
                }
            });
        }

        return searchString;
    }
}
