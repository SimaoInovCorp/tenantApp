// ── Domain types — mirror the backend API Resources exactly ─────────────────

export interface Tenant {
    id: number;
    name: string;
    slug: string;
    logo_url: string | null;
    primary_color: string | null;
    settings: Record<string, unknown> | null;
    owner_id: number;
    /** Present when loaded via the user's tenants relationship (pivot). */
    current_user_role: 'owner' | 'admin' | 'member' | null;
}

export interface Plan {
    id: number;
    name: string;
    slug: string;
    price: string; // decimal string from API, e.g. "29.00"
    interval: 'monthly' | 'yearly';
    trial_days: number;
    limits: Record<string, number>; // e.g. { max_users: 5 }
    features: string[];             // e.g. ['reports', 'api_access']
    is_active: boolean;
}

export interface Subscription {
    id: number;
    status: 'active' | 'trial' | 'canceled' | 'expired';
    starts_at: string; // ISO 8601
    ends_at: string;
    trial_ends_at: string | null;
    days_remaining: number;
    is_active: boolean;
    is_trial: boolean;
    is_expired: boolean;
    next_billing_date: string | null;
    prorated_amount: string | null;
    plan: Plan;
}

export interface PlanChangeLog {
    id: number;
    from_plan: Plan | null;
    to_plan: Plan;
    reason: string | null;
    changed_by: { id: number; name: string; email: string };
    created_at: string;
}

export interface OnboardingTask {
    id: number;
    task_key: 'branding' | 'invite_users' | 'set_permissions';
    is_completed: boolean;
    completed_at: string | null;
}

export interface TenantUser {
    id: number;
    name: string;
    email: string;
    role: 'owner' | 'admin' | 'member';
}
