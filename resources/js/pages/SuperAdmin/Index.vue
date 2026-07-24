<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import { show, toggleStatus } from '@/routes/super-admin';
import type { Tenant } from '@/types/auth';

type TenantWithStats = Tenant & {
    students_count: number;
    teachers_count: number;
    guardians_count: number;
};

defineProps<{
    tenants: TenantWithStats[];
}>();

defineOptions({
    layout: SuperAdminLayout,
});
</script>

<template>
    <Head title="Panel Super Admin" />

    <div class="flex flex-col gap-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Daftar Lembaga</h1>
            <p class="text-sm text-muted-foreground">
                Semua lembaga yang terdaftar di SantriQ.
            </p>
        </div>

        <div class="overflow-x-auto rounded-md border bg-card">
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b bg-muted/50 text-xs font-medium text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Subdomain</th>
                        <th class="px-4 py-3 text-right">Santri</th>
                        <th class="px-4 py-3 text-right">Pengajar</th>
                        <th class="px-4 py-3 text-right">Wali</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="tenant in tenants"
                        :key="tenant.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-medium">
                            <Link
                                :href="show(tenant.id)"
                                class="hover:underline"
                            >
                                {{ tenant.name }}
                            </Link>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ tenant.subdomain }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ tenant.students_count }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ tenant.teachers_count }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ tenant.guardians_count }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                :variant="
                                    tenant.suspended_at
                                        ? 'destructive'
                                        : 'secondary'
                                "
                            >
                                {{
                                    tenant.suspended_at ? 'Disuspend' : 'Aktif'
                                }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                as-child
                                :variant="
                                    tenant.suspended_at
                                        ? 'default'
                                        : 'destructive'
                                "
                                size="sm"
                            >
                                <Link
                                    :href="toggleStatus(tenant.id)"
                                    method="patch"
                                >
                                    {{
                                        tenant.suspended_at
                                            ? 'Aktifkan'
                                            : 'Suspend'
                                    }}
                                </Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="tenants.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Belum ada lembaga terdaftar.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
