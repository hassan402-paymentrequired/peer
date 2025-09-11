import { BreadcrumbItem } from "@/types";
import { dashboard } from '@/routes';

export const dashboardBreadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];