import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dropdown'];

    initialize() {
        this.isOpen = false;
    }

    click() {
        if (this.isOpen) {
            this.dropdownTarget.classList.add('hidden');
            this.isOpen = false;
        } else {
            this.dropdownTarget.classList.remove('hidden');
            this.isOpen = true;
        }
    }
}
