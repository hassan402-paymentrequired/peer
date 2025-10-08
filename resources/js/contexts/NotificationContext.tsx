import { createContext, ReactNode, useContext, useEffect, useState } from 'react';

interface Notification {
    id: number;
    title: string;
    message: string;
    type: string;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    data: any;
    read_at: string | null;
    created_at: string;
}

interface NotificationContextType {
    notifications: Notification[];
    unreadCount: number;
    addNotification: (notification: Notification) => void;
    markAsRead: (notificationId: number) => void;
    markAllAsRead: () => void;
    removeNotification: (notificationId: number) => void;
    showToast: (message: string, type?: 'success' | 'error' | 'info') => void;
    toasts: Toast[];
    removeToast: (id: string) => void;
}

interface Toast {
    id: string;
    message: string;
    type: 'success' | 'error' | 'info';
    timestamp: number;
}

const NotificationContext = createContext<NotificationContextType | undefined>(undefined);

interface NotificationProviderProps {
    children: ReactNode;
    userId?: number;
}

export function NotificationProvider({ children, userId }: NotificationProviderProps) {
    const [notifications, setNotifications] = useState<Notification[]>([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [toasts, setToasts] = useState<Toast[]>([]);

    

    // Fetch initial notifications and unread count
    useEffect(() => {
        if (!userId) return;

        fetchNotifications();
        fetchUnreadCount();
    }, [userId]);

    const fetchNotifications = async () => {
        try {
            const response = await fetch('/notifications');
            const data = await response.json();
            setNotifications(data.data || []);
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        }
    };

    const fetchUnreadCount = async () => {
        try {
            const response = await fetch('/notifications/unread-count');
            const data = await response.json();
            setUnreadCount(data.unread_count || 0);
        } catch (error) {
            console.error('Failed to fetch unread count:', error);
        }
    };

    const addNotification = (notification: Notification) => {
        setNotifications((prev) => [notification, ...prev]);
        if (!notification.read_at) {
            setUnreadCount((prev) => prev + 1);
        }
    };

    const markAsRead = async (notificationId: number) => {
        try {
            await fetch('/notifications/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ notification_id: notificationId }),
            });

            // Update local state
            setNotifications((prev) => prev.map((notif) => (notif.id === notificationId ? { ...notif, read_at: new Date().toISOString() } : notif)));

            setUnreadCount((prev) => Math.max(0, prev - 1));
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    };

    const markAllAsRead = async () => {
        try {
            await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            // Update local state
            setNotifications((prev) => prev.map((notif) => ({ ...notif, read_at: new Date().toISOString() })));

            setUnreadCount(0);
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    };

    const removeNotification = async (notificationId: number) => {
        try {
            await fetch('/notifications/delete', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ notification_id: notificationId }),
            });

            // Update local state
            const notification = notifications.find((n) => n.id === notificationId);
            setNotifications((prev) => prev.filter((notif) => notif.id !== notificationId));

            if (notification && !notification.read_at) {
                setUnreadCount((prev) => Math.max(0, prev - 1));
            }
        } catch (error) {
            console.error('Failed to delete notification:', error);
        }
    };

    const showToast = (message: string, type: 'success' | 'error' | 'info' = 'info') => {
        const id = Math.random().toString(36).substr(2, 9);
        const toast: Toast = {
            id,
            message,
            type,
            timestamp: Date.now(),
        };

        setToasts((prev) => [...prev, toast]);

        // Auto-remove toast after 5 seconds
        setTimeout(() => {
            removeToast(id);
        }, 5000);
    };

    const removeToast = (id: string) => {
        setToasts((prev) => prev.filter((toast) => toast.id !== id));
    };

    const value: NotificationContextType = {
        notifications,
        unreadCount,
        addNotification,
        markAsRead,
        markAllAsRead,
        removeNotification,
        showToast,
        toasts,
        removeToast,
    };

    return <NotificationContext.Provider value={value}>{children}</NotificationContext.Provider>;
}

export function useNotifications() {
    const context = useContext(NotificationContext);
    if (context === undefined) {
        throw new Error('useNotifications must be used within a NotificationProvider');
    }
    return context;
}
