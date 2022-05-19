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
import * as moment from 'moment';
import {Router} from '@angular/router';
import {
    EditDashboardAlertComponent
} from '../configure-item/edit-dashboard-alert/edit-dashboard-alert.component';

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
    public _ = _;
    public imageError = false;
    public Object = Object;
    public String = String;
    public dataset: any;
    public chartData: any;
    public dashboardDatasetInstance: any = {};
    public loadingItem = false;
    public filterFields: any = [];
    public dependencies: any = {};
    public metric: any = {};
    public textData: any = {};
    public imageData: any = {};
    public tabular: any = {};
    public tableCells: any = {};
    public hiddenColumns: any = {};
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
                private sanitizer: DomSanitizer,
                private router: Router) {
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

    public editItemAlerts(alert, index?) {
        const dialogRef = this.dialog.open(EditDashboardAlertComponent, {
            width: '900px',
            height: '750px',
            data: {
                alert,
                filterFields: this.filterFields
            }
        });

        dialogRef.afterClosed().subscribe(alertItem => {
            if (alertItem) {
                if (!this.dashboardDatasetInstance.alerts) {
                    this.dashboardDatasetInstance.alerts = [];
                }
                if (index >= 0) {
                    this.dashboardDatasetInstance.alerts[index] = alertItem;
                } else {
                    this.dashboardDatasetInstance.alerts.push(alertItem);
                }

                this.reloadAlert();
            }
        });
    }

    public removeAlert(event, alert, i) {
        const message = `Are you sure you would like to delete the "${alert.title}" Alert?`;
        if (window.confirm(message)) {
            this.dashboardDatasetInstance.alerts.splice(i, 1);
            this.reloadAlert();
        }
    }

    public updateHeading(event) {
        if (!this.dashboard.displaySettings.heading) {
            this.dashboard.displaySettings.heading = {};
        }

        this.dashboard.displaySettings.heading[this.itemInstanceKey] = this.dashboardItemType.headingValue;
        this.dashboardItemType._editing = false;
    }

    public evaluateTextData(textData) {
        let evaluatedTextData = '';
        const element: any = document.createElement('div');
        element.innerHTML = this.bindParametersInString(textData);

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
        evaluatedTextData = this.sanitizer.sanitize(1, boundHTML);
        element.remove();
        return evaluatedTextData;
    }

    public setChartData() {
        if (this.dashboardItemType.xAxis && this.dashboardItemType.yAxis) {
            let data: any;

            if (!this.dashboardItemType.seriesColumn) {
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
            } else {
                const chartData = [];
                const series = _.uniq(_.map(this.dataset.allData, this.dashboardItemType.seriesColumn));
                series.forEach(value => {
                    const seriesResults = _.filter(this.dataset.allData, allData => {
                        return allData[this.dashboardItemType.seriesColumn] === value;
                    });

                    chartData.push({
                        data: _.map(seriesResults, item => {
                            return {x: item[this.dashboardItemType.xAxis], y: item[this.dashboardItemType.yAxis]};
                        }),
                        label: value,
                        fill: !!this.dashboardItemType.fill
                    });
                });
                this.chartData = chartData;
                this.dashboardItemType.labels = series;
            }
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

        this.hiddenColumns = {};

        return this.datasetService.evaluateDataset(
            datasetInstanceSummary,
            String(this.offset),
            String(this.limit)
        )
            .then(data => {
                if (Object.keys(this.tableCells).length) {
                    Object.keys(this.tableCells).forEach(tableCell => {
                        if (tableCell !== 'column') {
                            data.allData.map((item, index) => {

                                if (item[tableCell]) {
                                    const cellData = this.tableCells[tableCell].data;
                                    switch (this.tableCells[tableCell].type) {
                                        case 'number':
                                            const cellNumber = Number(item[tableCell]);
                                            item[tableCell] = cellNumber.toFixed(cellData.decimal);
                                            break;
                                        case 'currency':
                                            let cellCurrency: any = Number(item[tableCell]);

                                            if (cellData.thousandsSeparator && (cellData.currency && cellData.currency.value)) {
                                                cellCurrency = cellCurrency.toLocaleString('en-GB', {
                                                    style: 'currency',
                                                    currency: cellData.currency.value,
                                                    minimumFractionDigits: cellData.decimal
                                                });
                                            } else {
                                                cellCurrency = cellCurrency.toFixed(cellData.decimal);
                                                if (cellData.thousandsSeparator) {
                                                    cellCurrency = Number(cellCurrency).toLocaleString('en-GB', {
                                                        minimumFractionDigits: cellData.decimal
                                                    });
                                                }
                                                if (cellData.currency && cellData.currency.value) {
                                                    cellCurrency = `${cellData.currency.symbol}${cellCurrency}`;
                                                }
                                            }

                                            item[tableCell] = cellCurrency;
                                            break;
                                        case 'percentage':
                                            let cellPercent: any = Number(item[tableCell]);
                                            const formatter = new Intl.NumberFormat('en-GB', {
                                                style: 'percent',
                                                minimumFractionDigits: cellData.decimal
                                            });
                                            cellPercent = formatter.format(cellPercent);

                                            if (!cellData.thousandsSeparator) {
                                                cellPercent = cellPercent.replace(',', '');
                                            }

                                            item[tableCell] = cellPercent;
                                            break;
                                        case 'datetime':
                                            const dateMoment = moment(item[tableCell]);
                                            item[tableCell] = dateMoment.format(cellData.dateFormat + ' ' + cellData.timeFormat);
                                            break;
                                        case 'comparison':
                                            const comparisonColumn = this.tableCells[tableCell].data.comparisonColumn;
                                            const initialValue = _.isPlainObject(item[tableCell]) ? item[tableCell].initialValue : item[tableCell];
                                            const comparisonValue = _.isPlainObject(data.allData[index][comparisonColumn]) ? data.allData[index][comparisonColumn].initialValue : data.allData[index][comparisonColumn];
                                            let difference: any = Number(initialValue) - Number(comparisonValue);
                                            let cellValue = `<div class="flex items-center space-between"><span class="font-medium">${initialValue}</span>`;
                                            if (difference === 0) {
                                                cellValue += `<span class="ml-1 text-blue-500 font-medium text-lg">&#61;</span>`;
                                            }
                                            if (difference > 0) {
                                                if (cellData.comparisonPercentage) {
                                                    const compareFormatter = new Intl.NumberFormat('en-GB', {
                                                        style: 'percent',
                                                        minimumFractionDigits: cellData.comparisonDecimals || 0
                                                    });
                                                    difference = compareFormatter.format(difference / comparisonValue);
                                                }
                                                cellValue += `<span class="ml-1 text-green-500 font-medium text-lg">&#8593;</span><span class="text-sm text-green-500">${difference}</span>`;
                                            }
                                            if (difference < 0) {
                                                if (cellData.comparisonPercentage) {
                                                    const compareFormatter = new Intl.NumberFormat('en-GB', {
                                                        style: 'percent',
                                                        minimumFractionDigits: cellData.comparisonDecimals || 0
                                                    });
                                                    difference = compareFormatter.format(difference / comparisonValue);
                                                }
                                                cellValue += `<span class="ml-1 text-red-500 font-medium text-lg">&#8595;</span><span class="text-sm text-red-500">${difference}</span>`;
                                            }
                                            cellValue += `<span class="ml-2 text-xs text-gray-400">from ${comparisonValue}</span></div>`;
                                            item[tableCell] = {cellValue, initialValue};
                                            break;
                                        case 'link':
                                            let linkValue = item[tableCell];
                                            let anchor = '';
                                            if (cellData.linkType === 'custom') {
                                                const dataItem = data.allData[index] || this.dashboard.layoutSettings.parameters;

                                                linkValue = this.bindParametersInString(linkValue, dataItem);
                                                // Check if we have any column eg. [[ ]] values needing mapping
                                                linkValue = this.mapColumnToValue(linkValue, dataItem);
                                                anchor = `<a href="${linkValue}" target="_blank" class="text-indigo-600 hover:underline flex items-center">${item[tableCell]}<span class="ml-0.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                      <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                                      <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                                    </svg></span></a>`;
                                            } else if (cellData.linkType === 'dashboard') {
                                                const params = {};
                                                const dashboardLink = cellData.dashboardLink;
                                                const dataItem = data.allData[index] || this.dashboard.layoutSettings.parameters;

                                                Object.keys(cellData.dashboardLinkParams).forEach(paramKey => {
                                                    let value = this.bindParametersInString(cellData.dashboardLinkParams[paramKey], dataItem);
                                                    // Check if we have any column eg. [[ ]] values needing mapping
                                                    value = this.mapColumnToValue(value, dataItem);

                                                    params[paramKey] = value;

                                                });
                                                const urlParams = new URLSearchParams(params).toString();
                                                linkValue = `${this.router.url.split('/')[0]}/dashboards/${dashboardLink.value}${this.admin ? '?a=true&' : '?'}${urlParams}`;
                                                anchor = `<a href="${linkValue}" class="text-indigo-600 hover:underline flex items-center">${item[tableCell]}<span class="ml-0.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                      <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                                      <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                                    </svg></span></a>`;
                                            } else {
                                                linkValue = linkValue.includes('http') ? linkValue : `http://${linkValue}`;
                                                anchor = `<a href="${linkValue}" target="_blank" class="text-indigo-600 hover:underline flex items-center">${item[tableCell]}<span class="ml-0.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                      <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                                      <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                                    </svg></span></a>`;
                                            }

                                            item[tableCell] = {cellValue: this.sanitizer.bypassSecurityTrustHtml(anchor), initialValue: linkValue};
                                            break;
                                        case 'custom':
                                            const element: any = document.createElement('div');
                                            const originalValue = item[tableCell];
                                            element.innerHTML = this.bindParametersInString(cellData.customText);

                                            const kData: any = {
                                                dataSet: data.allData
                                            };
                                            _.forEach(data.allData[index] || [], (value, key) => {
                                                kData[key] = value;
                                            });
                                            const Kinibind = window.Kinibind;
                                            Kinibind.config = {
                                                prefix: 'd',
                                                templateDelimiters: ['[[', ']]']
                                            };
                                            const bind = new Kinibind(element, kData);
                                            const boundHTML = bind.boundContext.els[0].innerHTML;
                                            item[tableCell] = {cellValue: this.sanitizer.sanitize(1, boundHTML), initialValue: originalValue};
                                            element.remove();
                                            break;
                                        case 'hide':
                                            this.hiddenColumns[tableCell] = true;
                                            break;
                                    }
                                }

                                return item;
                            });
                        }
                    });
                }
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
                    this.textData.safeTextData = this.evaluateTextData(this.textData.value);
                }

                if (Object.keys(this.general).length) {
                    this.general.evaluatedName = this.general.name ? this.evaluateTextData(this.general.name) : '';
                    this.general.evaluatedDescription = this.general.description ? this.evaluateTextData(this.general.description) : '';
                    this.general.evaluatedFooter = this.general.footer ? this.evaluateTextData(this.general.footer) : '';
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

    private reloadAlert() {
        const thisEl = document.getElementById(this.itemInstanceKey).closest('.grid-stack-item');
        const existingGrid = _.find(this.dashboard.layoutSettings.grid, grid => {
            return grid.content.includes(`id="${this.itemInstanceKey}"`);
        });
        if (thisEl && existingGrid) {
            this.grid.removeWidget(thisEl);
            this.grid.addWidget(existingGrid);
        }

        this.dashboardService.saveDashboard(this.dashboard);
    }
}
