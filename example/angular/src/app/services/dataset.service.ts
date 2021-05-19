import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';

@Injectable({
    providedIn: 'root'
})
export class DatasetService {

    constructor(private http: HttpClient) {
    }

    public evaluateDataset(datasetInstanceSummary) {
        return this.http.post('/account/dataset/evaluate', {
            datasetInstanceSummary
        }).toPromise();
    }
}
