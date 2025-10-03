# Tournament & Peer Scoring System

## Overview

Automated scoring system that fetches live player statistics from Football API and calculates winners for tournaments and peer competitions.

## Key Components

### 1. FetchLiveStatisticsJob

- **Purpose**: Fetches live player statistics from Football API
- **Schedule**: Runs every 5 minutes via cron
- **Triggers**: Automatically dispatches scoring when all matches complete

### 2. CalculateCompetitionScoresJob

- **Purpose**: Calculates participant scores and determines winners
- **Handles**: Both tournaments and peer competitions
- **Actions**: Updates scores, determines winners, distributes prizes

### 3. Admin API Endpoints

- `POST /admin/scoring/fetch-live-statistics` - Manually trigger statistics fetch
- `POST /admin/scoring/calculate-tournament-scores` - Force tournament scoring
- `POST /admin/scoring/calculate-peer-scores` - Force peer scoring
- `GET /admin/scoring/competition-status` - Get competition status overview

## Setup Instructions

### 1. Configure Point Values

Edit `config/point.php` to set point values:

```php
return [
    'goal' => 6,
    'assist' => 4,
    'shot' => 1,
    'shot_on_target' => 2,
    'yellow_card' => -1,
];
```

### 2. Set Up Cron Job

Add to your server's crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Queue Configuration

Make sure your queue worker is running:

```bash
php artisan queue:work
```

## Manual Commands

### Fetch Live Statistics

```bash
php artisan statistics:fetch-live
```

### Test Scoring

```bash
php artisan scoring:test tournament 1
php artisan scoring:test peer 1
```

## How It Works

1. **Cron Schedule**: Every 5 minutes, `FetchLiveStatisticsJob` runs
2. **API Calls**: Fetches live statistics for ongoing fixtures with selected players
3. **Database Updates**: Updates `PlayerStatistic` records with latest data
4. **Completion Check**: Checks if all matches for tournaments/peers are finished
5. **Scoring Trigger**: Automatically dispatches `CalculateCompetitionScoresJob`
6. **Score Calculation**: Calculates total points for each participant
7. **Winner Determination**: Ranks participants and determines winners
8. **Prize Distribution**: Updates wallets and creates transaction records
9. **Status Updates**: Changes competition status to completed
10. **Notifications**: Sends completion notifications to participants

## Point Calculation

Points are calculated using the `PlayerStatistic` model's `getPointsAttribute()` method:

```php
$points = ($goals_total * config('point.goal')) +
          ($assists * config('point.assist')) +
          ($shots_total * config('point.shot')) +
          ($shots_on_target * config('point.shot_on_target')) +
          ($yellow_cards * config('point.yellow_card'));
```

Players who didn't play (`did_play = false`) or were injured (`is_injured = true`) receive 0 points.

## Prize Distribution

### Tournaments

- Winners split the total prize pool equally
- Prize pool = entry_fee × number_of_participants
- Status changes to "close"

### Peers

- Winner determined by highest score
- Prize distribution based on `sharing_ratio`
- Status changes to "finished"
- `winner_user_id` field updated

## Monitoring

Check logs for:

- API call failures
- Scoring calculation errors
- Prize distribution issues
- Competition completion status

## Testing

Use the admin API endpoints to manually trigger processes during development:

1. Create test tournament/peer with participants
2. Use `/admin/scoring/fetch-live-statistics` to fetch data
3. Use `/admin/scoring/calculate-tournament-scores` to test scoring
4. Check `/admin/scoring/competition-status` for overview

## Important Notes

- API rate limits are respected with 1-second delays between fixture calls
- Database transactions ensure data integrity during scoring
- Jobs run in background to avoid blocking
- Overlapping job execution is prevented
- All operations are logged for debugging
