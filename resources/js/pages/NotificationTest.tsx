import AppLayout from '@/components/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { useNotifications } from '@/contexts/NotificationContext';

export default function NotificationTest() {
    const { showToast } = useNotifications();

    const testNotifications = () => {
        showToast('🏆 Tournament completed! Check your results.', 'success');

        setTimeout(() => {
            showToast('🎯 New peer competition available!', 'info');
        }, 1000);

        setTimeout(() => {
            showToast('🎉 You won ₦5,000 in the daily tournament!', 'success');
        }, 2000);
    };

    const testApiCall = async () => {
        try {
            // This would trigger a test notification via API
            const response = await fetch('/api/test/notification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                showToast('Test notification sent via API!', 'success');
            }
        } catch (error) {
            showToast('Failed to send test notification', 'error');
        }
    };

    const testTournamentNotification = async () => {
        try {
            const response = await fetch('/api/test/tournament-notification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                showToast('Tournament notification sent!', 'success');
            }
        } catch (error) {
            showToast('Failed to send tournament notification', 'error');
        }
    };

    const testPrizeNotification = async () => {
        try {
            const response = await fetch('/api/test/prize-notification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                showToast('Prize notification sent!', 'success');
            }
        } catch (error) {
            showToast('Failed to send prize notification', 'error');
        }
    };

    return (
        <div className="p-6">
            <div className="mx-auto max-w-2xl">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">Notification System Test</h1>

                <div className="space-y-4 rounded-lg bg-white p-6 shadow">
                    <div>
                        <h2 className="mb-2 text-lg font-semibold text-gray-800">Test Toast Notifications</h2>
                        <p className="mb-4 text-gray-600">Click the button below to test toast notifications that appear in the top-right corner.</p>
                        <Button onClick={testNotifications}>Show Test Toasts</Button>
                    </div>

                    <hr />

                    <div>
                        <h2 className="mb-2 text-lg font-semibold text-gray-800">Test API Notifications</h2>
                        <p className="mb-4 text-gray-600">
                            These will send test notifications through the backend API and should appear in the notification center.
                        </p>
                        <div className="space-x-2">
                            <Button onClick={testApiCall} variant="outline">
                                Send Test Notification
                            </Button>
                            <Button onClick={testTournamentNotification} variant="outline">
                                Tournament Notification
                            </Button>
                            <Button onClick={testPrizeNotification} variant="outline">
                                Prize Notification
                            </Button>
                        </div>
                    </div>

                    <hr />

                    <div>
                        <h2 className="mb-2 text-lg font-semibold text-gray-800">Real-time Features</h2>
                        <ul className="space-y-2 text-gray-600">
                            <li>• ✅ Toast notifications for immediate feedback</li>
                            <li>• ✅ Notification center with unread count badge</li>
                            <li>• ✅ Real-time WebSocket connection via Laravel Reverb</li>
                            <li>• ✅ Tournament completion notifications</li>
                            <li>• ✅ Peer competition notifications</li>
                            <li>• ✅ Prize won notifications</li>
                            <li>• ✅ Mark as read/unread functionality</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    );
}

// Example usage in a main app component
export function App() {
    // You would get the user ID from your authentication system
    const userId = 1; // Replace with actual user ID

    return (
        <AppLayout userId={userId}>
            <NotificationTest />
        </AppLayout>
    );
}
