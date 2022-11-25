import {Subject} from 'rxjs';

export class ActionEvent {

    public name: string;
    public title: number;
    public actionLabel: string;
    public completeLabel: string;
    public event: Subject<any>;
    public data: any;
    public disabled = false;
    public comparisonField: string;

    constructor(actionEvent?: any) {
        if (actionEvent) {
            this.name = actionEvent.name;
            this.title = actionEvent.title;
            this.actionLabel = actionEvent.actionLabel;
            this.completeLabel = actionEvent.completeLabel;
            this.event = actionEvent.event;
            this.data = actionEvent.data;
            this.disabled = actionEvent.disabled || false;
            this.comparisonField = actionEvent.comparisonField;
        }
    }

}
