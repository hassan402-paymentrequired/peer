import { Head } from '@inertiajs/react';
import { useState } from 'react';

export default function TestNotifications() {
    const [loading, setLoading] = useState<string | null>(null);
    const [results, setResults] = useState<string[]>([]);

    const addResult = (message: string) => {
        setResults((prev) => [...prev, `${new Date().toLocaleTimeString()}: ${message}`]);
    };

    const testNotification = async (endpoint: string, type: string) => {
        setLoading(type);
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (response.ok) {
                addResult(`✅ ${type}: ${data.message}`);
            } else {
                addResult(`❌ ${type}: ${data.error || 'Failed'}`);
            }
        } catch (error) {
            addResult(`❌ ${type}: ${error instanceof Error ? error.message : 'Unknown error'}`);
        } finally {
            setLoading(null);
        }
    };

    const checkNotificationPermission = () => {
        const permission = Notification.permission;
        addResult(`🔔 Notification permission: ${permission}`);

        if (permission === 'default') {
            addResult('ℹ️ Please refresh the page to grant notification permission');
        }
    };

    const clearResults = () => {
        setResults([]);
    };

    return (
        <>
            <Head title="Test Notifications" />

            <div className="min-h-screen bg-gray-100 py-12">
                <div className="mx-auto max-w-4xl px-4">
                    <div className="rounded-lg bg-white p-8 shadow-lg">
                        <h1 className="mb-8 text-3xl font-bold text-gray-900">🔔 WebPush Notification Testing</h1>

                        {/* Permission Status */}
                        <div className="mb-8 rounded-lg bg-blue-50 p-4">
                            <h2 className="mb-2 text-lg font-semibold text-blue-900">Notification Permission Status</h2>
                            <button onClick={checkNotificationPermission} className="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                                Check Permission
                            </button>
                        </div>

                        {/* Test Buttons */}
                        <div className="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <button
                                onClick={() => testNotification('/api/test/webpush-notification', 'WebPush Test')}
                                disabled={loading === 'WebPush Test'}
                                className="rounded-lg bg-green-600 p-4 text-white hover:bg-green-700 disabled:opacity-50"
                            >
                                {loading === 'WebPush Test' ? '⏳ Sending...' : '🏆 Test Tournament WebPush'}
                            </button>

                            <button
                                onClick={() => testNotification('/api/test/tournament-notification', 'Tournament')}
                                disabled={loading === 'Tournament'}
                                className="rounded-lg bg-yellow-600 p-4 text-white hover:bg-yellow-700 disabled:opacity-50"
                            >
                                {loading === 'Tournament' ? '⏳ Sending...' : '🎯 Test Tournament Notification'}
                            </button>

                            <button
                                onClick={() => testNotification('/api/test/prize-notification', 'Prize')}
                                disabled={loading === 'Prize'}
                                className="rounded-lg bg-purple-600 p-4 text-white hover:bg-purple-700 disabled:opacity-50"
                            >
                                {loading === 'Prize' ? '⏳ Sending...' : '🎉 Test Prize Notification'}
                            </button>

                            <button
                                onClick={() => testNotification('/api/test/notification', 'Basic')}
                                disabled={loading === 'Basic'}
                                className="rounded-lg bg-gray-600 p-4 text-white hover:bg-gray-700 disabled:opacity-50"
                            >
                                {loading === 'Basic' ? '⏳ Sending...' : '📱 Test Basic Notification'}
                            </button>
                        </div>

                        {/* Results */}
                        <div className="rounded-lg bg-gray-50 p-4">
                            <div className="mb-4 flex items-center justify-between">
                                <h2 className="text-lg font-semibold text-gray-900">Test Results</h2>
                                <button onClick={clearResults} className="rounded bg-red-500 px-3 py-1 text-sm text-white hover:bg-red-600">
                                    Clear
                                </button>
                            </div>

                            <div className="max-h-64 space-y-2 overflow-y-auto">
                                {results.length === 0 ? (
                                    <p className="text-gray-500 italic">No test results yet...</p>
                                ) : (
                                    results.map((result, index) => (
                                        <div key={index} className="rounded border bg-white p-2 font-mono text-sm">
                                            {result}
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>

                        {/* Instructions */}
                        <div className="mt-8 rounded-lg bg-yellow-50 p-4">
                            <h3 className="mb-2 text-lg font-semibold text-yellow-900">📋 Testing Instructions</h3>
                            <ol className="list-inside list-decimal space-y-1 text-yellow-800">
                                <li>Make sure you've granted notification permission (check status above)</li>
                                <li>Click any test button to send a notification</li>
                                <li>You should see both a browser notification AND an in-app notification</li>
                                <li>Click on the browser notification to test navigation</li>
                                <li>Check the console for any errors</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
