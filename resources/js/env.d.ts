import type { AxiosStatic } from 'axios';
import type { route as routeFn } from 'ziggy-js';

declare global {
    const route: typeof routeFn;

    interface User {
        id: number;
        name: string;
        email: string;
        email_verified_at: string | null;
    }

    interface Window {
        axios: AxiosStatic;
    }
}

declare module '@inertiajs/core' {
    interface InertiaConfig {
        flashDataType: {
            error?: string;
            success?: string;
        };
        sharedPageProps: {
            auth: {
                user: User | null;
            };
        };
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof routeFn;
    }
}

export {};
