import { Component } from '@angular/core';
import {ExportProjectComponent as KAExportProjectComponent} from 'ng-kiniauth';


@Component({
  selector: 'ki-export-project',
  templateUrl: './export-project.component.html',
  styleUrls: ['./export-project.component.sass']
})
export class ExportProjectComponent extends KAExportProjectComponent{
    public alertGroupIds = {};
    public alertGroupUpdateindicators = {};


    export(exportConfig: any = {}) {

        exportConfig.includedAlertGroupIdUpdateIndicators = {};

        Object.keys(this.alertGroupIds).forEach(groupId => {
            if (this.alertGroupIds[groupId]){
                exportConfig.includedAlertGroupIdUpdateIndicators[groupId] = this.alertGroupUpdateindicators[groupId] || false;
            }
        });


        // Perform parent export
        super.export(exportConfig);
    }
}
