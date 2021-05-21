import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';

@Injectable({
    providedIn: 'root'
})
export class DashboardService {

    constructor(private http: HttpClient) {
    }

    public getDashboard(id) {
        return this.http.get(`/account/dashboard/${id}`).toPromise();
    }

    public getDashboards(filterString = '', limit = '10', offset = '0') {
        return this.http.get('/account/dashboard', {
            params: {
                filterString, limit, offset
            }
        });
    }
}
