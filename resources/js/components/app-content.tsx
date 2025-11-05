import NotificationCenter from '@/components/notifications/NotificationCenter';
import ToastContainer from '@/components/notifications/ToastContainer';
import * as React from 'react';

interface AppContentProps extends React.ComponentProps<'main'> {
    variant?: 'header' | 'sidebar';
    title?: string
}

export function AppContent({ title = 'Starpick', children, ...props }: AppContentProps) {
    // if (variant === 'sidebar') {
    //     return <SidebarInset {...props}>{children}</SidebarInset>;
    // }

    return (
        <main className="flex h-screen w-full flex-1 flex-col  overflow-hidden overflow-y-auto bg-white lg:mx-auto lg:max-w-7xl" {...props}>

            <header className="border-b bg-stone-50">
                <div className="mx-auto  pr-4 sm:pr-6 lg:pr-8">
                    <div className="flex h-14 items-center justify-between">
                        <div className="flex items-center ml-3">
                            <h1 className="text-xl font-semibold text-gray-900 capitalize">{title}</h1>
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
