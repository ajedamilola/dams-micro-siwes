import { useEffect, useState } from 'react';
import type { ThemePreference } from '@/components/arc/user-menu/user-menu';

export type { ThemePreference };

/**
 * Page theme preference driving Arc's data-theme on <html>.
 * "system" follows the operating system preference live.
 */
export function useThemePreference() {
    const [preference, setPreference] = useState<ThemePreference>('system');

    useEffect(() => {
        if (typeof window === 'undefined' || typeof document === 'undefined') {
            return undefined;
        }
        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const apply = (): void => {
            document.documentElement.dataset.theme =
                preference === 'system'
                    ? media.matches
                        ? 'dark'
                        : 'light'
                    : preference;
        };
        apply();
        if (preference === 'system') {
            media.addEventListener('change', apply);
            return () => media.removeEventListener('change', apply);
        }
        return undefined;
    }, [preference]);

    return { preference, setPreference };
}
