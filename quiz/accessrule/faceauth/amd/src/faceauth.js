define([], function() {
    var state = {
        stream: null,
        timer: null,
        config: null,
        video: null,
        canvas: null,
        statusNode: null
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
        if (!state.stream || !state.config) {
            return;
        }

        var payload = {
            tenant_id: state.config.tenantId,
            attempt_id: state.config.attemptId,
            quiz_id: state.config.quizId,
            cmid: state.config.courseModuleId,
            user_hash: state.config.userHash,
            event_time: new Date().toISOString(),
            image_base64: captureBase64()
        };

        fetch(state.config.backendUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload),
            credentials: 'omit'
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
                return;
            }
            setStatus('FaceAuth: unsupported status', 'faceauth-warn');
        }).catch(function() {
            setStatus('FaceAuth: backend unavailable', 'faceauth-warn');
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
