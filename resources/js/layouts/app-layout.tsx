import { NotificationProvider } from '@/contexts/NotificationContext';
import AppLayoutTemplate from '@/layouts/app/app-header-layout';
import { type BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    title?: string;
}

export default ({ children, breadcrumbs, title, ...props }: AppLayoutProps) => {
    const {
        auth: { user },
    } = usePage().props;

    return (
        <NotificationProvider userId={user.id}>
            <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props} title={title}>
                {children}
            </AppLayoutTemplate>
        </NotificationProvider>
    );
};
