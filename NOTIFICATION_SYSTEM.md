# In-App Notification System

This document describes the complete in-app notification system implementation for the fantasy sports application.

## Overview

The notification system provides real-time in-app notifications alongside existing email notifications. It uses Laravel Reverb for WebSocket connections and includes both persistent notifications and toast notifications.

## Architecture

### Backend Components

1. **NotificationService** (`app/Services/NotificationService.php`)
    - Creates and manages notifications
    - Handles tournament/peer completion notifications
    - Manages prize won notifications
    - Broadcasts real-time events

2. **Notification Model** (`app/Models/Notification.php`)
    - Uses `user_notifications` table
    - Supports read/unread status
    - Stores structured data in JSON format

3. **NotificationController** (`app/Http/Controllers/NotificationController.php`)
    - API endpoints for fetching notifications
    - Mark as read/unread functionality
    - Pagination and filtering

4. **Events**
    - `NotificationCreated` - Broadcasts when new notifications are created
    - `TournamentCompleted` - Existing event for tournament completion
    - `PeerCompleted` - Existing event for peer completion
    - `PrizeWon` - Existing event for prize distribution

### Frontend Components

1. **NotificationContext** (`resources/js/contexts/NotificationContext.tsx`)
    - React context for notification state management
    - WebSocket connection handling
    - Toast notification management

2. **NotificationCenter** (`resources/js/components/notifications/NotificationCenter.tsx`)
    - Bell icon with unread count badge
    - Dropdown with notification list
    - Mark as read functionality

3. **ToastContainer** (`resources/js/components/notifications/ToastContainer.tsx`)
    - Real-time toast notifications
    - Auto-dismiss after 5 seconds
    - Different styles for success/error/info

4. **AppLayout** (`resources/js/components/layouts/AppLayout.tsx`)
    - Wraps app with NotificationProvider
    - Includes NotificationCenter in header
    - Renders ToastContainer

## Database Schema

### user_notifications Table

```sql
CREATE TABLE user_notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(255) NOT NULL,
    data JSON NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_user_read (user_id, read_at),
    INDEX idx_user_type (user_id, type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Notification Types

1. **tournament_completed**
    - Sent when tournament scoring is finished
    - Includes final ranking and prize information

2. **peer_completed**
    - Sent when peer competition is finished
    - Includes winner announcement

3. **prize_won**
    - Sent when user wins a prize
    - Includes prize amount and wallet update

4. **test_notification**
    - For testing purposes only

## API Endpoints

### Notification Management

- `GET /notifications` - Fetch user notifications (paginated)
- `GET /notifications/unread-count` - Get unread notification count
- `POST /notifications/mark-read` - Mark specific notification as read
- `POST /notifications/mark-all-read` - Mark all notifications as read
- `DELETE /notifications/delete` - Delete specific notification
- `GET /notifications/recent` - Get recent notifications

### Test Endpoints (Development Only)

- `POST /api/test/notification` - Send test notification
- `POST /api/test/tournament-notification` - Send test tournament notification
- `POST /api/test/prize-notification` - Send test prize notification

## Real-time Broadcasting

### Laravel Reverb Configuration

The system uses Laravel Reverb for WebSocket connections. Configuration in `config/broadcasting.php`:

```php
'reverb' => [
    'driver' => 'reverb',
    'key' => env('REVERB_APP_KEY'),
    'secret' => env('REVERB_APP_SECRET'),
    'app_id' => env('REVERB_APP_ID'),
    'options' => [
        'host' => env('REVERB_HOST'),
        'port' => env('REVERB_PORT', 443),
        'scheme' => env('REVERB_SCHEME', 'https'),
    ],
],
```

### Channel Structure

- **Private Channels**: `user.{user_id}` for user-specific notifications
- **Events**:
    - `notification.created` - New notification created
    - `tournament.completed` - Tournament finished
    - `peer.completed` - Peer competition finished
    - `prize.won` - Prize awarded

## Integration with Existing System

### Tournament Scoring Job

The `CalculateCompetitionScoresJob` has been updated to:

1. Use `NotificationService` instead of direct model creation
2. Send prize won notifications when distributing prizes
3. Broadcast completion events with proper data

### Email Notifications

The existing email notification system remains intact. The in-app notifications work alongside emails, not as a replacement.

## Usage Examples

### Backend - Creating Notifications

```php
use App\Services\NotificationService;

// Inject the service
$notificationService = app(NotificationService::class);

// Create a simple notification
$notificationService->createNotification(
    $user,
    'Tournament Completed',
    'Your tournament has finished!',
    'tournament_completed',
    ['tournament_id' => 123]
);

// Tournament completion (handles all participants)
$notificationService->notifyTournamentCompletion($tournament, $winners, $totalPrizePool);

// Prize won notification
$notificationService->notifyPrizeWon($user, 1000.00, 'tournament', 'Daily Championship');
```

### Frontend - Using Notifications

```tsx
import { useNotifications } from '@/contexts/NotificationContext';

function MyComponent() {
    const { notifications, unreadCount, markAsRead, showToast } = useNotifications();

    // Show a toast
    showToast('Success!', 'success');

    // Mark notification as read
    markAsRead(notificationId);

    return (
        <div>
            <p>Unread: {unreadCount}</p>
            {notifications.map((notification) => (
                <div key={notification.id}>{notification.title}</div>
            ))}
        </div>
    );
}
```

### App Setup

```tsx
import AppLayout from '@/components/layouts/AppLayout';

function App() {
    const userId = getCurrentUserId(); // Your auth logic

    return (
        <AppLayout userId={userId}>
            <YourAppContent />
        </AppLayout>
    );
}
```

## Environment Variables

Add these to your `.env` file:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Frontend (in .env or vite config)
VITE_REVERB_APP_KEY=your_app_key
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

## Testing

1. **Run Laravel Reverb Server**:

    ```bash
    php artisan reverb:start
    ```

2. **Test Notifications**:
    - Visit the test page (implement routing to `NotificationTest` component)
    - Use the test API endpoints
    - Check browser console for WebSocket connection logs

3. **Test Real Competition Flow**:
    - Create a tournament/peer
    - Trigger the scoring job
    - Verify notifications are created and broadcast

## Production Considerations

1. **Remove Test Routes**: Delete the test notification routes from `routes/web.php`
2. **WebSocket Scaling**: Consider using Redis for WebSocket scaling in production
3. **Notification Cleanup**: Implement a job to clean up old notifications
4. **Rate Limiting**: Add rate limiting to notification creation
5. **Error Handling**: Implement proper error handling for WebSocket disconnections

## Troubleshooting

### WebSocket Connection Issues

- Check Reverb server is running
- Verify environment variables
- Check browser console for connection errors
- Ensure CSRF token is properly set

### Notifications Not Appearing

- Check database for notification records
- Verify user authentication
- Check Laravel logs for errors
- Ensure broadcasting is enabled

### Performance Issues

- Add database indexes for large notification tables
- Implement notification pagination
- Consider archiving old notifications
