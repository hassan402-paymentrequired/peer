import NotificationCenter from '@/components/notifications/NotificationCenter';
import ToastContainer from '@/components/notifications/ToastContainer';
import * as React from 'react';

interface AppContentProps extends React.ComponentProps<'main'> {
    variant?: 'header' | 'sidebar';
}

export function AppContent({ variant = 'header', children, ...props }: AppContentProps) {
    // if (variant === 'sidebar') {
    //     return <SidebarInset {...props}>{children}</SidebarInset>;
    // }

    return (
        <main className="flex h-screen w-full flex-1 flex-col gap-4 overflow-hidden overflow-y-auto bg-white lg:mx-auto lg:max-w-7xl" {...props}>
            <header className="border-b bg-white shadow-sm">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <div className="flex items-center">
                            <h1 className="text-xl font-semibold text-gray-900">Fantasy Sports App</h1>
                        </div>

                        {/* Notification Center in header */}
                        <div className="flex items-center space-x-4">
                            <NotificationCenter />
                        </div>
                    </div>
                </div>
            </header>
            {children}
            <ToastContainer />
        </main>
    );
}
