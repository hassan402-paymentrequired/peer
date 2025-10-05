import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useNotifications } from '@/contexts/NotificationContext';
import { Bell, Gift, Target, Trophy, X } from 'lucide-react';
import { useState } from 'react';

interface NotificationCenterProps {
    className?: string;
}

export default function NotificationCenter({ className = '' }: NotificationCenterProps) {
    const { notifications, unreadCount, markAsRead, markAllAsRead } = useNotifications();
    const [isOpen, setIsOpen] = useState(false);
    const [loading, setLoading] = useState(false);

    const getNotificationIcon = (type: string) => {
        switch (type) {
            case 'tournament_winner':
            case 'tournament_completed':
                return <Trophy className="h-5 w-5 text-yellow-500" />;
            case 'peer_winner':
            case 'peer_completed':
                return <Target className="h-5 w-5 text-blue-500" />;
            case 'prize_won':
                return <Gift className="h-5 w-5 text-green-500" />;
            default:
                return <Bell className="h-5 w-5 text-gray-500" />;
        }
    };

    const formatTimeAgo = (dateString: string) => {
        const date = new Date(dateString);
        const now = new Date();
        const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

        if (diffInMinutes < 1) return 'Just now';
        if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
        if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
        return `${Math.floor(diffInMinutes / 1440)}d ago`;
    };

    return (
        <div className={`relative ${className}`}>
            {/* Notification Bell */}
            <Button variant="ghost" size="sm" onClick={() => setIsOpen(!isOpen)} className="relative p-2">
                <Bell className="h-5 w-5" />
                {unreadCount > 0 && (
                    <Badge className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 p-0 text-xs text-white">
                        {unreadCount > 99 ? '99+' : unreadCount}
                    </Badge>
                )}
            </Button>

            {/* Notification Dropdown */}
            {isOpen && (
                <div className="absolute top-full right-0 z-50 mt-2 w-80 rounded-lg border border-gray-200 bg-white shadow-lg">
                    <div className="border-b border-gray-200 p-4">
                        <div className="flex items-center justify-between">
                            <h3 className="font-semibold text-gray-900">Notifications</h3>
                            <div className="flex items-center gap-2">
                                {unreadCount > 0 && (
                                    <Button variant="ghost" size="sm" onClick={markAllAsRead} className="text-xs">
                                        Mark all read
                                    </Button>
                                )}
                                <Button variant="ghost" size="sm" onClick={() => setIsOpen(false)}>
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div className="max-h-96 overflow-y-auto">
                        {notifications.length > 0 ? (
                            notifications.map((notification) => (
                                <div
                                    key={notification.id}
                                    className={`cursor-pointer border-b border-gray-100 p-4 transition-colors hover:bg-gray-50 ${
                                        !notification.read_at ? 'bg-blue-50' : ''
                                    }`}
                                    onClick={() => markAsRead(notification.id)}
                                >
                                    <div className="flex items-start gap-3">
                                        <div className="mt-1 flex-shrink-0">{getNotificationIcon(notification.type)}</div>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <p className="text-sm font-medium text-gray-900">{notification.title}</p>
                                                {!notification.read_at && <div className="h-2 w-2 rounded-full bg-blue-500"></div>}
                                            </div>
                                            <p className="mt-1 text-sm text-gray-600">{notification.message}</p>
                                            <p className="mt-2 text-xs text-gray-400">{formatTimeAgo(notification.created_at)}</p>
                                        </div>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="p-8 text-center text-gray-500">
                                <Bell className="mx-auto mb-2 h-8 w-8 text-gray-300" />
                                <p>No notifications yet</p>
                            </div>
                        )}
                    </div>

                    {notifications.length > 0 && (
                        <div className="border-t border-gray-200 p-3 text-center">
                            <Button variant="ghost" size="sm" className="text-xs">
                                View all notifications
                            </Button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
