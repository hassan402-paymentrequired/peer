import { useNotifications } from '@/contexts/NotificationContext';
import { AlertCircle, CheckCircle, Info, X } from 'lucide-react';

export default function ToastContainer() {
    const { toasts, removeToast } = useNotifications();

    if (toasts.length === 0) return null;

    const getToastIcon = (type: string) => {
        switch (type) {
            case 'success':
                return <CheckCircle className="h-5 w-5 text-green-500" />;
            case 'error':
                return <AlertCircle className="h-5 w-5 text-red-500" />;
            case 'info':
            default:
                return <Info className="h-5 w-5 text-blue-500" />;
        }
    };

    const getToastStyles = (type: string) => {
        switch (type) {
            case 'success':
                return 'border-green-200 bg-green-50';
            case 'error':
                return 'border-red-200 bg-red-50';
            case 'info':
            default:
                return 'border-blue-200 bg-blue-50';
        }
    };

    return (
        <div className="fixed top-4 right-4 z-50 space-y-2">
            {toasts.map((toast) => (
                <div
                    key={toast.id}
                    className={`flex items-center gap-3 rounded-lg border p-4 shadow-lg transition-all duration-300 ease-in-out ${getToastStyles(toast.type)} max-w-md min-w-80`}
                >
                    <div className="flex-shrink-0">{getToastIcon(toast.type)}</div>
                    <div className="flex-1 text-sm text-gray-800">{toast.message}</div>
                    <button onClick={() => removeToast(toast.id)} className="flex-shrink-0 text-gray-400 transition-colors hover:text-gray-600">
                        <X className="h-4 w-4" />
                    </button>
                </div>
            ))}
        </div>
    );
}
