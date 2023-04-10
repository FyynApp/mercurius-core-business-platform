import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['stillImage', 'animatedImage'];

    stillImageTarget: HTMLDivElement;
    animatedImageTarget: HTMLDivElement;

    showAnimated() {
        this.stillImageTarget.classList.add('hidden');
        this.animatedImageTarget.classList.remove('hidden');
    }

    showStill() {
        this.stillImageTarget.classList.remove('hidden');
        this.animatedImageTarget.classList.add('hidden');
    }
}
