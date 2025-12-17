import {Component, Inject, OnInit, ViewChild} from '@angular/core';
import {
    MAT_DIALOG_DATA,
    MatDialog,
    MatDialogRef
} from '@angular/material/dialog';
import {
    SourceSelectorDialogComponent
} from '../../dashboard-editor/source-selector-dialog/source-selector-dialog.component';
import {DashboardService} from '../../../services/dashboard.service';
import * as lodash from 'lodash';

const _ = lodash.default;
import chroma from 'chroma-js';
import {DatasetService} from '../../../services/dataset.service';
import {EditDashboardAlertComponent} from '../configure-item/edit-dashboard-alert/edit-dashboard-alert.component';
import {
    DatasetFilterComponent
} from '../../dataset/dataset-editor/dataset-filters/dataset-filter/dataset-filter.component';
import {Router} from '@angular/router';
import {DatasourceService} from '../../../services/datasource.service';
import {BehaviorSubject, Subject} from 'rxjs';
import {ActionEvent} from '../../../objects/action-event';
import {BaseChartDirective} from 'ng2-charts';
import regression from 'regression';
import {ProjectService} from '../../../services/project.service';
import {scales} from 'chart.js';
import visNetworkOptions from './vis-network-options.json';
import {CreateDatasetComponent} from '../../dataset/create-dataset/create-dataset.component';
import {
    ChangeSourceWarningComponent
} from '../../data-explorer/change-source-warning/change-source-warning.component';
import {DatasetEditorComponent} from '../../dataset/dataset-editor/dataset-editor.component';

@Component({
    selector: 'ki-configure-item',
    templateUrl: './configure-item.component.html',
    styleUrls: ['./configure-item.component.sass'],
    host: { class: 'configure-dialog' },
    standalone: false
})
export class ConfigureItemComponent implements OnInit {

    @ViewChild(BaseChartDirective) chart: BaseChartDirective;
    @ViewChild('datasetEditorComponent') datasetEditorComponent: DatasetEditorComponent;

    public grid;
    public chartData: any;
    public metric: any = {};
    public textData: any = {};
    public networkData: any = {};
    public wordCloud: any = {};
    public imageData: any = {};
    public tabular: any = {};
    public tableCells: any = {};
    public general: any = {};
    public dependencies: any = {};
    public callToAction: any = {};
    public actionItem: any = {};
    public dashboard;
    public dashboardItemType;
    public dashboardDatasetInstance: any;
    public dashboards: any = [];
    public sharedDashboards: any = [];
    public privateDashboards: any = [];
    public dashboardParameters: any = [];
    public dashboardParamValues: any = [];
    public actionEvents: ActionEvent[] = [];
    public admin: boolean;
    public filterFields: any = [];
    public chartTypes = ['line', 'bar', 'pie', 'doughnut', 'scatter'];
    public metricFormats = ['Currency', 'Number', 'Percentage'];
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
    public filterJunction = {
        logic: 'AND',
        filters: [{
            lhsExpression: '',
            rhsExpression: '',
            filterType: ''
        }],
        filterJunctions: []
    };
    public columnFormats: any = [
        {
            title: 'Number',
            type: 'number'
        },
        {
            title: 'Currency',
            type: 'currency'
        },
        {
            title: 'Percentage',
            type: 'percentage'
        },
        {
            title: 'Date & Time',
            type: 'datetime'
        },
        {
            title: 'Comparison',
            type: 'comparison'
        },
        {
            title: 'Link',
            type: 'link'
        },
        {
            title: 'Custom',
            type: 'custom'
        },
        {
            title: 'Hide Column',
            type: 'hide'
        }
    ];
    public showAlertWarning = false;
    public dapFeeds: any = [];
    public sideOpen = false;
    public openSide = new BehaviorSubject(false);
    public dataset: any;
    public edgeDataset: any;
    public fullEdgeDataset: any;
    public _ = _;
    public docColumns = new Subject();
    public canSetAlerts = false;
    public colourPalettes: any = [];
    public visNetworkOptions = visNetworkOptions;
    public availableNodeOptions: string[] = [];
    public availableEdgeOptions: string[] = [];
    public datasetNodes: any = [];
    public datasetEdges: any = [];
    public nodeGroups: any;
    public widgetParameters: any = {};

