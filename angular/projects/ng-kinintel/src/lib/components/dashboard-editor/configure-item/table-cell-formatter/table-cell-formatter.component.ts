import {Component, Input, OnInit} from '@angular/core';
import * as moment_ from 'moment';
const moment = moment_;
import {DashboardService} from '../../../../services/dashboard.service';
import * as _ from 'lodash';

@Component({
    selector: 'ki-table-cell-formatter',
    templateUrl: './table-cell-formatter.component.html',
    styleUrls: ['./table-cell-formatter.component.sass']
})
export class TableCellFormatterComponent implements OnInit {

    @Input() type: string;
    @Input() data: any = {};
    @Input() currencies: any = [];
    @Input() openSide: any;
    @Input() dataset: any;
    @Input() dashboards: any = [];
    @Input() sharedDashboards: any = [];
    @Input() dashboardParameters: any = [];

    public dateFormats: any = [
        {
            title: 'None',
            value: null
        },
        {
            title: moment().format('dddd, D MMMM YYYY'),
            value: 'dddd, D MMMM YYYY'
        },
        {
            title: moment().format('ddd, D MMM YYYY'),
            value: 'ddd, D MMM YYYY'
        },
        {
            title: moment().format('D MMMM YYYY'),
            value: 'D MMMM YYYY'
        },
        {
            title: moment().format('D MMM YYYY'),
            value: 'D MMM YYYY'
        },
        {
            title: moment().format('D MMMM'),
            value: 'D MMMM'
        },
        {
            title: moment().format('MMM D'),
            value: 'MMM D'
        },
        {
            title: moment().format('MMMM YYYY'),
            value: 'MMMM YYYY'
        },
        {
            title: moment().format('MMMM'),
            value: 'MMMM'
        },
        {
            title: moment().format('YYYY'),
            value: 'YYYY'
        },
        {
            title: moment().format('D/M/YYYY'),
            value: 'D/M/YYYY'
        },
        {
            title: moment().format('DD/MM/YYYY'),
            value: 'DD/MM/YYYY'
        },
        {
            title: moment().format('YYYY-MM-DD'),
            value: 'YYYY-MM-DD'
        }
    ];
    public timeFormats: any = [
        {
            title: 'None',
            value: null
        },
        {
            title: moment().format('h a'),
            value: 'ha'
        },
        {
            title: moment().format('h:mm a'),
            value: 'h:mm a'
        },
        {
            title: moment().format('h:mm:ss a'),
            value: 'h:mm:ss a'
        },
        {
            title: moment().format('HH:mm'),
            value: 'HH:mm'
        },
        {
            title: moment().format('HH:mm:ss'),
            value: 'HH:mm:ss'
        }
    ];

    constructor(private dashboardService: DashboardService) {
    }

    ngOnInit(): void {
        if (this.data.linkType === 'dashboard' && this.data.dashboardLink) {
            this.dashboardParamUpdate(this.data.dashboardLink);
        }
    }

    public selectOption(c1: any, c2: any) {
        if (!c2) {
            return true;
        }
        return c1.value === c2.value;
    }

    public ctaSelectOption(c1: any, c2: any) {
        return c1 === c2;
    }

    public setDecimalValue(value) {
        if (value === 'undefined') {
            delete this.data.decimal;
        }
    }

    public async dashboardParamUpdate(selection) {
        if (!this.data.dashboardLinkParams || Array.isArray(this.data.dashboardLinkParams)) {
            this.data.dashboardLinkParams = {};
        }
        const dashboard: any = await this.dashboardService.getDashboard(selection.value);
        if (dashboard.layoutSettings.parameters) {
            this.dashboardParameters = _.values(dashboard.layoutSettings.parameters);

            setTimeout(() => {
                const el = document.getElementsByClassName('dashboard-param-pick').item(0);
                if (el) {
                    el.scrollIntoView();
                }
            }, 0);
        }
    }

}
