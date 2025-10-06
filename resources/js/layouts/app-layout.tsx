import AppLayoutTemplate from '@/layouts/app/app-header-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';
import { NotificationProvider } from '@/contexts/NotificationContext';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    title?: string;
}

export default ({ children, breadcrumbs, title, ...props }: AppLayoutProps) => (
    <NotificationProvider  userId={3}>
    <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props} title={title}>
        {children}
    </AppLayoutTemplate>
    </NotificationProvider>
);