    protected readonly Array = Array;
    protected readonly Object = Object;

    constructor(public dialogRef: MatDialogRef<ConfigureItemComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dialog: MatDialog,
                private dashboardService: DashboardService,
                private datasetService: DatasetService,
                private datasourceService: DatasourceService,
                private router: Router,
                private projectService: ProjectService) {
    }

    async ngOnInit(): Promise<any> {
        this.availableNodeOptions = Object.keys(this.visNetworkOptions.nodes);
        this.availableEdgeOptions = Object.keys(this.visNetworkOptions.edges);

        this.grid = this.data.grid;
        this.dashboard = this.data.dashboard;
        this.dashboardDatasetInstance = this.data.dashboardDatasetInstance;
        this.dashboardItemType = this.data.dashboardItemType;
        this.admin = !!this.data.admin;
        this.actionEvents = this.data.actionEvents || [];
        this.canSetAlerts = this.projectService.doesActiveProjectHavePrivilege('alertmanage');

        if (this.actionEvents.length) {
            this.columnFormats.push({
                title: 'Action',
                type: 'action'
            });
        }

        if (!this.dashboardItemType.colourMode) {
            this.dashboardItemType.colourMode = 'lrgb';
        }
        if (!this.dashboardItemType.borderColor || !Array.isArray(this.dashboardItemType.borderColor)) {
            this.dashboardItemType.borderColor = [_.isString(this.dashboardItemType.borderColor) ? this.dashboardItemType.borderColor : ''];
        }

        if (!this.dashboardDatasetInstance) {
            this.selectedDatasource();
        } else {
            this.loadDashboardItems();
        }

        this.dashboardService.getDashboards(
            '',
            '100',
            '0'
        ).toPromise().then(dashboards => {
            this.dashboards = dashboards;
        });

        this.dashboardService.getDashboards(
            '',
            '100',
            '0',
            null
        ).toPromise().then(dashboards => {
            this.sharedDashboards = dashboards;
        });

        this.dashboardService.getDashboards(
            '',
            '100',
            '0',
            0
        ).toPromise().then(dashboards => {
            this.privateDashboards = dashboards;
        });

        this.dapFeeds = await this.datasetService.getDatasets(
            '',
            '1000',
            '0',
            '',
            ''
        ).toPromise();

        this.openSide.subscribe((open: boolean) => {
            if (open) {
                document.getElementById('sidebarWrapper2')?.classList.add('z-20');
                document.getElementById('sidebarWrapper2')?.classList.remove('-z-10');
                this.docColumns.next(this.filterFields);
            } else {
                setTimeout(() => {
                    document.getElementById('sidebarWrapper2')?.classList.add('-z-10');
                    document.getElementById('sidebarWrapper2')?.classList.remove('z-20');
                }, 700);
            }
            this.sideOpen = open;
        });

        const project: any = await this.projectService.getProject(this.projectService.activeProject.getValue().projectKey);
        if (project.settings.palettes && project.settings.palettes.length) {
            this.colourPalettes = project.settings.palettes;
        }
    }

    public updateChart(newValue, index) {
        this.dashboardItemType.backgroundColor[index] = newValue;
        setTimeout(() => {
            this.chart.chart.update();
        }, 50);
    }

    public changeSource() {
        const dialogRef = this.dialog.open(CreateDatasetComponent, {
            width: '1200px',
            height: '800px',
            data: {
                admin: this.admin
            }
        });
        dialogRef.afterClosed().subscribe(res => {
            if (res) {
                const dialogRef2 = this.dialog.open(ChangeSourceWarningComponent, {
                    width: '700px',
                    height: '275px'
                });
                dialogRef2.afterClosed().subscribe(proceed => {
                    if (proceed) {
                        this.dashboardDatasetInstance.datasetInstanceId = res.datasetInstanceId;
                        this.dashboardDatasetInstance.datasourceInstanceKey = res.datasourceInstanceKey;
                        this.dashboardDatasetInstance.source = {
                            title: res.title,
                            datasetInstanceId: res.datasetInstanceId,
                            datasourceInstanceKey: res.datasourceInstanceKey,
                            type: null
                        };
                        const transformation = this.dashboardDatasetInstance.transformationInstances[0];
                        if (transformation) {
                            this.datasetEditorComponent.excludeUpstreamTransformations(transformation, true);
                        } else {
                            this.datasetEditorComponent.evaluateDataset(true);
                        }
                    }
                });
            }
        });
    }

