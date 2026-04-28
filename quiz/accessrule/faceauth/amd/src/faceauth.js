define([], function() {
    var state = {
        stream: null,
        timer: null,
        config: null,
        video: null,
        canvas: null,
        statusNode: null,
        pending: false
    };

    function setStatus(text, cssClass) {
        if (!state.statusNode) {
            return;
        }
        state.statusNode.textContent = text;
        state.statusNode.className = 'faceauth-status ' + (cssClass || '');
    }

    function ensureNodes() {
        if (state.video && state.canvas && state.statusNode) {
            return;
        }

        state.video = document.createElement('video');
        state.video.setAttribute('autoplay', 'autoplay');
        state.video.setAttribute('playsinline', 'playsinline');
        state.video.style.display = 'none';

        state.canvas = document.createElement('canvas');
        state.canvas.width = 640;
        state.canvas.height = 480;
        state.canvas.style.display = 'none';

        state.statusNode = document.createElement('div');
        state.statusNode.className = 'faceauth-status';
        state.statusNode.textContent = 'FaceAuth: initializing camera...';

        document.body.appendChild(state.video);
        document.body.appendChild(state.canvas);
        document.body.appendChild(state.statusNode);
    }

    function captureBase64() {
        var context = state.canvas.getContext('2d');
        context.drawImage(state.video, 0, 0, state.canvas.width, state.canvas.height);
        return state.canvas.toDataURL('image/jpeg', 0.75);
    }

    function sendSnapshot() {
        if (!state.stream || !state.config || state.pending) {
            return;
        }

        state.pending = true;

        var payload = {
            attempt_id: state.config.attemptId,
            quiz_id: state.config.quizId,
            cmid: state.config.courseModuleId,
            event_time: new Date().toISOString(),
            image_base64: captureBase64(),
            sesskey: state.config.sesskey
        };

        fetch(state.config.snapshotEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin'
        }).then(function(resp) {
            return resp.json();
        }).then(function(data) {
            if (!data || !data.status) {
                setStatus('FaceAuth: unknown response', 'faceauth-warn');
                return;
            }
            if (data.status === 'allow') {
                setStatus('FaceAuth: verification OK', 'faceauth-ok');
                return;
            }
            if (data.status === 'warn') {
                setStatus('FaceAuth: warning - ' + (data.code || 'check'), 'faceauth-warn');
                return;
            }
            if (data.status === 'block') {
                setStatus('FaceAuth: blocked - ' + (data.code || 'blocked'), 'faceauth-block');
                if (state.timer) {
                    window.clearInterval(state.timer);
                    state.timer = null;
                }
                return;
            }
            setStatus('FaceAuth: unsupported status', 'faceauth-warn');
        }).catch(function() {
            setStatus('FaceAuth: backend unavailable', 'faceauth-warn');
        }).finally(function() {
            state.pending = false;
        });
    }

    function startLoop() {
        var interval = parseInt(state.config.snapshotInterval, 10);
        if (!interval || interval < 5) {
            interval = 30;
        }
        sendSnapshot();
        state.timer = window.setInterval(sendSnapshot, interval * 1000);
    }

    function init(config) {
        state.config = config || {};
        ensureNodes();

        if (!state.config.sesskey) {
            setStatus('FaceAuth: session key missing', 'faceauth-block');
            return;
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('FaceAuth: camera API not supported', 'faceauth-block');
            return;
        }

        navigator.mediaDevices.getUserMedia({video: true, audio: false})
            .then(function(stream) {
                state.stream = stream;
                state.video.srcObject = stream;
                setStatus('FaceAuth: camera active', 'faceauth-ok');
                startLoop();
            })
            .catch(function() {
                setStatus('FaceAuth: camera permission denied', 'faceauth-block');
            });
    }

    return {
        init: init
    };
});
