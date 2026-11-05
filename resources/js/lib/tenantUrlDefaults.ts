import { router } from '@inertiajs/vue3';
import { setUrlDefaults } from '@/wayfinder';

interface PageWithSubdomain {
    props?: {
        subdomain?: string | null;
    };
}

function applyFromProps(props: PageWithSubdomain['props']): void {
    setUrlDefaults({ subdomain: props?.subdomain ?? undefined });
}

/**
 * Feeds the `subdomain` shared Inertia prop (see HandleInertiaRequests) into
 * Wayfinder's client-side URL defaults, so generated route functions like
 * `dashboard()` resolve the current lembaga without every call site having
 * to pass `{ subdomain }` explicitly.
 */
export function initializeTenantUrlDefaults(
    initialPage: PageWithSubdomain,
): void {
    applyFromProps(initialPage.props);

    router.on('navigate', (event) => {
        applyFromProps(
            (event as CustomEvent<{ page: PageWithSubdomain }>).detail?.page
                ?.props,
        );
    });
}
