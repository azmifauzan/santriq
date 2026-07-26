<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Award,
    BarChart3,
    CalendarCheck,
    CreditCard,
    FileText,
    GraduationCap,
    LayoutGrid,
    QrCode,
    ShieldCheck,
    UserCheck,
    UserCog,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';
import type { User } from '@/types/auth';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user as User | undefined);
const superAdminUrl = computed(() => page.props.superAdminUrl as string | null);

const mainNavItems = computed<NavItem[]>(() => {
    const isAdmin = currentUser.value?.role === 'admin';

    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Pindai QR',
            href: '/scan',
            icon: QrCode,
        },
        {
            title: 'Rekap Presensi',
            href: '/attendance',
            icon: CalendarCheck,
        },
        {
            title: 'Data Santri',
            href: '/students',
            icon: Users,
        },
        {
            title: 'Data Kelas',
            href: '/classrooms',
            icon: GraduationCap,
        },
        {
            title: 'Wali Santri',
            href: '/guardians',
            icon: UserCheck,
        },
        {
            title: 'Prestasi & Hafalan',
            href: '/achievements',
            icon: Award,
        },
        {
            title: 'SPP & Tagihan',
            href: '/invoices',
            icon: CreditCard,
        },
        {
            title: 'Perizinan',
            href: '/leave-requests',
            icon: FileText,
        },
        {
            title: 'Laporan Rekap',
            href: '/reports',
            icon: BarChart3,
        },
    ];

    if (isAdmin) {
        items.push({
            title: 'Pengajar',
            href: '/teachers',
            icon: UserCog,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu v-if="superAdminUrl">
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <a :href="superAdminUrl">
                            <ShieldCheck />
                            <span>Panel Super Admin</span>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavUser />
            <p
                class="px-2 pb-1 text-center text-xs text-muted-foreground group-data-[collapsible=icon]:hidden"
            >
                Managed by
                <a
                    href="https://satsetops.com"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="underline hover:text-foreground"
                    >SatsetOps</a
                >
            </p>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
