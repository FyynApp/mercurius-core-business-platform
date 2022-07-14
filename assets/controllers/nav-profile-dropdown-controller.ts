import { Controller } from '@hotwired/stimulus';
import { useClickOutside } from 'stimulus-use';

export default class extends Controller {
    static targets = ['dropdown'];

    isOpen: boolean;
    dropdownTarget: HTMLDivElement;

    initialize() {
        this.isOpen = false;
    }

    connect() {
        useClickOutside(this);
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

    clickOutside(event) {
        if (this.isOpen) {
            this.dropdownTarget.classList.add('hidden');
            this.isOpen = false;
        }
    }
}
