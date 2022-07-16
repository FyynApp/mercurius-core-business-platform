import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['bgColor'];

    bgColorTarget: HTMLFormElement;

    selectBgColor(e: Event) {
        console.log('event', e);
        const t: HTMLElement = e.target as HTMLElement;
        console.dir('value', t.dataset['value']);
    }
}
