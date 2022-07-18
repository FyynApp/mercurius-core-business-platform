import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
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

    initialize() {

        this.bgColorPreviewTarget.style.backgroundColor = this.bgColorHiddenInputTarget.value;
        this.textColorPreviewTarget.style.color = this.textColorHiddenInputTarget.value;

        this.bgColorOptionTargets.forEach(e => {
            e.classList.remove('border-8');
            e.classList.add('border-2');
            if (e.dataset['value'] === this.bgColorHiddenInputTarget.value) {
                e.classList.remove('border-2');
                e.classList.add('border-8');
            }
        });

        this.textColorOptionTargets.forEach(e => {
            e.classList.remove('border-8');
            e.classList.add('border-2');
            if (e.dataset['value'] === this.textColorHiddenInputTarget.value) {
                e.classList.remove('border-2');
                e.classList.add('border-8');
            }
        });

        super.initialize();
    }

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
            e.classList.remove('border-8');
            e.classList.add('border-2');
        });

        t.classList.remove('border-2');
        t.classList.add('border-8');

        this.textColorHiddenInputTarget.value = t.dataset['value'];

        this.textColorPreviewTarget.style.color = t.dataset['value'];
    }
}
