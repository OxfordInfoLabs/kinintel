import {AfterViewInit, Component, ElementRef, HostBinding, Input, ViewChild} from '@angular/core';
import {MatDialog} from '@angular/material/dialog';
import {ConfigureItemComponent} from '../configure-item/configure-item.component';
import {DatasourceService} from '../../../services/datasource.service';
import * as _ from 'lodash';
import {DatasetService} from '../../../services/dataset.service';
import {AlertService} from '../../../services/alert.service';
import {Subscription} from 'rxjs';
import {DashboardService} from '../../../services/dashboard.service';
import {DomSanitizer} from '@angular/platform-browser';

declare var window: any;

@Component({
    selector: 'ki-item-component',
    templateUrl: './item-component.component.html',
    styleUrls: ['./item-component.component.sass'],
    host: {
        class: 'dark:bg-gray-700 dark:text-gray-100'
    }
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
    public String = String;
    public dataset: any;
    public chartData: any;
    public dashboardDatasetInstance: any;
    public loadingItem = false;
    public filterFields: any = [];
    public dependencies: any = {};
    public metric: any = {};
    public textData: any = {};
    public imageData: any = {};
    public tabular: any = {};
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
    public limit = 25;
    public page = 1;
    public endOfResults = false;

    private offset = 0;
    private itemLoadedSub: Subscription;

    constructor(private dialog: MatDialog,
                private kiDatasourceService: DatasourceService,
                private kiDatasetService: DatasetService,
                private kiAlertService: AlertService,
                private dashboardService: DashboardService,
                private sanitizer: DomSanitizer) {
    }

    ngAfterViewInit() {
    }

    public async init(evaluate = false) {
        this.mapLayoutSettingsToComponentData();

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
        this.configureClass = true;
        if (this.dependencies.instanceKeys && this.dependencies.instanceKeys.length) {
            this.loadingItem = true;

            this.itemLoadedSub = this.dashboardService.dashboardItems.subscribe(async loaded => {
                const loadedInstances = Object.keys(loaded);
                const dependent = _.filter(loadedInstances, key => {
                    return this.dependencies.instanceKeys.indexOf(key) > -1;
                });
                if (dependent.length === this.dependencies.instanceKeys.length) {
                    if (this.itemLoadedSub) {
                        this.itemLoadedSub.unsubscribe();
                    }

                    const allLoaded = _.every(this.dependencies.instanceKeys, key => {
                        return this.dependencies[key].type === 'LOADED';
                    });
                    if (allLoaded) {
                        this.evaluate();
                    } else {
                        const matches = _.filter(this.dependencies, {type: 'MATCH'});
                        const allMatched = [];
                        for (const dep of matches) {
                            const selectedDatasetInstance = _.find(this.dashboard.datasetInstances, {instanceKey: dep.key});
                            if (selectedDatasetInstance) {
                                const mappedParams = this.getMappedParams(selectedDatasetInstance);

                                const transformations = _.clone(selectedDatasetInstance.transformationInstances);
                                transformations.push({type: 'filter', config: dep.filterJunction});

                                const datasetInstanceSummary = {
                                    datasetInstanceId: selectedDatasetInstance.datasetInstanceId,
                                    datasourceInstanceKey: selectedDatasetInstance.datasourceInstanceKey,
                                    transformationInstances: transformations,
                                    parameterValues: mappedParams,
                                    parameters: selectedDatasetInstance.parameters
                                };

                                const data = await this.datasetService.evaluateDataset(
                                    datasetInstanceSummary, '0', '1');

                                allMatched.push(data.allData.length === 1);
                            }
                        }
                        if (_.every(allMatched)) {
                            this.evaluate();
                        } else {
                            this.loadingItem = false;
                            const itemElement: any = document.getElementById(this.itemInstanceKey).closest('.grid-stack-item');
                            itemElement.classList.add('item-disabled');
                        }
                    }
                }
            });
        } else if (evaluate) {
            this.evaluate();
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
                    this.init(true);
                }
            });
        }

    }

    public load() {
        if (this.dashboardDatasetInstance) {
            return this.evaluate();
        }
        return Promise.resolve(true);
    }

    public removeWidget(event) {
        const message = 'Are your sure you would like to remove this item from your dashboard?';
        if (window.confirm(message)) {
            const itemElement = document.getElementById(this.itemInstanceKey);
            _.remove(this.dashboard.datasetInstances, {instanceKey: this.itemInstanceKey});
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
            const element: any = document.createElement('div');
            if (element) {
                element.innerHTML = this.bindParametersInString(this.textData.value);

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
                const boundHTML = bind.boundContext.els[0].innerHTML;
                this.textData.safeTextData = this.sanitizer.sanitize(1, boundHTML);
                element.remove();
            }
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
        if (this.metric.main) {
            this.metric.mainValue = this.dataset.allData[0][this.metric.main];
        }

        if (this.metric.mainFormat) {
            if (this.metric.mainFormatDecimals) {
                this.metric.mainValue = Number(this.metric.mainValue).toFixed(this.metric.mainFormatDecimals);
            }
            if (this.metric.mainFormat === 'Currency' && this.metric.mainFormatCurrency) {
                const currency = _.find(this.currencies, {value: this.metric.mainFormatCurrency});
                if (currency) {
                    this.metric.mainValue = currency.symbol + '' + this.metric.mainValue;
                }
            }
            if (this.metric.mainFormat === 'Percentage') {
                this.metric.mainValue = this.metric.mainValue + '%';
            }
        }

        if (this.metric.subMetric) {
            this.metric.subValue = this.dataset.allData[0][this.metric.subMetric];
        }

        if (this.metric.subMetricFormat) {
            if (this.metric.subMetricFormatDecimals) {
                this.metric.subValue = Number(this.metric.subValue).toFixed(this.metric.subMetricFormatDecimals);
            }
            if (this.metric.subMetricFormat === 'Currency' && this.metric.subMetricFormatCurrency) {
                const currency = _.find(this.currencies, {value: this.metric.subMetricFormatCurrency});
                if (currency) {
                    this.metric.subValue = currency.symbol + '' + this.metric.subValue;
                }
            }
            if (this.metric.subMetricFormat === 'Percentage') {
                this.metric.subValue = this.metric.subValue + '%';
            }
        }

        if (this.metric.showSubChange && !_.isNil(this.metric.subValue) && String(this.metric.subValue).length) {
            const changeClass = `${parseInt(this.metric.subValue, 10) > 0 ? 'up' : 'down'}`;
            const icon = `${parseInt(this.metric.subValue, 10) > 0 ? '&#8593;' : '&#8595;'}`;
            this.metric.subValue = `<span class="sub-change ${changeClass}">${icon}&nbsp;${this.metric.subValue}</span>`;
        }
    }

    public callToActionLink(cta, dataItem?) {
        if (cta.type === 'dashboard') {
            const params = _.pickBy(cta.parameters, (value, key) => {
                return !_.startsWith(key, 'custom-');
            });

            const data = dataItem || this.dashboard.layoutSettings.parameters;

            Object.keys(params).forEach(paramKey => {
                let value = this.bindParametersInString(params[paramKey], data);
                // Check if we have any column eg. [[ ]] values needing mapping
                value = this.mapColumnToValue(value, data);

                params[paramKey] = value;
            });
            const urlParams = new URLSearchParams(params).toString();
            window.location.href = `/dashboards/${cta.value}${this.admin ? '?a=true&' : '?'}${urlParams}`;
        } else if (cta.type === 'custom') {
            let url = cta.link;

            const data = dataItem || this.dashboard.layoutSettings.parameters;

            url = this.bindParametersInString(url, data);
            // Check if we have any column eg. [[ ]] values needing mapping
            url = this.mapColumnToValue(url, data);
            window.location.href = url;
        }
    }

    public bindParametersInString(searchString, data?) {
        if (!data) {
            data = this.dashboard.layoutSettings.parameters;
        }
        if (searchString) {
            const matches = searchString.match(/\{\{(.*?)\}\}/g) || [];
            matches.forEach(exp => {
                const expValue = exp.replace('{{', '').replace('}}', '');
                const parameter = data ? data[expValue] : null;
                if (parameter) {
                    const value = _.isPlainObject(parameter) ? parameter.value : parameter;
                    searchString = searchString.replace(exp, value);
                }
            });
        }

        return searchString;
    }

    public increaseOffset() {
        this.page = this.page + 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.evaluate();
    }

    public decreaseOffset() {
        this.page = this.page <= 1 ? 1 : this.page - 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.evaluate();
    }

    public pageSizeChange(value) {
        this.limit = value;
        this.evaluate(true);
    }

    private mapColumnToValue(searchString, data) {
        if (searchString) {
            const matches = searchString.match(/\[\[(.*?)\]\]/g) || [];
            matches.forEach(exp => {
                const expValue = exp.replace('[[', '').replace(']]', '');
                const value = data ? data[expValue] : null;
                if (value) {
                    searchString = searchString.replace(exp, value);
                }
            });
        }

        return searchString;
    }

    private evaluate(resetPager?) {
        if (resetPager) {
            this.resetPager();
        }

        const itemElement = document.getElementById(this.itemInstanceKey);
        if (itemElement) {
            const parentElement = itemElement.closest('.grid-stack-item');
            parentElement.classList.remove('item-disabled');
        }

        if (this.grid && _.isFunction(this.grid.makeElement)) {
            setTimeout(() => {
                this.grid.makeElement(itemElement);
            }, 0);
        }

        this.loadingItem = true;

        const mappedParams = this.getMappedParams(this.dashboardDatasetInstance);

        const datasetInstanceSummary = {
            datasetInstanceId: this.dashboardDatasetInstance.datasetInstanceId,
            datasourceInstanceKey: this.dashboardDatasetInstance.datasourceInstanceKey,
            transformationInstances: this.dashboardDatasetInstance.transformationInstances,
            parameterValues: mappedParams,
            parameters: this.dashboardDatasetInstance.parameters
        };

        if (this.dashboard.layoutSettings.parameters && Object.keys(this.dashboard.layoutSettings.parameters).length) {
            _.forEach(this.dashboard.layoutSettings.parameters, (param, name) => {
                if (!datasetInstanceSummary.parameterValues[name]) {
                    datasetInstanceSummary.parameterValues[name] = param.value;
                }
            });
        }

        return this.datasetService.evaluateDataset(
            datasetInstanceSummary,
            String(this.offset),
            String(this.limit)
        )
            .then(data => {
                this.dataset = data;
                this.filterFields = _.map(this.dataset.columns, column => {
                    return {
                        title: column.title,
                        name: column.name
                    };
                });
                if (this.dashboard.layoutSettings.metric) {
                    this.updateMetricDataValues();
                }

                if (this.dashboard.layoutSettings.imageData) {
                    this.updateImageData();
                }

                if (Object.keys(this.textData).length) {
                    this.evaluateTextData();
                }

                this.loadingItem = false;
                this.configureClass = false;
                this.setChartData();

                if (this.dashboard.alertsEnabled) {
                    if (this.dashboardDatasetInstance.alerts && this.dashboardDatasetInstance.alerts.length) {
                        const dashboardInstance = _.cloneDeep(this.dashboardDatasetInstance);
                        dashboardInstance.parameterValues = mappedParams;
                        this.alertService.processAlertsForDashboardDatasetInstance(dashboardInstance)
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
                        this.resetAlertData(itemElement);
                    }
                } else {
                    this.resetAlertData(itemElement);
                }

                const existing = this.dashboardService.dashboardItems.getValue();
                existing[this.itemInstanceKey] = true;
                this.dashboardService.dashboardItems.next(existing);

                this.endOfResults = this.dataset.allData.length < this.limit;

                return Promise.resolve(true);
            }).catch(err => {
            });
    }

    private getMappedParams(dashboardDatasetInstance) {
        const mappedParams = {};
        _.forEach(dashboardDatasetInstance.parameterValues, (value, key) => {
            if (_.isString(value) && value.includes('{{')) {
                const globalKey = value.replace('{{', '').replace('}}', '');
                if (this.dashboard.layoutSettings.parameters && this.dashboard.layoutSettings.parameters[globalKey]) {
                    mappedParams[key] = this.dashboard.layoutSettings.parameters[globalKey].value;
                }
            } else {
                mappedParams[key] = value;
            }
        });
        return mappedParams;
    }

    private resetAlertData(itemElement) {
        this.alert = false;
        this.alertData = [];
        if (itemElement) {
            itemElement.classList.remove('alert');
            itemElement.parentElement.classList.remove('alert');
        }
    }

    private resetPager() {
        this.offset = 0;
        this.page = 1;
    }

    private mapLayoutSettingsToComponentData() {
        _.forEach(this.dashboard.layoutSettings, (data, key) => {
            if (key !== 'grid') {
                const defaultValue = _.isPlainObject(this[key]) ? {} : [];
                this[key] = Object.keys(data).length ? data[this.itemInstanceKey] || defaultValue : defaultValue;
            }
        });
    }
}
