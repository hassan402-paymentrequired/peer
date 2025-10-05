# Design Document

## Overview

Ts system implements automated scoring for tournaments and peer competitions by fetching live player statistics from the Football API and calculating winners when all matches are complete.

## Architecture

### Core Components

1. **FetchLiveStatisticsJob** - Cron job that fetches live player statistics
2. **ScoringService** - Calculates points and determines winners
3. **CompetitionFinalizerService** - Handles prize distribution and status updates
4. **NotificationService** - Creates in-app notifications and broadcasts real-time updates
5. **NotificationController** - API endpoints for fetching and managing notifications
6. **NotificationCenter** - Frontend component for displaying notifications

### Data Flow

```
Cron Schedule → FetchLiveStatisticsJob → Football API
                      ↓
PlayerStatistic Updates → Check All Matches Complete
                      ↓
ScoringService → Calculate Total Points → Determine Winners
                      ↓
CompetitionFinalizerService → Update Status → Distribute Prizes
                      ↓
NotificationService → Create In-App Notifications → Broadcast Real-time Updates
                      ↓
Frontend NotificationCenter → Display Notifications → Update UI
```

## Components and Interfaces

### 1. FetchLiveStatisticsJob

**Purpose**: Scheduled job that fetches live statistics for ongoing matches

**Key Methods**:

- `handle()` - Main execution method
- `getActiveFixtures()` - Get fixtures with selected players
- `fetchFixtureStatistics($fixtureId)` - Call Football API
- `updatePlayerStatistics($data)` - Update database
- `checkCompletionStatus()` - Trigger scoring if all matches done

### 2. ScoringService

**Purpose**: Calculate participant scores and determine winners

**Key Methods**:

- `calculateTournamentScores($tournamentId)` - Calculate all tournament participant scores
- `calculatePeerScores($peerId)` - Calculate all peer participant scores
- `calculateParticipantScore($participantSquads)` - Calculate individual score
- `determineWinners($participants)` - Rank and determine winners

### 3. CompetitionFinalizerService

**Purpose**: Handle completion, prize distribution, and status updates

**Key Methods**:

- `finalizeTournament($tournamentId)` - Complete tournament
- `finalizePeer($peerId)` - Complete peer competition
- `distributePrizes($competition, $winners)` - Handle prize distribution
- `updateWallets($winners, $amounts)` - Update winner wallets

## Data Models

### PlayerStatistic Updates

```php
// Fields updated from API
- player_id (from squad selection)
- fixture_id (from player_matches)
- goals_total (from API response)
- goals_assists (from API response)
- shots_total (from API response)
- shots_on_target (from API response)
- yellow_cards (from API response)
- did_play (from API response)
- is_injured (from API response)
- minutes (from API response)
- rating (from API response)
```

### Point Calculation

```php
// Using PlayerStatistic->getPointsAttribute()
$points = $this->goals_total * config('point.goal') +
          $this->goals_assists * config('point.assist') +
          $this->shots_total * config('point.shot') +
          $this->shots_on_target * config('point.shot_on_target') +
          $this->yellow_cards * config('point.yellow_card');
```

## Error Handling

### API Rate Limiting

- Implement exponential backoff for API calls
- Queue failed requests for retry
- Log API errors and response codes

### Database Failures

- Use database transactions for score calculations
- Rollback on calculation errors
- Maintain competition state integrity

### Notification System

#### In-App Notifications

- Store notifications in user_notifications table
- Support different notification types (tournament_completed, peer_completed, prize_won)
- Include structured data for rich notification content
- Track read/unread status per user

#### Real-time Broadcasting

- Use Laravel Reverb for WebSocket connections
- Broadcast to private user channels
- Support notification counts and live updates
- Handle connection failures gracefully

#### Frontend Integration

- NotificationCenter component with unread counts
- Toast notifications for immediate feedback
- Notification history and management
- Real-time updates via WebSocket connection

## Testing Strategy

### Unit Tests

- Test point calculation logic
- Test winner determination algorithms
- Test prize distribution calculations

### Integration Tests

- Test API integration with mock responses
- Test database transaction handling
- Test complete scoring workflow

### Performance Tests

- Test with large numbers of participants
- Test API rate limiting handling
- Test concurrent job execution

## Configuration

### Point Values (config/point.php)

```php
return [
    'goal' => 6,
    'assist' => 4,
    'shot' => 1,
    'shot_on_target' => 2,
    'yellow_card' => -1,
];
```

### Cron Schedule

```php
// In app/Console/Kernel.php
$schedule->job(FetchLiveStatisticsJob::class)->everyFiveMinutes();
```

### API Configuration

```php
// Football API settings
'football_api' => [
    'base_url' => 'https://v3.football.api-sports.io',
    'key' => env('SPORT_API_KEY'),
    'rate_limit' => 100, // requests per minute
    'timeout' => 30,
];
```

## Notification System Architecture

### Database Schema

#### user_notifications Table

```php
- id (primary key)
- user_id (foreign key to users)
- title (notification title)
- message (notification content)
- type (tournament_completed, peer_completed, prize_won, etc.)
- data (JSON field for additional context)
- read_at (timestamp for read status)
- created_at, updated_at
```

### Notification Types

1. **Tournament Completed**
    - Sent when tournament scoring is finished
    - Includes final ranking and prize information
    - Data: tournament_id, final_rank, total_points, prize_amount

2. **Peer Competition Completed**
    - Sent when peer competition is finished
    - Includes winner announcement and prize distribution
    - Data: peer_id, is_winner, total_points, prize_amount

3. **Prize Won**
    - Sent when user wins a prize
    - Includes prize amount and wallet update
    - Data: competition_type, competition_id, prize_amount, new_balance

### Real-time Broadcasting

#### Laravel Reverb Integration

```php
// Broadcasting to private user channel
broadcast(new NotificationCreated($notification))->toOthers();

// Channel naming convention
'private-user.{user_id}'
```

#### Frontend WebSocket Connection

```javascript
// Connect to user's private channel
Echo.private(`user.${userId}`).listen('NotificationCreated', (e) => {
    // Update notification center
    // Show toast notification
    // Update unread count
});
```

### API Endpoints

#### Notification Management

```php
GET /api/notifications - Fetch user notifications
POST /api/notifications/{id}/read - Mark notification as read
POST /api/notifications/read-all - Mark all notifications as read
GET /api/notifications/unread-count - Get unread notification count
```
