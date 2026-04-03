(function () {
    const video = document.getElementById('heroVideo');
    const toggle = document.getElementById('heroVideoToggle');
    if (!video || !toggle) return;

    function setButtonState() {
        const isPaused = video.paused;
        toggle.setAttribute('aria-pressed', String(isPaused));
        toggle.textContent = isPaused ? 'Play background video' : 'Pause background video';
    }

    const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
        video.pause();
    }
    setButtonState();

    toggle.addEventListener('click', function () {
        if (video.paused) {
            const playPromise = video.play();
            if (playPromise && typeof playPromise.then === 'function') {
                playPromise.then(setButtonState).catch(setButtonState);
            } else {
                setButtonState();
            }
        } else {
            video.pause();
            setButtonState();
        }
    });

    video.addEventListener('play', setButtonState);
    video.addEventListener('pause', setButtonState);
})();
