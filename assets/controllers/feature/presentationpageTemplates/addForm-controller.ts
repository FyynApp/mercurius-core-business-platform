import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'bgColorHiddenInput', 'bgColorOption', 'bgColorPreview',
        'textColorHiddenInput', 'textColorOption', 'textColorPreview',
    ];

    bgColorHiddenInputTarget: HTMLInputElement;
    bgColorOptionTargets: HTMLDivElement[];
    bgColorPreviewTarget: HTMLDivElement;

    textColorHiddenInputTarget: HTMLInputElement;
    textColorOptionTargets: HTMLDivElement[];
    textColorPreviewTarget: HTMLDivElement;

    selectBgColor(e: Event) {
        const t: HTMLElement = e.target as HTMLElement;

        this.bgColorOptionTargets.forEach(e => {
            e.classList.remove('border-8');
            e.classList.add('border-2');
        });

        t.classList.remove('border-2');
        t.classList.add('border-8');

        this.bgColorHiddenInputTarget.value = t.dataset['value'];

        this.bgColorPreviewTarget.style.backgroundColor = t.dataset['value'];
    }

    selectTextColor(e: Event) {
        const t: HTMLElement = e.target as HTMLElement;

        this.textColorOptionTargets.forEach(e => {
            e.classList.remove('border-5');
            e.classList.add('border-1');
        });

        t.classList.remove('border-1');
        t.classList.add('border-5');

        this.textColorHiddenInputTarget.value = t.dataset['value'];

        this.textColorPreviewTarget.style.color = t.dataset['value'];
    }
}
