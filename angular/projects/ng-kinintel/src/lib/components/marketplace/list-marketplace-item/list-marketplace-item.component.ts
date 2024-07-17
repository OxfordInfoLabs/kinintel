import {Component} from '@angular/core';

@Component({
    selector: 'ki-list-marketplace-item',
    templateUrl: './list-marketplace-item.component.html',
    styleUrls: ['./list-marketplace-item.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class ListMarketplaceItemComponent {

    public listOnMarketplace = false;
    public marketplaceData: any = {};

    constructor() {
    }

}
