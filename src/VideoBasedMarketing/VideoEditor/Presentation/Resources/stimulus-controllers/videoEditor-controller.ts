import { Controller } from '@hotwired/stimulus';
import { Timeline } from 'vis-timeline/esnext';
import { DataSet } from 'vis-data';

const items = new DataSet([
    {id: 1, content: 'item 1', start: '2014-04-20'},
    {id: 2, content: 'item 2', start: '2014-04-14'},
    {id: 3, content: 'item 3', start: '2014-04-18'},
    {id: 4, content: 'item 4', start: '2014-04-16', end: '2014-04-19'},
    {id: 5, content: 'item 5', start: '2014-04-25'},
    {id: 6, content: 'item 6', start: '2014-04-27', type: 'point'}
]);

export default class extends Controller {
    static targets = ['videoEditor'];

    videoEditorTarget: HTMLElement;

    setup() {

        const timeline = new Timeline(
            this.videoEditorTarget,
            items,
            {}
        );

        timeline.on('timechange', () => {});
    }
}
