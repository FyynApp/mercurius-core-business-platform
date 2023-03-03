import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    dragstart(event): void {
        event.dataTransfer.setData(
            'application/drag-key',
            event.target.getAttribute('data-video-folder-drag-and-drop-video-id')
        );

        const img = document.createElement('img');
        img.id = 'dragImage';
        img.src = event.target.getAttribute('data-video-folder-drag-and-drop-video-drag-image-url');
        img.classList.add('w-32');
        img.classList.add('rounded-md');
        img.classList.add('animate-rotate');
        img.style.position = 'absolute';
        img.style.width = '40px';
        img.style.top = '-1000px'; img.style.right = '-1000px';
        document.body.appendChild(img);
        event.dataTransfer.setDragImage(img, 0, 0);

        event.dataTransfer.effectAllowed = 'move';
    }

    dragover(event): boolean {
        const $dragoverTarget: HTMLElement = event.currentTarget;
        const videoFolderId = this.getVideoFolderId($dragoverTarget);

        const $videoFolder = document.getElementById(`video-folder-${videoFolderId}`);

        $videoFolder.classList.add('font-bold');

        document.getElementById('folder-icon-normal-' + videoFolderId).classList.add('hidden');
        document.getElementById('folder-icon-open-' + videoFolderId).classList.remove('hidden');

        event.preventDefault();
        return true;
    }

    dragenter(event): void {
        return;
    }

    dragleave(event): void {
        const $dragoverTarget: HTMLElement = event.currentTarget;
        const videoFolderId = this.getVideoFolderId($dragoverTarget);

        const $videoFolder = document.getElementById(`video-folder-${videoFolderId}`);

        $videoFolder.classList.remove('font-bold');

        document.getElementById('folder-icon-normal-' + videoFolderId).classList.remove('hidden');
        document.getElementById('folder-icon-open-' + videoFolderId).classList.add('hidden');

        event.preventDefault();
    }


    drop(event): void {
        const videoId = event.dataTransfer.getData('application/drag-key');
        const $dropTarget: HTMLElement = event.target;

        if (videoId === '') {
            return;
        }

        const $video = document.getElementById(`video-${videoId}`);

        const videoFolderId = this.getVideoFolderId($dropTarget);
        const $videoFolder = document.getElementById(`video-folder-${videoFolderId}`);

        const url = $videoFolder.getAttribute('data-move-video-into-folder-url');
        const csrfToken = $video.getAttribute('data-move-video-into-folder-csrf-token');

        const req = new XMLHttpRequest();
        req.open(
            'POST',
            url
                + '?videoId=' + encodeURIComponent(videoId)
                + '&videoFolderId=' + encodeURIComponent(videoFolderId)
                + '&_csrf_token=' + encodeURIComponent(csrfToken)
        );
        req.send();

        const $numberOfVideosInFolder = document.getElementById(
            `number-of-videos-in-folder-${videoFolderId}`
        );

        if ($numberOfVideosInFolder !== null) {
            $numberOfVideosInFolder.textContent = `${Number($numberOfVideosInFolder.textContent) + 1}`;
        }

        console.debug(
            'videoId', videoId,
            'videoFolderId', this.getVideoFolderId($dropTarget),
            '$dropTarget', event.target
        );

        $videoFolder.classList.remove('font-bold');
        document.getElementById('folder-icon-normal-' + videoFolderId).classList.remove('hidden');
        document.getElementById('folder-icon-open-' + videoFolderId).classList.add('hidden');

        $video.remove();

        event.preventDefault();
    }

    dragend(event): void {
    }

    getVideoFolderId(element: HTMLElement): string|null {
        if (element.hasAttribute('data-video-folder-drag-and-drop-video-folder-id')) {
            return element.getAttribute('data-video-folder-drag-and-drop-video-folder-id');
        }

        return this.getVideoFolderId(element.parentElement);
    }
}
