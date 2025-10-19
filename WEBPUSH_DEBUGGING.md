# WebPush Debugging Guide

## 🔍 **Console Errors Explained**

The errors you're seeing are **NOT** from your WebPush implementation:

```
popup.js:6 Uncaught TypeError: Cannot read properties of null (reading 'addEventListener')
set-json-global.js:1 JSON Formatter: Type "json" to inspect
```

These are from **browser extensions** (likely JSON formatter or other dev tools extensions). They're harmless and don't affect your notifications.

## 🧪 **Testing Your WebPush Notifications**

### Step 1: Visit Test Page

Go to: `http://your-app.com/test-notifications`

### Step 2: Check Permission

1. Click "Check Permission" button
2. Should show "granted" if notifications are allowed
3. If "default", refresh the page to grant permission

### Step 3: Test Notifications

1. Click any test button (start with "Test Tournament WebPush")
2. Should see success message in results
3. Should receive browser notification
4. Click notification to test navigation

## 🔧 **Troubleshooting Steps**

### 1. Check Browser Console

Look for these logs (not the extension errors):

```
Service Worker registered: [ServiceWorkerRegistration]
Push Subscription: {"endpoint":"...","keys":{"p256dh":"...","auth":"..."}}
```

### 2. Check Laravel Logs

```bash
tail -f storage/logs/laravel.log
```

Look for:

```
Sending test WebPush notification to user X
User has X push subscriptions
```

### 3. Check Database

```sql
-- Check if push subscriptions are saved
SELECT * FROM push_subscriptions WHERE subscribable_type = 'App\\Models\\User';

-- Check if notifications are created
SELECT * FROM user_notifications ORDER BY created_at DESC LIMIT 5;
```

### 4. Check Network Tab

- Look for POST to `/save-subscription` (should return 200)
- Look for POST to `/api/test/webpush-notification` (should return 200)

## 🚨 **Common Issues & Solutions**

### Issue 1: No Browser Notification

**Cause**: User hasn't granted permission or no subscription saved
**Solution**:

- Check `Notification.permission` in console
- Refresh page to re-register service worker
- Check if subscription was saved in database

### Issue 2: "Failed to send notification"

**Cause**: User has no push subscriptions
**Solution**:

- Make sure service worker registered successfully
- Check if `/save-subscription` endpoint was called
- Verify VAPID keys are configured

### Issue 3: Notification Shows But Doesn't Navigate

**Cause**: Service worker click handler issue
**Solution**:

- Check service worker console for errors
- Verify notification data includes correct URL

## 🔑 **VAPID Keys Check**

Make sure you have VAPID keys configured:

```env
VAPID_PUBLIC_KEY=BG6wjrwln2cyFMKkNp5IqockYwtichfoyM4MrCi9U0PcLpHK6ySi9PXf_qoRKV8ay8GOuucYVOLeipejJFSoFX8
VAPID_PRIVATE_KEY=your_private_key_here
VAPID_SUBJECT=mailto:your-email@example.com
```

## 📱 **Testing Checklist**

- [ ] Service worker registers without errors
- [ ] Notification permission granted
- [ ] Push subscription saved to database
- [ ] Test API endpoints return success
- [ ] Browser notification appears
- [ ] Clicking notification navigates correctly
- [ ] In-app notification also created

## 🎯 **Expected Flow**

1. **Page Load**: Service worker registers, asks for permission
2. **Permission Granted**: Creates push subscription, saves to backend
3. **Test Button Clicked**: Sends API request
4. **Backend**: Creates notification, sends WebPush
5. **Browser**: Receives push, shows notification
6. **User Clicks**: Opens relevant page in app

If any step fails, check the logs and database for that specific step.
