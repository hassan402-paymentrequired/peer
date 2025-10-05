import NotificationCenter from '@/components/notifications/NotificationCenter';
import ToastContainer from '@/components/notifications/ToastContainer';
import { NotificationProvider } from '@/contexts/NotificationContext';
import { ReactNode } from 'react';

interface AppLayoutProps {
    children: ReactNode;
    userId?: number;
}

export default function AppLayout({ children, userId }: AppLayoutProps) {
    return (
        <NotificationProvider userId={userId}>
            <div className="min-h-screen bg-gray-50">
                {/* Header with notification center */}
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

                {/* Main content */}
                <main className="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">{children}</main>

                {/* Toast notifications */}
                <ToastContainer />
            </div>
        </NotificationProvider>
    );
}