    public selectedDatasource() {
        const dialogRef = this.dialog.open(SourceSelectorDialogComponent, {
            width: '1200px',
            height: '800px',
            data: {
                dashboard: this.dashboard,
                dashboardItemInstanceKey: this.data.itemInstanceKey,
                dashboardDatasetInstance: this.dashboardDatasetInstance || {
                    dashboardId: this.dashboard.id,
                    instanceKey: this.data.itemInstanceKey,
                    transformationInstances: [],
                    parameterValues: {},
                    parameters: []
                },
                admin: this.admin
            }
        });

        dialogRef.afterClosed().subscribe(dashboardDatasetInstance => {
            this.dashboardDatasetInstance = dashboardDatasetInstance;
            this.loadDashboardItems();
        });
    }

    public pickNetworkEdgeDataset() {
        const dialogRef = this.dialog.open(SourceSelectorDialogComponent, {
            width: '1200px',
            height: '800px',
            data: {
                dashboard: this.dashboard,
                dashboardItemInstanceKey: this.data.itemInstanceKey,
                dashboardDatasetInstance: this.dashboardDatasetInstance || {
                    dashboardId: this.dashboard.id,
                    instanceKey: this.data.itemInstanceKey,
                    transformationInstances: [],
                    parameterValues: {},
                    parameters: []
                },
                admin: this.admin
            }
        });

        dialogRef.afterClosed().subscribe(async dashboardDatasetInstance => {
            this.networkData.edgeDatasetId = dashboardDatasetInstance.datasetInstanceId;
            this.networkData.edgeDatasourceKey = dashboardDatasetInstance.datasourceInstanceKey;

            await this.loadEdgeDataset();

            this.networkData.edgeDatasetTitle = dashboardDatasetInstance.title || this.edgeDataset.instanceTitle;
        });
    }

    public async loadEdgeDataset() {
        const datasetInstanceSummary = {
            datasetInstanceId: this.networkData.edgeDatasetId,
            datasourceInstanceKey: this.networkData.edgeDatasourceKey,
            transformationInstances: [],
            parameterValues: {},
            parameters: {}
        };

        this.edgeDataset = await this.datasetService.evaluateDataset(
            datasetInstanceSummary,
            '0',
            '1'
        );
    }

    public clearEdgeDataset() {
        delete this.networkData.edgeDatasetTitle;
        delete this.networkData.edgeDatasetId;
        delete this.networkData.edgeDatasourceKey;

        this.edgeDataset = null;
    }

    public addGroupOption(nodeGroup: string) {
        if (!this.networkData.nodeGroupOptions) {
            this.networkData.nodeGroupOptions = {};
        }

        if (!this.networkData.nodeGroupOptions[nodeGroup]) {
            this.networkData.nodeGroupOptions[nodeGroup] = {options: []};
        }

        this.networkData.nodeGroupOptions[nodeGroup].options.unshift({});
    }

    public addOption(typeOptions: string) {
        if (!this.networkData[typeOptions] || !Array.isArray(this.networkData[typeOptions])) {
            this.networkData[typeOptions] = [];
        }

        this.networkData[typeOptions].unshift({});
    }

    public updateOptions(nodeOption: any, datasetType: string) {
        const option = nodeOption.key;
        const column = nodeOption.column;
        let value = nodeOption.value;
        let allData = this.dataset.allData;

        if (datasetType === 'datasetEdges') {
            allData = this.fullEdgeDataset ? this.fullEdgeDataset.allData : this.dataset.allData;
        }

        this[datasetType].forEach(item => {
            if (column) {
                const dataset = _.find(allData, {id: item.id});
                if (dataset) {
                    value = dataset[column];
                }
            }

            const existing = _.find(item.options, {key: option});
            if (existing) {
                existing.value = value;
            } else {
                item.options.unshift({key: option, value});
            }
        });
    }

