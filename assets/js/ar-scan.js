import * as THREE from 'three';
import { MindARThree } from 'https://cdn.jsdelivr.net/npm/mind-ar@1.2.5/dist/mindar-image-three.prod.js';

const root = document.querySelector('[data-ar-scan]');
if (root) {
    const config = JSON.parse(root.querySelector('[data-config]').textContent);
    const cameraView = root.querySelector('[data-camera-view]');
    const startButton = root.querySelector('[data-start]');
    const stopButton = root.querySelector('[data-stop]');
    const status = root.querySelector('[data-tracking-status]');
    const help = root.querySelector('[data-scan-help]');
    const card = root.querySelector('[data-info-card]');
    const videoDialog = root.querySelector('[data-video-dialog]');
    const videoFrame = root.querySelector('[data-video-frame]');
    const muteButton = root.querySelector('[data-mute]');
    const scannerBottom = root.querySelector('.scanner-bottom');
    const raycaster = new THREE.Raycaster();
    const pointer = new THREE.Vector2();
    muteButton.hidden = true;
    let mindar = null;
    let activeAnchor = null;
    let hotspotMeshes = [];
    let trackingStarted = false;
    let activeVideoId = null;
    let videoMuted = true;

    const closeCard = () => {
        card.hidden = true;
        videoFrame.src = '';
        if (videoDialog.open) videoDialog.close();
        else scannerBottom.append(muteButton);
    };

    const showHotspot = (id) => {
        const hotspot = config.hotspots.find((item) => String(item.id) === String(id));
        if (!hotspot || !activeAnchor?.visible) return;
        card.hidden = false;
        root.querySelector('[data-info-title]').textContent = hotspot.attraction_name || hotspot.label;
        root.querySelector('[data-info-description]').textContent = hotspot.short_description || 'Discover more about this place.';
        const image = root.querySelector('[data-info-image]');
        image.hidden = !hotspot.main_image;
        if (hotspot.main_image) image.src = hotspot.main_image;
        const map = root.querySelector('[data-map-link]');
        map.hidden = !hotspot.map_url;
        if (hotspot.map_url) map.href = hotspot.map_url;
        const video = root.querySelector('[data-video-button]');
        video.hidden = !hotspot.youtube_id;
        video.onclick = () => {
            activeVideoId = hotspot.youtube_id;
            videoMuted = true;
            muteButton.setAttribute('aria-pressed', 'true');
            muteButton.setAttribute('aria-label', 'Unmute video');
            muteButton.innerHTML = '<i class="fa-solid fa-volume-xmark"></i>';
            muteButton.hidden = false;
            videoFrame.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(activeVideoId)}?autoplay=1&mute=1`;
            videoDialog.prepend(muteButton);
            videoDialog.showModal();
        };
        const details = root.querySelector('[data-details-link]');
        details.hidden = !hotspot.attraction_slug;
        if (hotspot.attraction_slug) details.href = `${config.baseUrl}attraction.php?slug=${encodeURIComponent(hotspot.attraction_slug)}`;
    };

    const attachHotspots = () => {
        const ratio = config.posterHeight / config.posterWidth;
        hotspotMeshes = config.hotspots.map((hotspot) => {
            const geometry = new THREE.PlaneGeometry(Number(hotspot.width), Number(hotspot.height) * ratio);
            const material = new THREE.MeshBasicMaterial({ color: 0xc7ef69, transparent: true, opacity: 0.43, side: THREE.DoubleSide, depthTest: false });
            const mesh = new THREE.Mesh(geometry, material);
            mesh.position.set(Number(hotspot.x) + Number(hotspot.width) / 2 - .5, ratio * (.5 - Number(hotspot.y) - Number(hotspot.height) / 2), .02 + Number(hotspot.z_index) * .001);
            mesh.userData.hotspotId = hotspot.id;
            activeAnchor.group.add(mesh);
            return mesh;
        });
    };

    const start = async () => {
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            status.textContent = 'Camera access requires HTTPS (localhost is allowed).';
            return;
        }
        startButton.disabled = true;
        status.textContent = 'Starting camera…';
        try {
            mindar = new MindARThree({ container: cameraView, imageTargetSrc: config.targetUrl, uiLoading: 'no', uiScanning: 'no', uiError: 'no', maxTrack: 1, filterMinCF: .0001, filterBeta: 1000 });
            activeAnchor = mindar.addAnchor(0);
            attachHotspots();
            activeAnchor.onTargetFound = () => {
                status.textContent = 'POSTER DETECTED';
                help.hidden = true;
            };
            activeAnchor.onTargetLost = () => {
                status.textContent = 'SEARCHING FOR POSTER';
                help.hidden = false;
                closeCard();
            };
            const { renderer, scene, camera } = mindar;
            await mindar.start();
            trackingStarted = true;
            renderer.setAnimationLoop(() => renderer.render(scene, camera));
            root.classList.add('is-scanning');
            status.textContent = 'SEARCHING FOR POSTER';
            startButton.hidden = true;
            stopButton.hidden = false;
        } catch (error) {
            status.textContent = error.name === 'NotAllowedError' ? 'Camera access was denied. Allow camera permission in your browser settings and try again.' : `AR could not start: ${error.message}`;
            startButton.disabled = false;
            startButton.hidden = false;
            stopButton.hidden = true;
            mindar?.video?.srcObject?.getTracks().forEach((track) => track.stop());
            mindar?.video?.remove();
        }
    };

    const stop = () => {
        if (mindar && trackingStarted) {
            mindar.renderer.setAnimationLoop(null);
            mindar.stop();
            trackingStarted = false;
        }
        root.classList.remove('is-scanning');
        startButton.disabled = false;
        startButton.hidden = false;
        stopButton.hidden = true;
        status.textContent = 'SCANNER STOPPED';
        closeCard();
    };

    cameraView.addEventListener('click', (event) => {
        if (!activeAnchor?.visible) return;
        const bounds = mindar.renderer.domElement.getBoundingClientRect();
        pointer.x = ((event.clientX - bounds.left) / bounds.width) * 2 - 1;
        pointer.y = -((event.clientY - bounds.top) / bounds.height) * 2 + 1;
        raycaster.setFromCamera(pointer, mindar.camera);
        const match = raycaster.intersectObjects(hotspotMeshes)[0];
        if (match) showHotspot(match.object.userData.hotspotId);
    });
    startButton.addEventListener('click', start);
    stopButton.addEventListener('click', stop);
    root.querySelector('[data-close-card]').addEventListener('click', closeCard);
    root.querySelector('[data-video-close]').addEventListener('click', () => {
        videoFrame.src = '';
        videoDialog.close();
    });
    videoDialog.addEventListener('close', () => {
        videoFrame.src = '';
        activeVideoId = null;
        muteButton.hidden = true;
        muteButton.setAttribute('aria-pressed', 'true');
        muteButton.setAttribute('aria-label', 'Unmute video');
        muteButton.innerHTML = '<i class="fa-solid fa-volume-xmark"></i>';
        scannerBottom.append(muteButton);
    });
    muteButton.setAttribute('aria-label', 'Unmute video');
    muteButton.addEventListener('click', (event) => {
        videoMuted = !videoMuted;
        event.currentTarget.setAttribute('aria-pressed', String(videoMuted));
        event.currentTarget.innerHTML = videoMuted ? '<i class="fa-solid fa-volume-xmark"></i>' : '<i class="fa-solid fa-volume-high"></i>';
        event.currentTarget.setAttribute('aria-label', videoMuted ? 'Unmute video' : 'Mute video');
        if (activeVideoId) {
            videoFrame.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(activeVideoId)}?autoplay=1&mute=${videoMuted ? '1' : '0'}`;
        }
    });
}
