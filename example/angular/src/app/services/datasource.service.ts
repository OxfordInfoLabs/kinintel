import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';

@Injectable({
    providedIn: 'root'
})
export class DatasourceService {

    constructor(private http: HttpClient) {
    }

    public getDatasources() {
        return this.http.get('/account/datasource').toPromise();
    }

    public getDatasource(key) {
        return this.http.get('/account/datasource/' + key).toPromise();
    }
}
