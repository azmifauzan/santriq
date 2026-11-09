import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import GuardianLayout from '@/layouts/GuardianLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { initializeTenantUrlDefaults } from '@/lib/tenantUrlDefaults';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    withApp(_app, { page, ssr }) {
        if (!ssr) {
            initializeTenantUrlDefaults(page);
        }
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case [
                'Legal',
                'Welcome',
                'RequestFeature',
                'Feature',
                'Article',
                'ArticleIndex',
                'Tenant/Landing',
                'ErrorPage',
                'Reports/ReportCard',
            ].includes(name):
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('guardian/'):
                return GuardianLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