    public dataLoaded(dataset) {
        this.dataset = dataset;
        this.filterFields = _.map(dataset.columns, column => {
            return {
                title: column.title,
                name: column.name
            };
        });
        this.updateMetricDataValues();
        this.setChartData();
        this.setNetworkChartData();
    }

    public async ctaUpdate(cta) {
        this.dashboardParameters = [];
        if (cta) {
            if (cta.type && cta.type === 'dashboard') {
                if (!cta.parameters) {
                    cta.parameters = {};
                }

                const dashboard: any = await this.dashboardService.getDashboard(cta.value);
                if (dashboard.layoutSettings.parameters) {
                    this.dashboardParameters = _.values(dashboard.layoutSettings.parameters);

                    setTimeout(() => {
                        const el = document.getElementsByClassName('dashboard-param-pick').item(0);
                        if (el) {
                            el.scrollIntoView();
                        }
                    }, 0);
                }
            } else {
                cta.parameters = {};
            }
        }
    }

    public setImageData(column) {
        this.imageData.source = column ? this.dataset.allData[0][column] : null;
    }

    public setColumn(column) {
        if (!this.tableCells[column] || Array.isArray(this.tableCells[column])) {
            this.tableCells[column] = {};
        }
    }

    public setColumnFormat(format) {
        if (format === 'undefined') {
            delete this.tableCells[this.tableCells.column].type;
        } else {
            if (!this.tableCells[this.tableCells.column].data || Array.isArray(this.tableCells[this.tableCells.column].data)) {
                this.tableCells[this.tableCells.column].data = {};
            }
        }
    }

