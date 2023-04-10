import { startStimulusApp } from '@symfony/stimulus-bridge';
import Clipboard from 'stimulus-clipboard';

import NavMobileMenuSwitch
    from '../src/Shared/Presentation/Resources/stimulus-controllers/navMobileMenuSwitch-controller';

import NavProfileDropdown
    from '../src/Shared/Presentation/Resources/stimulus-controllers/navProfileDropdown-controller';

import VideoPreview
    from '../src/VideoBasedMarketing/Recordings/Presentation/Resources/stimulus-controllers/videoPreview-controller';

import VideoFolderDragAndDrop
    from '../src/VideoBasedMarketing/Recordings/Presentation/Resources/stimulus-controllers/videoFolderDragAndDrop-controller';

import VideoEditor
    from '../src/VideoBasedMarketing/VideoEditor/Presentation/Resources/stimulus-controllers/videoEditor-controller';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

app.register('clipboard', Clipboard);


app.register(
    'shared-navMobileMenuSwitch',
    NavMobileMenuSwitch
);

app.register(
    'shared-navProfileDropdown',
    NavProfileDropdown
);


app.register(
    'videoBasedMarketing-recordings-videoPreview',
    VideoPreview
);

app.register(
    'videoBasedMarketing-recordings-videoFolderDragAndDrop',
    VideoFolderDragAndDrop
);

app.register(
    'videoBasedMarketing-videoEditor-videoEditor',
    VideoEditor
);
