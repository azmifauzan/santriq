export type Tenant = {
    id: number;
    name: string;
    slug: string;
    address: string | null;
    phone: string | null;
    timezone: string;
    settings: Record<string, unknown> | null;
};

export type User = {
    id: number;
    tenant_id: number | null;
    name: string;
    email: string;
    role: 'admin' | 'pengajar';
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    tenant?: Tenant | null;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */
