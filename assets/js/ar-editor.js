const editor = document.querySelector('[data-ar-editor]');
if (editor) {
    const board = editor.querySelector('[data-board]');
    const posterImage = editor.querySelector('[data-poster-image]');
    const inspector = editor.querySelector('[data-inspector]');
    const saveButton = editor.querySelector('[data-save]');
    const compileButton = editor.querySelector('[data-compile]');
    const status = editor.querySelector('[data-editor-status]');
    const config = JSON.parse(editor.querySelector('[data-config]').textContent);
    let hotspots = config.hotspots;
    let selectedId = null;

    const normalize = (hotspot) => {
        hotspot.width = Math.min(1, Math.max(0.04, hotspot.width));
        hotspot.height = Math.min(1, Math.max(0.04, hotspot.height));
        hotspot.x = Math.min(1 - hotspot.width, Math.max(0, hotspot.x));
        hotspot.y = Math.min(1 - hotspot.height, Math.max(0, hotspot.y));
    };

    const render = () => {
        board.querySelectorAll('.hotspot-box').forEach((node) => node.remove());
        hotspots.forEach((hotspot, index) => {
            const box = document.createElement('button');
            box.type = 'button';
            box.className = `hotspot-box${hotspot.id === selectedId ? ' is-selected' : ''}`;
            box.style.left = `${hotspot.x * 100}%`;
            box.style.top = `${hotspot.y * 100}%`;
            box.style.width = `${hotspot.width * 100}%`;
            box.style.height = `${hotspot.height * 100}%`;
            box.textContent = hotspot.label || `Hotspot ${index + 1}`;
            box.setAttribute('aria-label', `Move ${box.textContent}`);
            const resize = document.createElement('span');
            resize.className = 'hotspot-resize';
            resize.setAttribute('aria-hidden', 'true');
            box.append(resize);
            box.addEventListener('click', () => select(hotspot.id));
            box.addEventListener('pointerdown', (event) => startDrag(event, hotspot, event.target === resize));
            board.append(box);
        });
        const active = hotspots.find((hotspot) => hotspot.id === selectedId);
        inspector.hidden = !active;
        if (active) {
            inspector.elements.label.value = active.label;
            inspector.elements.attraction_id.value = active.attraction_id ?? '';
            inspector.elements.video_url.value = active.video_url ?? '';
            inspector.elements.content_type.value = active.content_type;
        }
    };

    const select = (id) => {
        selectedId = id;
        render();
    };

    const startDrag = (event, hotspot, resizing) => {
        if (event.button !== 0) return;
        event.preventDefault();
        event.stopPropagation();
        select(hotspot.id);
        const bounds = board.getBoundingClientRect();
        const startX = event.clientX;
        const startY = event.clientY;
        const initial = { x: hotspot.x, y: hotspot.y, width: hotspot.width, height: hotspot.height };
        board.setPointerCapture(event.pointerId);
        const move = (pointerEvent) => {
            const dx = (pointerEvent.clientX - startX) / bounds.width;
            const dy = (pointerEvent.clientY - startY) / bounds.height;
            if (resizing) {
                hotspot.width = initial.width + dx;
                hotspot.height = initial.height + dy;
            } else {
                hotspot.x = initial.x + dx;
                hotspot.y = initial.y + dy;
            }
            normalize(hotspot);
            render();
        };
        const end = () => {
            window.removeEventListener('pointermove', move);
            window.removeEventListener('pointerup', end);
        };
        window.addEventListener('pointermove', move);
        window.addEventListener('pointerup', end, { once: true });
    };

    board.addEventListener('click', (event) => {
        if (event.target !== board && event.target !== posterImage) return;
        const bounds = board.getBoundingClientRect();
        const x = (event.clientX - bounds.left) / bounds.width;
        const y = (event.clientY - bounds.top) / bounds.height;
        const id = `new-${Date.now()}-${Math.random().toString(36).slice(2)}`;
        hotspots.push({ id, label: `Hotspot ${hotspots.length + 1}`, x: Math.max(0, Math.min(.85, x - .075)), y: Math.max(0, Math.min(.85, y - .075)), width: .15, height: .15, attraction_id: null, video_url: '', content_type: 'information' });
        select(id);
    });

    inspector.addEventListener('input', () => {
        const active = hotspots.find((hotspot) => hotspot.id === selectedId);
        if (!active) return;
        active.label = inspector.elements.label.value.trim();
        active.attraction_id = inspector.elements.attraction_id.value || null;
        active.video_url = inspector.elements.video_url.value.trim();
        active.content_type = inspector.elements.content_type.value;
        render();
    });

    editor.querySelector('[data-add]').addEventListener('click', () => {
        const id = `new-${Date.now()}-${Math.random().toString(36).slice(2)}`;
        hotspots.push({ id, label: `Hotspot ${hotspots.length + 1}`, x: .4, y: .4, width: .2, height: .2, attraction_id: null, video_url: '', content_type: 'information' });
        select(id);
    });
    editor.querySelector('[data-delete]').addEventListener('click', () => {
        hotspots = hotspots.filter((hotspot) => hotspot.id !== selectedId);
        selectedId = null;
        render();
    });

    saveButton.addEventListener('click', async () => {
        saveButton.disabled = true;
        status.textContent = 'Saving hotspot layout…';
        try {
            const response = await fetch(config.saveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': config.csrf },
                body: JSON.stringify({ hotspots: hotspots.map(({ id, ...hotspot }) => hotspot) }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Could not save the hotspot layout.');
            hotspots = result.hotspots;
            selectedId = null;
            render();
            status.textContent = 'Hotspots saved.';
        } catch (error) {
            status.textContent = error.message;
        } finally {
            saveButton.disabled = false;
        }
    });

    compileButton.addEventListener('click', async () => {
        compileButton.disabled = true;
        status.textContent = 'Preparing image target…';
        try {
            const { Compiler } = await import('https://cdn.jsdelivr.net/npm/mind-ar@1.2.5/dist/mindar-image.prod.js');
            const compiler = new Compiler();
            const progress = (percent) => { status.textContent = `Compiling target… ${Math.round(percent)}%`; };
            await compiler.compileImageTargets([posterImage], progress);
            const target = await compiler.exportData();
            status.textContent = 'Uploading compiled target…';
            const response = await fetch(config.compileUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/octet-stream', 'X-CSRF-Token': config.csrf },
                body: new Blob([target], { type: 'application/octet-stream' }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Target upload failed.');
            status.textContent = 'AR target ready.';
            editor.querySelector('[data-target-status]').textContent = 'READY';
        } catch (error) {
            status.textContent = `Target compilation failed: ${error.message}`;
        } finally {
            compileButton.disabled = false;
        }
    });

    render();
}
