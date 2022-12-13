import http from 'k6/http';
import { sleep } from 'k6';

const chunkBins = [
    open('/Users/manuel/Dropbox/projects/Mercurius/Software and Systems/Infrastructure/Load Testing/Assets/chunk1.webm', 'b'),
    open('/Users/manuel/Dropbox/projects/Mercurius/Software and Systems/Infrastructure/Load Testing/Assets/chunk2.webm', 'b'),
    open('/Users/manuel/Dropbox/projects/Mercurius/Software and Systems/Infrastructure/Load Testing/Assets/chunk3.webm', 'b'),
    open('/Users/manuel/Dropbox/projects/Mercurius/Software and Systems/Infrastructure/Load Testing/Assets/chunk4.webm', 'b'),
    open('/Users/manuel/Dropbox/projects/Mercurius/Software and Systems/Infrastructure/Load Testing/Assets/chunk5.webm', 'b'),
    open('/Users/manuel/Dropbox/projects/Mercurius/Software and Systems/Infrastructure/Load Testing/Assets/chunk6.webm', 'b'),
    open('/Users/manuel/Dropbox/projects/Mercurius/Software and Systems/Infrastructure/Load Testing/Assets/chunk7.webm', 'b'),
    open('/Users/manuel/Dropbox/projects/Mercurius/Software and Systems/Infrastructure/Load Testing/Assets/chunk8.webm', 'b'),
    open('/Users/manuel/Dropbox/projects/Mercurius/Software and Systems/Infrastructure/Load Testing/Assets/chunk9.webm', 'b'),
    open('/Users/manuel/Dropbox/projects/Mercurius/Software and Systems/Infrastructure/Load Testing/Assets/chunk10.webm', 'b'),
    open('/Users/manuel/Dropbox/projects/Mercurius/Software and Systems/Infrastructure/Load Testing/Assets/chunk11.webm', 'b'),
    open('/Users/manuel/Dropbox/projects/Mercurius/Software and Systems/Infrastructure/Load Testing/Assets/chunk12.webm', 'b'),
    open('/Users/manuel/Dropbox/projects/Mercurius/Software and Systems/Infrastructure/Load Testing/Assets/chunk13.webm', 'b'),
];
export default function () {
    http.get('https://preprod.fyyn.io/api/extension/v1/account/session-info');
    sleep(sleep(Math.random() * 4));

    const res = http.post('https://preprod.fyyn.io/api/extension/v1/recordings/recording-sessions/');
    sleep(sleep(Math.random() * 5));

    for (let i = 0; i < chunkBins.length; i++) {
        sleep(5);
        http.post(
            res.json().settings.postUrl,
            chunkBins[i],
            { headers: { 'Content-Type': 'multipart/form-data; boundary=----WebKitFormBoundarysjaoFgqJEdiRW8ZK' } }
        );
    }
}
