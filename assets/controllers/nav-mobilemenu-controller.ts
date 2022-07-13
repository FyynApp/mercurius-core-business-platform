import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    isOpen: boolean;
    menuTarget: HTMLDivElement;

    initialize() {
        this.isOpen = false;
    }

    click() {
        if (this.isOpen) {
            this.menuTarget.classList.add('hidden');
            this.isOpen = false;
        } else {
            this.menuTarget.classList.remove('hidden');
            this.isOpen = true;
        }
    }
}
