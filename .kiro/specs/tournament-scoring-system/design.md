# Design Document

## Overview

Ts system implements automated scoring for tournaments and peer competitions by fetching live player statistics from the Football API and calculating winners when all matches are complete.

## Architecture

### Core Components

1. **FetchLiveStatisticsJob** - Cron job that fetches live player statistics
2. **ScoringService** - Calculates points and determines winners
3. **CompetitionFinalizerService** - Handles prize distribution and status updates
4. **NotificationService** - Sends completion notifications

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
NotificationService → Send Notifications
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

### Notification Failures

- Queue notifications separately from scoring
- Retry failed notifications
- Log notification errors

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
