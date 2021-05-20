import {Component, OnInit} from '@angular/core';
import {DatasetService} from '../../services/dataset.service';

@Component({
    selector: 'app-dataset',
    templateUrl: './dataset.component.html',
    styleUrls: ['./dataset.component.sass']
})
export class DatasetComponent implements OnInit {

    constructor(public datasetService: DatasetService) {
    }

    ngOnInit(): void {
    }

}
