import {
    AfterViewInit,
    Component,
    ElementRef,
    EventEmitter,
    HostBinding,
    Input,
    OnDestroy,
    Output,
    ViewChild
} from '@angular/core';
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {ConfigureItemComponent} from '../configure-item/configure-item.component';
import {DatasourceService} from '../../../services/datasource.service';
import * as lodash from 'lodash';

const _ = lodash.default;
import {DatasetService} from '../../../services/dataset.service';
import {AlertService} from '../../../services/alert.service';
import {Subject, Subscription} from 'rxjs';
import {DashboardService} from '../../../services/dashboard.service';
import {DomSanitizer} from '@angular/platform-browser';
import moment from 'moment';
import regression from 'regression';
import {Router} from '@angular/router';
import {
    EditDashboardAlertComponent
} from '../configure-item/edit-dashboard-alert/edit-dashboard-alert.component';
import {ExportDataComponent} from '../../data-explorer/export-data/export-data.component';
import {Location} from '@angular/common';
import {ActionEvent} from '../../../objects/action-event';
import {ExternalService} from '../../../services/external.service';
import {ProjectService} from '../../../services/project.service';
import chroma from 'chroma-js';
import { DataSet, Network } from 'vis-network/standalone';
import visNetworkOptions from '../configure-item/vis-network-options.json';

declare var window: any;

@Component({
    selector: 'ki-item-component',
    templateUrl: './item-component.component.html',
    styleUrls: ['./item-component.component.sass']
})
export class ItemComponentComponent implements AfterViewInit, OnDestroy {

    @Input() dashboardItemType: any;
    @Input() itemInstanceKey: any;
    @Input() dashboard: any;
    @Input() dashboardItem: any;
    @Input() dragItem: boolean;
    @Input() grid: any;
    @Input() actionEvents: ActionEvent[] = [];
    @Input() external = false;
    @Input() queryParams: any;
    @Input() editAlerts = false;

    @Output() duplicateItem: EventEmitter<any> = new EventEmitter<any>();

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
    public networkData: any = {};
    public wordCloud: any = {};
    public imageData: any = {};
    public tabular: any = {};
    public tableCells: any = {};
    public hiddenColumns: any = {};
    public general: any = {};
    public callToAction: any = {};
    public actionItem: any = {};
    public alert = false;
    public alertData: any = [];
    public showAlertData = false;
    public admin: boolean;
    public viewOnly: boolean;
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
    public offset = 0;
    public endOfResults = false;
    public itemLocked = false;
    public optimise = true;
    public optimiseSubject = new Subject<boolean>();
    public canExportData = false;
    public externalError = false;
    public quotaExceeded: string = null;
    public existingGridItem = false;
    public itemNoMove = false;
    public visNetworkOptions = visNetworkOptions;

    private itemLoadedSub: Subscription;
    private optimiseSub: Subscription;

    constructor(private dialog: MatDialog,
                private kiDatasourceService: DatasourceService,
                private kiDatasetService: DatasetService,
                private kiAlertService: AlertService,
                private dashboardService: DashboardService,
                private sanitizer: DomSanitizer,
                private router: Router,
                private location: Location,
                private externalService: ExternalService,
                private projectService: ProjectService) {
    }

    ngAfterViewInit() {
        setTimeout(() => {
            this.canExportData = this.projectService.doesActiveProjectHavePrivilege('exportdata');

            const instanceElement = document.getElementById(this.itemInstanceKey);
            if (instanceElement) {
                const widget = instanceElement.closest('.grid-stack-item');
                this.itemNoMove = widget.getAttribute('gs-no-move') === 'true';
            }
        }, 50);
    }

