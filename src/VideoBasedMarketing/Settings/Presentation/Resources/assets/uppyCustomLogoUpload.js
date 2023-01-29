import Uppy from '@uppy/core';
import Tus from '@uppy/tus';
import Dashboard from '@uppy/dashboard';
import de_DE from '@uppy/locales/lib/de_DE';
import en_US from '@uppy/locales/lib/en_US';

const appLocale = document.currentScript.getAttribute('data-lang');

let locale = en_US;
if (appLocale === 'de') {
    locale = de_DE;
}

const uppy = new Uppy({
    id: 'uppyCustomLogoUploadDashboard',
    autoProceed: true,
    allowMultipleUploadBatches: true,
    debug: true,
    restrictions: {
        maxFileSize: 10485760,
        minFileSize: null,
        maxTotalFileSize: 10485760,
        maxNumberOfFiles: 1,
        minNumberOfFiles: 1,
        allowedFileTypes: [
            'image/png',
            'image/gif',
            'image/jpeg',
            'image/webp',
            'image/svg+xml',
            'image/avif',
        ],
    },
    meta: {},
    onBeforeFileAdded: (currentFile, files) => currentFile,
    onBeforeUpload: (files, record) => {},
    locale: locale,
    infoTimeout: 5000
});

uppy.use(Tus, {
    endpoint: '/api/settings/logo-upload/v1/tus/',
    retryDelays: [0, 1000, 3000, 5000],
});

uppy.use(Dashboard, {
    id: 'uppyCustomLogoUploadDashboard',
    target: 'body',
    metaFields: [],
    trigger: '#uppyCustomLogoUploadDashboardOpenCta',
    inline: false,
    width: 750,
    height: 550,
    thumbnailWidth: 280,
    showLinkToFileUploadResult: false,
    showProgressDetails: false,
    hideUploadButton: false,
    hideRetryButton: false,
    hidePauseResumeButton: false,
    hideCancelButton: false,
    hideProgressAfterFinish: false,
    doneButtonHandler: () => {
        uppy.cancelAll();
        location.reload();
    },
    note: null,
    closeModalOnClickOutside: false,
    closeAfterFinish: false,
    disableStatusBar: false,
    disableInformer: false,
    disableThumbnailGenerator: false,
    disablePageScrollWhenModalOpen: true,
    animateOpenClose: true,
    fileManagerSelectionType: 'files',
    proudlyDisplayPoweredByUppy: false,
    onRequestCloseModal: () => location.reload(),
    showSelectedFiles: true,
    showRemoveButtonAfterComplete: false,
    showNativePhotoCameraButton: false,
    showNativeVideoCameraButton: false,
    browserBackButtonClose: false,
    theme: 'light',
    autoOpenFileEditor: false,
    disableLocalFiles: false
});
