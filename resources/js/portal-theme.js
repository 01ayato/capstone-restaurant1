/**
 * Shared light/dark theme toggle for portal + admin.
 * Dark: expands from the button to the corners.
 * Light: collapses back into the button.
 */

const THEME_KEYS = ['sgTheme', 'portalTheme'];
const WAVE_MS = 700;
const WAVE_EASING = 'cubic-bezier(0.22, 1, 0.36, 1)';

function readStoredTheme() {
    try {
        for (const key of THEME_KEYS) {
            const value = localStorage.getItem(key);
            if (value === 'light' || value === 'dark') {
                return value;
            }
        }
    } catch {
        // Ignore storage failures.
    }
    return null;
}

function setPortalTheme(isDark) {
    document.documentElement.classList.toggle('dark', Boolean(isDark));
    try {
        const value = isDark ? 'dark' : 'light';
        localStorage.setItem('sgTheme', value);
        localStorage.setItem('portalTheme', value);
    } catch {
        // Ignore storage failures (private mode, etc.).
    }
}

function waveOrigin(event) {
    const target = event?.currentTarget;
    if (target && typeof target.getBoundingClientRect === 'function') {
        const rect = target.getBoundingClientRect();
        return {
            x: rect.left + rect.width / 2,
            y: rect.top + rect.height / 2,
        };
    }

    return {
        x: event?.clientX ?? window.innerWidth / 2,
        y: event?.clientY ?? 0,
    };
}

function endThemeTransitionClasses() {
    // Drop transition flags after paint so shell CSS transitions do not re-animate.
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            document.documentElement.classList.remove('theme-transitioning', 'theme-wave-reverse');
        });
    });
}

/**
 * @param {MouseEvent|PointerEvent} event
 * @param {boolean} nextDark
 * @returns {Promise<void>}
 */
export async function togglePortalThemeWithWave(event, nextDark) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const apply = () => setPortalTheme(nextDark);

    if (reducedMotion || typeof document.startViewTransition !== 'function') {
        document.documentElement.classList.add('theme-transitioning');
        apply();
        endThemeTransitionClasses();
        return;
    }

    const { x, y } = waveOrigin(event);
    const endRadius = Math.hypot(
        Math.max(x, window.innerWidth - x),
        Math.max(y, window.innerHeight - y),
    );

    document.documentElement.classList.add('theme-transitioning');
    document.documentElement.classList.toggle('theme-wave-reverse', !nextDark);

    try {
        const transition = document.startViewTransition(apply);
        await transition.ready;

        // Keep the VT group alive for the full wave (animation:none on layers
        // would otherwise finish the transition immediately and cause a blink).
        document.documentElement.style.setProperty('--theme-wave-ms', `${WAVE_MS}ms`);

        const animation = document.documentElement.animate(
            nextDark
                ? {
                      clipPath: [
                          `circle(0px at ${x}px ${y}px)`,
                          `circle(${endRadius}px at ${x}px ${y}px)`,
                      ],
                  }
                : {
                      clipPath: [
                          `circle(${endRadius}px at ${x}px ${y}px)`,
                          `circle(0px at ${x}px ${y}px)`,
                      ],
                  },
            {
                duration: WAVE_MS,
                easing: WAVE_EASING,
                fill: 'forwards',
                pseudoElement: nextDark
                    ? '::view-transition-new(root)'
                    : '::view-transition-old(root)',
            },
        );

        await Promise.allSettled([animation.finished, transition.finished]);
    } catch {
        apply();
    } finally {
        document.documentElement.style.removeProperty('--theme-wave-ms');
        endThemeTransitionClasses();
    }
}

window.togglePortalThemeWithWave = togglePortalThemeWithWave;
window.setPortalTheme = setPortalTheme;
window.readStoredTheme = readStoredTheme;
