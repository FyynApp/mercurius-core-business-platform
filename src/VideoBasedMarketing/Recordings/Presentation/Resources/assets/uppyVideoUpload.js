import Uppy from '@uppy/core';
import Tus from '@uppy/tus';
import Dashboard from '@uppy/dashboard';
import StatusBar from '@uppy/status-bar';
import de_DE from '@uppy/locales/lib/de_DE';
import en_US from '@uppy/locales/lib/en_US';

const appLocale = document.currentScript.getAttribute('data-lang');
const maxFileSize = document.currentScript.getAttribute('data-max-file-size');
const showInline = document.currentScript.getAttribute('data-show-inline');
const dashboardTarget = document.currentScript.getAttribute('data-dashboard-target');
const statusBarTarget = document.currentScript.getAttribute('data-status-bar-target');
const afterDoneLocation = document.currentScript.getAttribute('data-after-done-location');

let locale = en_US;
if (appLocale === 'de') {
    locale = de_DE;
}

const uppy = new Uppy({
    id: 'uppyVideoUpload',
    autoProceed: true,
    allowMultipleUploadBatches: true,
    debug: true,
    restrictions: {
        maxFileSize: maxFileSize,
        minFileSize: null,
        maxTotalFileSize: maxFileSize,
        maxNumberOfFiles: 1,
        minNumberOfFiles: 1,
        allowedFileTypes: ['video/*'],
    },
    meta: {},
    onBeforeFileAdded: (currentFile, files) => currentFile,
    onBeforeUpload: (files, record) => {},
    locale: locale,
    infoTimeout: 5000
});

uppy.use(Tus, {
    endpoint: '/api/recordings/video-upload/v1/tus/',
    retryDelays: [0, 1000, 3000, 5000],
    chunkSize: 41943040, // 40 MiB
});

uppy.use(Dashboard, {
    id: 'uppyVideoUploadDashboard',
    target: dashboardTarget,
    metaFields: [],
    trigger: '#uppyVideoUploadDashboardOpenCta',
    inline: showInline === 'true',
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
        window.location.href = afterDoneLocation;
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

uppy.use(StatusBar, {
    id: 'StatusBar',
    target: statusBarTarget,
    hideAfterFinish: false,
    showProgressDetails: false,
    hideUploadButton: true,
    hideRetryButton: true,
    hidePauseResumeButton: true,
    hideCancelButton: true,
    doneButtonHandler: null,
    locale: locale,
})

uppy.on('complete', () => {
    window.location.href = afterDoneLocation;
});
