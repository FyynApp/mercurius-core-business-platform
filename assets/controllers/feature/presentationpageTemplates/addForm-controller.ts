import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['bgColorHiddenInput', 'bgColorOption'];

    bgColorHiddenInputTarget: HTMLInputElement;
    bgColorOptionTargets: HTMLDivElement[];

    selectBgColor(e: Event) {
        const t: HTMLElement = e.target as HTMLElement;

        this.bgColorOptionTargets.forEach(e => {
            e.classList.remove('border-4');
            e.classList.add('border-0');
        });

        t.classList.remove('border-0');
        t.classList.add('border-4');

        this.bgColorHiddenInputTarget.value = t.dataset['value'];
    }
}