    public editAlert(alert, index?) {
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
                    this.showAlertWarning = !this.dashboard.alertsEnabled;
                }
            }
        });
    }

    public deleteAlert(index) {
        const message = 'Are you sure you would like to delete this alert?';
        if (window.confirm(message)) {
            this.dashboardDatasetInstance.alerts.splice(index, 1);
        }
    }

    public getAlertDetails(alert) {
        let details = '';
        const filter = alert.filterTransformation.filters[0];

        if (filter) {
            const filterType = DatasetFilterComponent.getFilterType(filter.filterType);
            details += `<span class="font-medium">Where</span> ${filter.lhsExpression} `;
            details += filterType ? filterType.label : '';
            details += ` ${filter.rhsExpression}`;
        }

        const matchRule = alert.matchRuleConfiguration;
        if (matchRule && alert.matchRuleType === 'rowcount') {
            if (matchRule.matchType === 'equals') {
                details += ` <span class="font-medium">And</span> exactly ${matchRule.value} row${matchRule.value > 1 ? 's' : ''} returned`;
            } else if (matchRule.matchType === 'greater') {
                details += ` <span class="font-medium">And</span> more than ${matchRule.value} row${matchRule.value > 1 ? 's' : ''} returned`;
            } else if (matchRule.matchType === 'less') {
                details += ` <span class="font-medium">And</span> less than ${matchRule.value} row${matchRule.value > 1 ? 's' : ''} returned`;
            }
        }

        return details;
    }

    public setCallToActionItem(d1: any, d2: any) {
        if (!d2) {
            return true;
        }
        if (d2.type === 'custom') {
            return d1.type === 'custom';
        } else if (d2.type === 'dashboard' || d2.type === 'feed') {
            return d1.value === d2.value;
        }
    }

    public ctaSelectOption(c1: any, c2: any) {
        return c1 === c2;
    }

    public paletteSelectOption(c1: any, c2: any) {
        if (!c2) {
            return true;
        }
        return c1.name === c2.name;
    }

    public depSelection(c1: any, c2: any) {
        if (!c2) {
            return true;
        }
        return c1.type === c2.type;
    }

    public backgroundColourUpdate() {
        const colours = this.dashboardItemType.colourPalette ? this.dashboardItemType.colourPalette.colours : [
            this.dashboardItemType.backgroundColorFrom || 'black',
            this.dashboardItemType.backgroundColorTo || 'black'
        ];

        if (this.dashboardItemType.colourMode !== 'repeat') {
            const chromaScale = chroma.scale(colours);
            chromaScale.mode(this.dashboardItemType.colourMode);
            this.dashboardItemType.backgroundColor = chromaScale.colors(this.dashboardItemType.labels.length);
        } else {
            this.repeatColours(colours);
        }

        setTimeout(() => {
            this.setChartData();
        }, 50);
    }

    public async updateInstanceFilterFields(change, instanceKey) {
        if (change.type === 'MATCH') {
            const selectedDatasetInstance = _.find(this.dashboard.datasetInstances, {instanceKey});
            if (selectedDatasetInstance) {
                const mappedParams = {};
                _.forEach(selectedDatasetInstance.parameterValues, (value, key) => {
                    if (_.isString(value) && value.includes('{{')) {
                        const globalKey = value.replace('{{', '').replace('}}', '');
                        if (this.dashboard.layoutSettings.parameters && this.dashboard.layoutSettings.parameters[globalKey]) {
                            mappedParams[key] = this.dashboard.layoutSettings.parameters[globalKey].value;
                        }
                    } else {
                        mappedParams[key] = value;
                    }
                });

                const datasetInstanceSummary = {
                    datasetInstanceId: selectedDatasetInstance.datasetInstanceId,
                    datasourceInstanceKey: selectedDatasetInstance.datasourceInstanceKey,
                    transformationInstances: selectedDatasetInstance.transformationInstances,
                    parameterValues: mappedParams,
                    parameters: selectedDatasetInstance.parameters
                };

                this.dataset = await this.datasetService.evaluateDataset(datasetInstanceSummary, '0', '10');

                this.dependencies[instanceKey].filterFields = _.map(this.dataset.columns, column => {
                    return {
                        title: column.title,
                        name: column.name
                    };
                });
            }

        }
    }

    public async saveDashboard() {
        const grid = this.grid.save(true);
        if (!this.dashboard.layoutSettings) {
            this.dashboard.layoutSettings = {};
        }
        this.dashboard.layoutSettings.grid = grid;

        if (!this.dashboard.layoutSettings.charts) {
            this.dashboard.layoutSettings.charts = {};
        }

        this.dashboard.layoutSettings.charts[this.dashboardDatasetInstance.instanceKey] = this.dashboardItemType;

        this.mapComponentDataToDashboardInstance();

        if (!this.networkData.nodeLevelOptions || Array.isArray(this.networkData.nodeLevelOptions)) {
            this.networkData.nodeLevelOptions = {};
        }

        this.datasetNodes.forEach(node => {
            if (node.options && node.options.length) {
                this.networkData.nodeLevelOptions[node.id] = node.options;
            }
        });

        if (!this.networkData.edgeLevelOptions || !Array.isArray(this.networkData.edgeLevelOptions)) {
            this.networkData.edgeLevelOptions = [];
        }

        this.datasetEdges.forEach(edge => {
            if (edge.options && edge.options.length) {
                this.networkData.edgeLevelOptions.push(edge);
            }
        });

        // If there is an old instance remove it, and then add the new/updated one.
        _.remove(this.dashboard.datasetInstances, {instanceKey: this.dashboardDatasetInstance.instanceKey});
        _.remove(this.dashboardDatasetInstance.transformationInstances, {type: 'paging'});
        this.dashboard.datasetInstances.push(this.dashboardDatasetInstance);

        this.dialogRef.close(this.dashboardDatasetInstance);
    }

    public resetDefaultColours() {
        this.dashboardItemType.backgroundColorFrom = null;
        this.dashboardItemType.backgroundColorTo = null;
        this.setChartData();
    }

    public updateColourPalette() {
        if (this.dashboardItemType.colourPalette) {
            this.dashboardItemType.colourMode = 'repeat';
            this.repeatColours(this.dashboardItemType.colourPalette.colours);
            this.setChartData();
        } else {
            this.backgroundColourUpdate();
        }
    }

    public async setChartData() {
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
                        fill: !!this.dashboardItemType.fill,
                        borderColor: (this.dashboardItemType.type === 'pie' || this.dashboardItemType.type === 'doughnut') ? chroma('white').alpha(0.2).hex() : this.dashboardItemType.borderColor,
                        backgroundColor: (this.dashboardItemType.type === 'line' || this.dashboardItemType.type === 'scatter') ?
                            (Array.isArray(this.dashboardItemType.borderColor) ? _.map(this.dashboardItemType.borderColor, colour => {
                                return chroma(colour || 'black').alpha(0.5).hex();
                            }) : chroma(this.dashboardItemType.borderColor || 'black').alpha(0.5).hex()) : this.dashboardItemType.backgroundColor,
                        tension: 0.2,
                        pointBackgroundColor: this.dashboardItemType.borderColor ? this.dashboardItemType.borderColor[0] : null,
                        pointBorderColor: this.dashboardItemType.borderColor ? this.dashboardItemType.borderColor[0] : null
                    }
                ];
                this.dashboardItemType.labels = _.map(this.dataset.allData, item => {
                    return item[this.dashboardItemType.xAxis];
                });
            } else {
                const chartData = [];
                const series = _.uniq(_.map(this.dataset.allData, this.dashboardItemType.seriesColumn));
                series.forEach((value, index) => {
                    const seriesResults = _.filter(this.dataset.allData, allData => {
                        return allData[this.dashboardItemType.seriesColumn] === value;
                    });

                    chartData.push({
                        data: _.map(seriesResults, item => {
                            return {x: item[this.dashboardItemType.xAxis], y: item[this.dashboardItemType.yAxis]};
                        }),
                        label: value,
                        fill: !!this.dashboardItemType.fill,
                        borderColor: this.dashboardItemType.borderColor,
                        backgroundColor: (this.dashboardItemType.type === 'line' || this.dashboardItemType.type === 'scatter') ?
                            (Array.isArray(this.dashboardItemType.borderColor) ? _.map(this.dashboardItemType.borderColor, colour => {
                                return chroma(colour || 'black').alpha(0.5).hex();
                            }) : chroma(this.dashboardItemType.borderColor || 'black').alpha(0.5).hex()) : this.dashboardItemType.backgroundColor[index],
                        tension: 0.2,
                        pointBackgroundColor: this.dashboardItemType.borderColor[index],
                        pointBorderColor: this.dashboardItemType.borderColor[index]
                    });
                });
                this.chartData = chartData;
                this.dashboardItemType.labels = _.uniq(_.map(this.dataset.allData, item => {
                    return item[this.dashboardItemType.xAxis];
                }));
            }

            if (this.dashboardItemType.trendLine &&
                this.dashboardItemType.type !== 'pie' &&
                this.dashboardItemType.type !== 'doughnut') {

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
                            typeof x === typeof y &&
                            !isNaN(x) &&
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

    private mapLayoutSettingsToComponentData() {
        _.forEach(this.dashboard.layoutSettings, (data, key) => {
            if (key !== 'grid') {
                const defaultValue = _.isPlainObject(this[key]) ? {} : [];
                this[key] = Object.keys(data).length ?
                    (data[this.dashboardDatasetInstance.instanceKey] && Object.keys(data[this.dashboardDatasetInstance.instanceKey]).length ?
                        data[this.dashboardDatasetInstance.instanceKey] : defaultValue) : defaultValue;
            }
        });
    }

    private mapComponentDataToDashboardInstance() {
        const layoutSettings = ['metric', 'dependencies', 'tabular', 'tableCells', 'general', 'imageData', 'textData', 'callToAction', 'wordCloud', 'actionItem', 'networkData'];
        layoutSettings.forEach(setting => {
            if (!this.dashboard.layoutSettings[setting]) {
                this.dashboard.layoutSettings[setting] = {};
            }
            const defaultData = _.isPlainObject(this[setting]) ? {} : [];
            this.dashboard.layoutSettings[setting][this.dashboardDatasetInstance.instanceKey] = this[setting] || defaultData;
        });
    }

    private repeatColours(colours: string[]) {
        this.dashboardItemType.backgroundColor = [];
        this.dashboardItemType.borderColor = [];
        while (this.dashboardItemType.backgroundColor.length < this.dataset.allData.length) {
            for (const colour of colours) {
                this.dashboardItemType.backgroundColor.push(colour);
                if (this.dashboardItemType.backgroundColor.length === this.dataset.allData.length) {
                    break;
                }
            }
        }
        const dataLength = (this.dashboardItemType.type === 'line' || this.dashboardItemType.type === 'scatter') ? this.chartData.length : this.dataset.allData;
        while (this.dashboardItemType.borderColor.length < dataLength) {
            for (const colour of colours) {
                this.dashboardItemType.borderColor.push(colour);
                if (this.dashboardItemType.borderColor.length === dataLength) {
                    break;
                }
            }
        }
    }

    private loadDashboardItems() {
        if (this.dashboard.layoutSettings) {
            this.mapLayoutSettingsToComponentData();

            if (!this.general.parameterBar) {
                this.general.parameterBar = {};
            }

            if (this.dashboard.layoutSettings.parameters) {
                this.dashboardParamValues = _(this.dashboard.layoutSettings.parameters)
                    .filter('value')
                    .map('value')
                    .valueOf();
            }

            this.widgetParameters = _.cloneDeep(this.dashboard.layoutSettings.parameters);
            if (this.general.widgetParameters && Object.keys(this.general.widgetParameters).length) {
                _.forEach(this.general.widgetParameters, widgetParam => {
                    if (widgetParam.value) {
                        this.widgetParameters[widgetParam.name].value = widgetParam.value;
                    }
                });
            }

            const matchDependencies = _.filter(this.dependencies, (dep, key) => {
                if (dep.type === 'MATCH') {
                    dep.key = key;
                    return true;
                }
                return false;
            });
            matchDependencies.forEach(dep => {
                this.updateInstanceFilterFields(dep, dep.key);
            });

            if (this.tabular.cta) {
                this.ctaUpdate(this.tabular.cta);
            }
        }
    }

    public async setNetworkChartData(reload = false) {
        if (!reload && this.networkData.edgeDatasetTitle) {
            await this.loadEdgeDataset();
        }

        const nodeData = this.dataset.allData;

        this.datasetNodes = [];
        this.datasetEdges = [];

        nodeData.forEach(item => {
            let nodeOptions: any = [];
            if (this.networkData.nodeLevelOptions && Object.keys(this.networkData.nodeLevelOptions).length &&
                this.networkData.nodeLevelOptions[item[this.networkData.nodeIds]]) {
                nodeOptions = this.networkData.nodeLevelOptions[item[this.networkData.nodeIds]];
            }

            this.datasetNodes.push({
                id: item[this.networkData.nodeIds],
                label: item[this.networkData.nodeLabels],
                group: item[this.networkData.nodeGroups],
                options: nodeOptions
            });
            if (!this.networkData.edgeDatasetTitle) {
                this.datasetEdges.push({
                    from: item[this.networkData.fromIds],
                    to: item[this.networkData.toIds],
                    options: []
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

            this.fullEdgeDataset = await this.datasetService.evaluateDataset(
                datasetInstanceSummary,
                '0',
                '10000000'
            );

            this.fullEdgeDataset.allData.forEach(item => {
                this.datasetEdges.push({
                    from: item[this.networkData.fromIds],
                    to: item[this.networkData.toIds],
                    options: []
                });
            });
        }

        this.nodeGroups = _(this.datasetNodes)
            .filter('group')
            .map('group')
            .uniq()
            .valueOf();

        this.datasetEdges.forEach(edge => {
            const edgeOptions = _.find(this.networkData.edgeLevelOptions, {from: edge.from, to: edge.to});
            if (edgeOptions) {
                edge.options = edgeOptions.options;
            }
        });
    }
}