    public async init(evaluate = false) {
        this.mapLayoutSettingsToComponentData();

        if (this.tabular && this.tabular.limit) {
            this.limit = this.tabular.limit;
        }

        if (this.general && this.general.limit) {
            this.limit = this.general.limit;
            this.offset = this.general.offset;
        }

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

        if (this.general.parameterBar && Object.keys(this.general.parameterBar).length) {
            if (!this.general.widgetParameters || Array.isArray(this.general.widgetParameters)) {
                this.general.widgetParameters = {};
            }

            Object.keys(this.general.parameterBar).forEach(paramKey => {
                if (this.general.parameterBar[paramKey] && !this.general.widgetParameters[paramKey]) {
                    this.general.widgetParameters[paramKey] = _.cloneDeep(this.dashboard.layoutSettings.parameters[paramKey]);
                }
                if (!this.general.parameterBar[paramKey]) {
                    delete this.general.widgetParameters[paramKey];
                }
            });
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

                                let data = null;
                                if (this.external) {
                                    data = await this.externalService.evaluateDataset(
                                        this.dashboard.id,
                                        this.itemInstanceKey,
                                        selectedDatasetInstance.parameterValues,
                                        '0',
                                        '1',
                                        this.queryParams);
                                } else {
                                    data = await this.datasetService.evaluateDataset(
                                        datasetInstanceSummary, '0', '1');
                                }


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

        this.optimiseSub = this.optimiseSubject.subscribe(res => this.optimise = res);

        this.existingGridItem = !!_.find(this.dashboard.layoutSettings.grid, item => {
            return item.content.includes(this.itemInstanceKey);
        });
    }

    ngOnDestroy() {
        if (this.optimiseSub) {
            this.optimiseSub.unsubscribe();
        }
        if (this.itemLoadedSub) {
            this.itemLoadedSub.unsubscribe();
        }
    }

    public lockItem() {
        const itemElement = document.getElementById(this.itemInstanceKey);
        const widget = itemElement.closest('.grid-stack-item');
        this.itemNoMove = widget.getAttribute('gs-no-move') !== 'true';
        this.grid.update(widget, {noMove: this.itemNoMove});
    }

    public duplicate() {
        this.duplicateItem.emit(this.itemInstanceKey);
    }

    public configure() {
        if (this.dashboardItemType.type === 'heading') {
            this.dashboardItemType._editing = true;
        } else {
            const queryParams: any = {};
            if (this.admin) {
                queryParams.a = true;
            }
            this.router.navigate([window.location.pathname], {
                queryParams,
                fragment: _.kebabCase(this.dashboard.title)
            });
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
                    admin: this.admin,
                    actionEvents: this.actionEvents
                }
            });
            dialogRef.afterClosed().subscribe(dashboardDatasetInstance => {
                if (dashboardDatasetInstance) {
                    this.dashboardDatasetInstance = dashboardDatasetInstance;
                    this.location.back();
                    this.init(true);
                }
            });
        }
    }

    public load() {
        // Check to see if any of the grid items are locked
        const grid = this.dashboard.layoutSettings.grid;
        const lockedItems = _.filter(grid, 'locked');
        this.itemLocked = !!_.find(lockedItems, lockedItem => {
            return lockedItem.content.includes(this.itemInstanceKey);
        });

        if (this.external) {
            this.itemLocked = true;
        }

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

    public triggerActionEvent(actionEvent, column, value) {
        const action: ActionEvent = _.find(this.actionEvents, {name: actionEvent});
        if (action) {
            action.event.next({key: column.name, value});
        }
    }

    public getActionEventTitle(actionEvent, column, value) {
        const action: ActionEvent = _.find(this.actionEvents, {name: actionEvent});

        let title = '';
        if (action) {
            title = this.isActionComplete(actionEvent, value) ? action.completeLabel : action.actionLabel;
        }
        return title;
    }

    public isActionComplete(actionEvent, value) {
        let complete = false;
        const action: ActionEvent = _.find(this.actionEvents, {name: actionEvent});

        if (action) {
            const findData = {};
            findData[action.comparisonField] = value;
            complete = _.find(action.data || [], findData);
        }

        return complete;
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

    public exportData() {
        const datasetInstanceSummary = this.prepareDatasetInstanceSummaryForEvaluation();

        const dialogRef = this.dialog.open(ExportDataComponent, {
            width: '600px',
            height: '530px',
            data: {
                datasetInstanceSummary
            }
        });
    }

    public updateHeading(event) {
        if (!this.dashboard.displaySettings.heading) {
            this.dashboard.displaySettings.heading = {};
        }

        this.dashboard.displaySettings.heading[this.itemInstanceKey] = this.dashboardItemType.headingValue;
        this.dashboardItemType._editing = false;
    }

    public async setupNetworkChart() {
        const nodeData = this.dataset.allData;

        const datasetNodes: any = [];
        const datasetEdges: any = [];

        nodeData.forEach(item => {
            const nodeLevelItem: any = {};
            const nodeItem: any = {
                id: item[this.networkData.nodeIds],
                label: item[this.networkData.nodeLabels],
                group: item[this.networkData.nodeGroups]
            };

            if (this.networkData.nodes) {
                if (this.networkData.nodes[nodeItem.id]) {
                    nodeItem.x = this.networkData.nodes[nodeItem.id].x;
                    nodeItem.y = this.networkData.nodes[nodeItem.id].y;
                }
            }

            if (this.networkData.nodeLevelOptions && Object.keys(this.networkData.nodeLevelOptions).length &&
                this.networkData.nodeLevelOptions[nodeItem.id]) {
                this.networkData.nodeLevelOptions[nodeItem.id].forEach(option => {
                    nodeLevelItem[option.key] = option.value;
                });
            }
            datasetNodes.push(Object.assign(nodeLevelItem, nodeItem));
            if (!this.networkData.edgeDatasetTitle) {
                datasetEdges.push({
                    from: item[this.networkData.fromIds],
                    to: item[this.networkData.toIds]
                });
            }
        });

        if (this.networkData.edgeDatasetTitle) {
            const datasetInstanceSummary = {
                datasetInstanceId: this.networkData.edgeDatasetId,
                datasourceInstanceKey: this.networkData.edgeDatasourceKey,
                transformationInstances: [],
                parameterValues: {},
                parameters: {}
            };

            const edgeDataset: any = await this.datasetService.evaluateDataset(
                datasetInstanceSummary,
                '0',
                '10000000'
            );
            edgeDataset.allData.forEach(item => {
                datasetEdges.push({
                    from: item[this.networkData.fromIds],
                    to: item[this.networkData.toIds]
                });
            });
        }

        datasetEdges.forEach(edge => {
            const edgeOptions = _.find(this.networkData.edgeLevelOptions, {from: edge.from, to: edge.to});
            if (edgeOptions && edgeOptions.options.length) {
                edgeOptions.options.forEach(option => {
                    edge[option.key] = option.value;
                });
            }
        });

        // create an array with nodes
        const nodes = new DataSet(datasetNodes);

        // create an array with edges
        const edges = new DataSet(datasetEdges);

        // create a network
        const container = document.getElementById(this.itemInstanceKey + 'VIS');
        const data = {
            nodes,
            edges
        };
        const options: any = {
            nodes: {
                font: {
                    multi: 'html'
                }
            },
            edges: {},
            groups: {},
            layout: {}
        };

        const mapOptionValue = (option: any, networkType: string) => {
            if (option && Object.keys(option).length) {
                const type = visNetworkOptions[networkType][option.key].type;
                let value = option.value;
                if (type === 'number') {
                    value = Number(value);
                } else if (type === 'boolean') {
                    value = value === 'true';
                }
                options[networkType][option.key] = value;
            }
        };

        if (this.networkData.nodeOptions) {
            this.networkData.nodeOptions.forEach(option => {
                mapOptionValue(option, 'nodes');
            });
        }

        if (this.networkData.edgeOptions) {
            this.networkData.edgeOptions.forEach(option => {
                mapOptionValue(option, 'edges');
            });
        }

        if (this.networkData.nodeGroupOptions && Object.keys(this.networkData.nodeGroupOptions).length) {
            Object.keys(this.networkData.nodeGroupOptions).forEach(groupKey => {
                this.networkData.nodeGroupOptions[groupKey].options.forEach(option => {
                    if (!options.groups[groupKey]) {
                        options.groups[groupKey] = {};
                    }
                    if (option && Object.keys(option).length) {
                        options.groups[groupKey][option.key] = option.value;
                    }
                });
            });
        }

        const network = new Network(container, data, options);
        this.networkData.network = network;
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

    public async setChartData() {
        if (this.dashboardItemType.xAxis && this.dashboardItemType.yAxis) {
            let datasetData = this.dataset.allData;
            if (this.dashboardItemType.limit) {
                datasetData = datasetData.slice(0, this.dashboardItemType.limit);
                if (this.dashboardItemType.combineRemaining && !this.dashboardItemType.seriesColumn) {
                    const remainingData = await this.evaluateDataset(10000000, 0);

                    const combined = [];
                    for (let i = 0; i < remainingData.allData.length; i++) {
                        if (i > this.dashboardItemType.limit) {
                            combined.push(remainingData.allData[i]);
                        }
                    }

                    const pushObject = {};
                    pushObject[this.dashboardItemType.xAxis] = this.dashboardItemType.combineRemaingingLabel || 'Remaining';
                    pushObject[this.dashboardItemType.yAxis] = _.sumBy(combined, remainingItem => {
                        return Number(remainingItem[this.dashboardItemType.yAxis]);
                    });
                    datasetData.push(pushObject);
                }
            }

            let data: any;

            if (!this.dashboardItemType.seriesColumn) {
                if (this.dashboardItemType.type !== 'pie' && this.dashboardItemType.type !== 'doughnut') {
                    data = _.map(datasetData, item => {
                        return {x: item[this.dashboardItemType.xAxis], y: item[this.dashboardItemType.yAxis]};
                    });
                } else {
                    data = _.map(datasetData, item => {
                        return item[this.dashboardItemType.yAxis];
                    });
                }

                this.chartData = [
                    {
                        data,
                        label: _.find(this.filterFields, {name: this.dashboardItemType.xAxis}).title,
                        fill: !!this.dashboardItemType.fill,
                        borderColor: (this.dashboardItemType.type === 'pie' || this.dashboardItemType.type === 'doughnut') ? chroma('white').alpha(0.2).hex() : this.dashboardItemType.borderColor,
                        backgroundColor: this.dashboardItemType.type === 'line' ?
                            (Array.isArray(this.dashboardItemType.borderColor) ? _.map(this.dashboardItemType.borderColor, colour => {
                                return chroma(colour || 'black').alpha(0.5).hex();
                            }) : chroma(this.dashboardItemType.borderColor || 'black').alpha(0.5).hex()) : this.dashboardItemType.backgroundColor,
                        tension: 0.2,
                        pointBackgroundColor: this.dashboardItemType.borderColor ? this.dashboardItemType.borderColor[0] : null,
                        pointBorderColor: this.dashboardItemType.borderColor ? this.dashboardItemType.borderColor[0] : null
                    }
                ];
                this.dashboardItemType.labels = _.map(datasetData, item => {
                    return item[this.dashboardItemType.xAxis];
                });
            } else {
                const chartData = [];
                const series = _.uniq(_.map(datasetData, this.dashboardItemType.seriesColumn));
                series.forEach((value, index) => {
                    const seriesResults = _.filter(datasetData, allData => {
                        return allData[this.dashboardItemType.seriesColumn] === value;
                    });

                    chartData.push({
                        data: _.map(seriesResults, item => {
                            return {x: item[this.dashboardItemType.xAxis], y: item[this.dashboardItemType.yAxis]};
                        }),
                        label: value,
                        fill: !!this.dashboardItemType.fill,
                        borderColor: this.dashboardItemType.borderColor,
                        backgroundColor: this.dashboardItemType.type === 'line' ?
                            (Array.isArray(this.dashboardItemType.borderColor) ? _.map(this.dashboardItemType.borderColor, colour => {
                                return chroma(colour || 'black').alpha(0.5).hex();
                            }) : chroma(this.dashboardItemType.borderColor || 'black').alpha(0.5).hex()) : this.dashboardItemType.backgroundColor[index],
                        tension: 0.2,
                        pointBackgroundColor: this.dashboardItemType.borderColor[index],
                        pointBorderColor: this.dashboardItemType.borderColor[index]
                    });
                });
                this.chartData = chartData;
                this.dashboardItemType.labels = _.uniq(_.map(datasetData, item => {
                    return item[this.dashboardItemType.xAxis];
                }));
            }

            if (this.dashboardItemType.trendLine) {
                let trendLine = [];
                let trendLabel = 'Trend';
                if (this.dashboardItemType.trendLine === 'average') {
                    trendLabel = 'Average';
                    const trendData = [];
                    this.chartData.forEach(dataItem => {
                        trendData.push(_.map(dataItem.data, 'y'));
                    });
                    const average = _.flatMap(trendData).reduce((p, c) => p + c, 0) / _.flatMap(trendData).length;
                    trendLine = _.fill(_.range(0, _.flatMap(trendData).length), average, 0, _.flatMap(trendData).length);
                } else if (this.dashboardItemType.trendLine === 'logarithmic' ||
                    this.dashboardItemType.trendLine === 'linear' ||
                    this.dashboardItemType.trendLine === 'power' ||
                    this.dashboardItemType.trendLine === 'exponential') {

                    const trendLineData = _.map(this.dataset.allData, (item, index) => {
                        return {x: index + 1, y: item[this.dashboardItemType.yAxis]};
                    }).filter(({x, y}) => {
                        return (
                            typeof x === typeof y &&  // filter out one string & one number
                            !isNaN(x) &&              // filter out `NaN`
                            !isNaN(y) &&
                            Math.abs(x) !== Infinity &&
                            Math.abs(y) !== Infinity
                        );
                    }).map(({x, y}) => {
                        return [x, y];
                    });

                    const trendLineResults = regression[this.dashboardItemType.trendLine](trendLineData);
                    trendLine = trendLineResults.points.map(([x, y]) => {
                        return y;
                    });
                } else {
                    trendLine = _.map(this.dataset.allData, item => {
                        return item[this.dashboardItemType.trendLine];
                    });
                    trendLabel = _.find(this.filterFields, {name: this.dashboardItemType.trendLine}).title;
                }

                this.chartData.push({
                    type: 'line',
                    data: trendLine,
                    label: trendLabel,
                    borderColor: this.dashboardItemType.trendLineColour,
                    fill: false,
                    order: -1,
                    tension: 0.15,
                    pointBackgroundColor: this.dashboardItemType.trendLineColour,
                    pointBorderColor: this.dashboardItemType.trendLineColour
                });
            }
        }
    }

    public updateImageData() {
        this.imageData.source = this.imageData.column ? this.dataset.allData[0][this.imageData.column] : null;
    }

    public updateMetricDataValues() {
        if (this.metric.main) {
            this.metric.mainValue = _.isNaN(Number(this.dataset.allData[0][this.metric.main])) ? this.dataset.allData[0][this.metric.main] : Number(this.dataset.allData[0][this.metric.main]);
            this.metric.title = _.startCase(this.metric.main);
        }

        if (this.metric.subMetric) {
            this.metric.subValue = Number(this.dataset.allData[0][this.metric.subMetric]);
            this.metric.subTitle = _.startCase(this.metric.subMetric);
        }

        if (this.metric.showSubChange) {
            this.metric.difference = Math.abs(this.metric.mainValue - this.metric.subValue);
        }
    }

    public callToActionLink(cta, dataItem?) {
        let url: string = null;

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
            url = `/dashboards/view/${cta.value}${this.admin ? '?a=true&' : '?'}${urlParams}`;
        } else if (cta.type === 'custom') {
            url = cta.link;

            const data = dataItem || this.dashboard.layoutSettings.parameters;

            url = this.bindParametersInString(url, data);
            // Check if we have any column eg. [[ ]] values needing mapping
            url = this.mapColumnToValue(url, data);
        } else if (cta.type === 'feed') {
            url = `/dap-data-feeds/${cta.value}`;
        }

        if (url) {
            if (!cta.windowLocation || cta.windowLocation === 'EXISTING') {
                window.location.href = url;
            } else {
                const a = document.createElement('a');
                a.href = url;
                a.setAttribute('target', '_blank');
                a.setAttribute('rel', 'noopener noreferrer');
                a.click();
            }
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

        // If we change the table limit - we also want to save the dashboard automatically to keep the changes
        this.tabular.limit = this.limit;
        this.dashboardService.saveDashboard(this.dashboard);

        this.evaluate(true);
    }

    public sortHeader(columnObject) {
        const column = columnObject.name;
        let direction = '';

        const existingMultiSort = _.find(this.dashboardDatasetInstance.transformationInstances, {type: 'multisort'});

        let existingColumnIndex = -1;
        if (existingMultiSort) {
            existingColumnIndex = _.findIndex(existingMultiSort.config.sorts, {fieldName: column});
        }

        if (!existingMultiSort || existingColumnIndex === -1) {
            direction = 'asc';
        } else if (existingColumnIndex >= 0 && existingMultiSort.config.sorts[existingColumnIndex].direction === 'asc') {
            direction = 'desc';
        } else if (existingColumnIndex >= 0 && existingMultiSort.config.sorts[existingColumnIndex].direction === 'desc') {
            direction = '';
        } else if (existingColumnIndex >= 0 && !existingMultiSort.config.sorts[existingColumnIndex].direction) {
            direction = 'asc';
        }

        columnObject.direction = direction;

        if (existingMultiSort) {
            const existingIndex = _.findIndex(existingMultiSort.config.sorts, {fieldName: column});
            if (direction) {
                if (existingIndex > -1) {
                    existingMultiSort.config.sorts[existingIndex] = {fieldName: column, direction};
                } else {
                    existingMultiSort.config.sorts.push({fieldName: column, direction});
                }
            } else {
                existingMultiSort.config.sorts.splice(existingIndex, 1);
                if (!existingMultiSort.config.sorts.length) {
                    _.remove(this.dashboardDatasetInstance.transformationInstances, {type: 'multisort'});
                }
            }
        } else {
            if (direction) {
                this.dashboardDatasetInstance.transformationInstances.push(
                    {
                        type: 'multisort',
                        config: {
                            sorts: [{fieldName: column, direction}]
                        }
                    }
                );
            }
        }

        this.evaluate(true);
    }

    public changeDateType(event, parameter, value) {
        event.stopPropagation();
        event.preventDefault();
        parameter._dateType = value;
        this.reloadWidget();
    }

    public updatePeriodValue(value, period, parameter) {
        parameter.value = `${value}_${period}_AGO`;
        this.reloadWidget();
    }

    public booleanUpdate(event, parameter) {
        parameter.value = event.checked;
        this.reloadWidget();
    }

    public reloadWidget() {
        const parameterValues: any = {};
        _.forEach(this.general.widgetParameters, param => {
            parameterValues[param.name] = param.value;
        });

        this.evaluate();
    }

    private mapColumnToValue(searchString, data) {
        if (searchString) {
            const matches = searchString.match(/\[\[(.*?)\]\]/g) || [];
            matches.forEach(exp => {
                const expValue = exp.replace('[[', '').replace(']]', '');
                let value = data ? data[expValue] : null;
                if (value) {
                    value = _.isPlainObject(value) ? value.initialValue : value;
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
        this.hiddenColumns = {};

        return this.evaluateDataset().then(data => {
            if (Object.keys(this.tableCells).length) {
                Object.keys(this.tableCells).forEach(tableCell => {
                    if (tableCell !== 'column') {
                        data.allData.map((item, index) => {

                            if (!_.isUndefined(item[tableCell])) {
                                const cellData = this.tableCells[tableCell].data;
                                switch (this.tableCells[tableCell].type) {
                                    case 'number':
                                        const cellNumber = parseFloat(String(item[tableCell]));
                                        item[tableCell] = {
                                            cellValue: cellNumber.toFixed(cellData.decimal),
                                            initialValue: item[tableCell]
                                        };
                                        break;
                                    case 'currency':
                                        let cellCurrency: any = parseFloat(String(item[tableCell]));

                                        if (cellData.thousandsSeparator && (cellData.currency && cellData.currency.value)) {
                                            cellCurrency = cellCurrency.toLocaleString('en-GB', {
                                                style: 'currency',
                                                currency: cellData.currency.value,
                                                minimumFractionDigits: cellData.decimal || 0
                                            });
                                        } else {
                                            cellCurrency = cellCurrency.toFixed(cellData.decimal);
                                            if (cellData.thousandsSeparator) {
                                                cellCurrency = Number(cellCurrency).toLocaleString('en-GB', {
                                                    minimumFractionDigits: cellData.decimal || 0
                                                });
                                            }
                                            if (cellData.currency && cellData.currency.value) {
                                                cellCurrency = `${cellData.currency.symbol}${cellCurrency}`;
                                            }
                                        }

                                        item[tableCell] = {cellValue: cellCurrency, initialValue: item[tableCell]};
                                        break;
                                    case 'percentage':
                                        let cellPercent: any = Number(item[tableCell]);
                                        const formatter = new Intl.NumberFormat('en-GB', {
                                            style: 'percent',
                                            minimumFractionDigits: cellData.decimal || 0
                                        });
                                        cellPercent = formatter.format(cellPercent);

                                        if (!cellData.thousandsSeparator) {
                                            cellPercent = cellPercent.replace(',', '');
                                        }

                                        item[tableCell] = {cellValue: cellPercent, initialValue: item[tableCell]};
                                        break;
                                    case 'datetime':
                                        const dateMoment = moment(item[tableCell]);
                                        const dateFormat = cellData.dateFormat === 'null' ? '' : cellData.dateFormat;
                                        const timeFormat = cellData.timeFormat === 'null' ? '' : cellData.timeFormat;

                                        item[tableCell] = {
                                            cellValue: dateMoment.format(dateFormat + ' ' + timeFormat),
                                            initialValue: item[tableCell]
                                        };
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
                                        let linkValue = cellData.customLink;
                                        let anchor = '';
                                        if (cellData.linkType === 'custom') {
                                            const dataItem = data.allData[index] || this.dashboard.layoutSettings.parameters;

                                            linkValue = this.bindParametersInString(linkValue, dataItem);
                                            // Check if we have any column eg. [[ ]] values needing mapping
                                            linkValue = this.mapColumnToValue(linkValue, dataItem);
                                            anchor = `<a href="${linkValue}" target="_blank" class="text-cta hover:underline flex items-center">${item[tableCell]}</a>`;
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
                                            linkValue = `${this.router.url.split('/')[0]}/dashboards/view/${dashboardLink.value}${this.admin ? '?a=true&' : '?'}${urlParams}`;
                                            anchor = `<a href="${linkValue}" class="text-cta hover:underline flex items-center">${item[tableCell]}</a>`;
                                        } else {
                                            linkValue = linkValue.includes('http') ? linkValue : `http://${linkValue}`;
                                            anchor = `<a href="${linkValue}" target="_blank" class="text-cta hover:underline flex items-center">${item[tableCell]}</a>`;
                                        }

                                        item[tableCell] = {
                                            cellValue: anchor,
                                            initialValue: linkValue
                                        };
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
                                        item[tableCell] = {
                                            cellValue: this.sanitizer.sanitize(1, boundHTML),
                                            initialValue: originalValue
                                        };
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
            const returnedDataLength = this.dataset.allData.length;

            const existingMultiSort = _.find(this.dashboardDatasetInstance.transformationInstances, {type: 'multisort'});

            if (existingMultiSort && existingMultiSort.config.sorts.length) {
                existingMultiSort.config.sorts.forEach(sort => {
                    const column = _.find(this.dataset.columns, {name: sort.fieldName});
                    if (column) {
                        column.direction = sort.direction;
                    }
                });
            }

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

            setTimeout(() => {
                if (this.dashboardItemType.type === 'network') {
                    this.setupNetworkChart();
                }

                const element = document.getElementById(this.itemInstanceKey);
                for (const child of Array.from(element ? element.children : []) as any[]) {
                    if (child.classList.contains('item-container')) {
                        this.wordCloud.width = child.clientWidth - 40;
                        this.wordCloud.height = child.clientHeight - 40;
                    }
                }
                if (Object.keys(this.wordCloud).length) {
                    if (this.wordCloud.populationMethod === 'SINGLE') {
                        const rowData = this.dataset.allData[this.wordCloud.row];
                        if (rowData) {
                            const words = _.words(rowData[this.wordCloud.column]);
                            const count = _.countBy(words, _.identity);

                            const max = _.max(_.values(count));
                            const fontSize = ((this.wordCloud.height) * (this.wordCloud.width)) / 1000;

                            this.wordCloud.data = _.orderBy(_.uniq(words).map(word => {
                                return {text: word, value: ((count[word] / max) * _.min([fontSize, 120]))};
                            }), ['value'], ['desc']).slice(0, 100);
                        }
                    } else if (this.wordCloud.populationMethod === 'WHOLE') {
                        const maxFreq = _.maxBy(this.dataset.allData, item => {
                            return Number(item[this.wordCloud.frequency]);
                        })[this.wordCloud.frequency];
                        const maxWordWidth: number = _.max(_.map(this.dataset.allData, item =>
                            item[this.wordCloud.column].length * item[this.wordCloud.frequency] / maxFreq
                        ));
                        const fontSize = ((this.wordCloud.height) * (this.wordCloud.width)) / 3000;
                        this.wordCloud.data = _.orderBy(this.dataset.allData.map(item => {
                            const text = item[this.wordCloud.column];
                            const itemFrequency = item[this.wordCloud.frequency];

                            return {
                                text,
                                value: ((8 / maxWordWidth) * (Number(itemFrequency) / Number(maxFreq)) * _.min([fontSize, 400]))
                            };
                        }), ['value'], ['desc']).slice(0, 100);
                    }

                }
            }, 50);


            this.loadingItem = false;
            this.configureClass = false;
            this.setChartData();

            if (this.dashboard.alertsEnabled && !this.external) {
                if (this.dashboardDatasetInstance.alerts && this.dashboardDatasetInstance.alerts.length) {
                    if (this.dashboard.layoutSettings.parameters && Object.keys(this.dashboard.layoutSettings.parameters).length) {
                        if (Array.isArray(this.dashboardDatasetInstance.parameterValues)) {
                            this.dashboardDatasetInstance.parameterValues = {};
                        }
                        _.forEach(this.dashboard.layoutSettings.parameters, parameter => {
                            this.dashboardDatasetInstance.parameterValues[parameter.name] = parameter.value;
                        });
                    }
                    const dashboardInstance = _.cloneDeep(this.dashboardDatasetInstance);
                    dashboardInstance.parameterValues = this.getMappedParams(this.dashboardDatasetInstance);
                    this.alertService.processAlertsForDashboardDatasetInstance(dashboardInstance)
                        .then((res: any) => {
                            if (res && res.length) {
                                this.alert = true;
                                this.alertData = res;
                                if (itemElement) {
                                    itemElement.classList.add('alert');
                                    itemElement.parentElement.classList.add('alert');
                                }
                            } else {
                                this.resetAlertData(itemElement);
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

            this.endOfResults = returnedDataLength < this.limit;

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

    private reloadAlert(save = true) {
        const thisEl = document.getElementById(this.itemInstanceKey).closest('.grid-stack-item');
        const existingGrid = _.find(this.dashboard.layoutSettings.grid, grid => {
            return grid.content.includes(`id="${this.itemInstanceKey}"`);
        });
        if (thisEl && existingGrid) {
            this.grid.removeWidget(thisEl);
            this.grid.addWidget(existingGrid);
        }

        if (save) {
            this.dashboardService.saveDashboard(this.dashboard);
        }
    }

    private evaluateDataset(limit?, offset?) {
        const datasetInstanceSummary = this.prepareDatasetInstanceSummaryForEvaluation();

        this.hiddenColumns = {};

        this.externalError = false;
        this.quotaExceeded = null;

        if (this.external) {
            return this.externalService.evaluateDataset(
                this.dashboard.id,
                this.itemInstanceKey,
                datasetInstanceSummary.parameterValues,
                String(offset || this.offset),
                String(limit || this.limit),
                this.queryParams
            ).catch(e => {
                this.externalError = true;
            });
        } else {
            return this.datasetService.evaluateDataset(
                datasetInstanceSummary,
                String(offset || this.offset),
                String(limit || this.limit)
            ).catch(e => {
                if (e && e.error && e.error.message && e.error.message.includes('quota')) {
                    this.quotaExceeded = e.error.message;
                } else {
                    this.externalError = true;
                }
            });
        }
    }

    private prepareDatasetInstanceSummaryForEvaluation() {
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
                let value = param.value;
                if (param.type === 'date' || param.type === 'datetime') {
                    if (value && !value.includes('AGO')) {
                        value = moment(value).format('YYYY-MM-DD HH:mm:ss');
                    }
                }

                // Add in the widget specific parameter values if there are any
                if (this.general.widgetParameters && Object.keys(this.general.widgetParameters).length) {
                    const widgetParam = _.find(this.general.widgetParameters, {name});
                    if (widgetParam && widgetParam.value) {
                        value = widgetParam.value;
                    }
                }

                datasetInstanceSummary.parameterValues[name] = value;
            });
        }

        return datasetInstanceSummary;
    }
}
