<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import { index, toggleStatus } from '@/routes/super-admin';
import type { Tenant, User } from '@/types/auth';

type TenantDetail = Tenant & {
    students_count: number;
    teachers_count: number;
    guardians_count: number;
};

const props = defineProps<{
    tenant: TenantDetail;
    staff: User[];
}>();

defineOptions({
    layout: SuperAdminLayout,
});

function toggleTenant() {
    const question = props.tenant.suspended_at
        ? `Aktifkan kembali lembaga ${props.tenant.name}?`
        : `Suspend lembaga ${props.tenant.name}? Lembaga ini tidak akan bisa diakses sama sekali sampai diaktifkan kembali.`;

    if (confirm(question)) {
        router.patch(toggleStatus(props.tenant.id).url);
    }
}
</script>

<template>
    <Head :title="`Lembaga: ${tenant.name}`" />

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <Link
                    :href="index()"
                    class="text-sm text-muted-foreground hover:underline"
                >
                    &larr; Kembali ke daftar lembaga
                </Link>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ tenant.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ tenant.subdomain }}
                </p>
            </div>
            <Button
                :variant="tenant.suspended_at ? 'default' : 'destructive'"
                @click="toggleTenant"
            >
                {{
                    tenant.suspended_at ? 'Aktifkan Lembaga' : 'Suspend Lembaga'
                }}
            </Button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-md border bg-card p-4">
                <p class="text-sm text-muted-foreground">Santri</p>
                <p class="text-2xl font-bold">{{ tenant.students_count }}</p>
            </div>
            <div class="rounded-md border bg-card p-4">
                <p class="text-sm text-muted-foreground">Pengajar</p>
                <p class="text-2xl font-bold">{{ tenant.teachers_count }}</p>
            </div>
            <div class="rounded-md border bg-card p-4">
                <p class="text-sm text-muted-foreground">Wali Santri</p>
                <p class="text-2xl font-bold">{{ tenant.guardians_count }}</p>
            </div>
        </div>

        <div>
            <h2 class="mb-2 text-lg font-semibold">Staf Lembaga</h2>
            <div class="overflow-x-auto rounded-md border bg-card">
                <table class="w-full text-left text-sm">
                    <thead
                        class="border-b bg-muted/50 text-xs font-medium text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Peran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="member in staff" :key="member.id">
                            <td class="px-4 py-3 font-medium">
                                {{ member.name }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ member.email }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant="secondary">
                                    {{
                                        member.role === 'admin'
                                            ? 'Admin Lembaga'
                                            : 'Pengajar'
                                    }}
                                </Badge>
                            </td>
                        </tr>
                        <tr v-if="staff.length === 0">
                            <td
                                colspan="3"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                Belum ada staf terdaftar.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
