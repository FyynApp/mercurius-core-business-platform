import { Controller } from '@hotwired/stimulus';
import { useClickOutside } from 'stimulus-use';

export default class extends Controller {
    static targets = [
        'menu'
    ];

    isOpen: boolean;
    menuTarget: HTMLDivElement;

    initialize() {
        this.isOpen = false;
    }

    connect() {
        useClickOutside(this);
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

    clickOutside(event) {
        if (this.isOpen) {
            this.menuTarget.classList.add('hidden');
            this.isOpen = false;
        }
    }
}
