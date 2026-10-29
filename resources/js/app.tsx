import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';
import '@fontsource-variable/inter/index.css';
import '@fontsource-variable/geist/index.css';
import '@/components/arc/foundation.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * Arc drives its light and dark themes from data-theme on <html>.
 * Follow the operating system preference so Arc surfaces stay in sync
 * with the rest of the page.
 */
function syncTheme(): void {
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }
    const preference = window.matchMedia('(prefers-color-scheme: dark)');
    const apply = (): void => {
        document.documentElement.dataset.theme = preference.matches
            ? 'dark'
            : 'light';
    };
    apply();
    preference.addEventListener('change', apply);
}

syncTheme();

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent<{ default: ComponentType }>(
            `./pages/${name}.tsx`,
            import.meta.glob<{ default: ComponentType }>('./pages/**/*.tsx'),
        ).then((module) => module.default),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
