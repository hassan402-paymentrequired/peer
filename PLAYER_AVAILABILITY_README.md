# Player Availability & Lineup System

## Overview

Enhanced player availability tracking system that uses actual fixture lineups from the Football API to determine which players are available for match creation.

## Key Features

### ✅ **Lineup-Based Availability**

- Uses real lineup data from `https://v3.football.api-sports.io/fixtures/lineups`
- Only shows players who are actually selected to play (starting XI + substitutes)
- Prevents creating matches for players not in the lineup

### ✅ **Automated Lineup Fetching**

- **FetchFixtureLineupsJob** runs every 30 minutes via cron
- Fetcineups for fixtures that are starting soon, ongoing, or recently finished
- Stores lineup data in `fixture_lineups` table

### ✅ **Enhanced Match Creation**

- Admins only see available players (those in lineups and not already matched)
- Comprehensive validation prevents conflicts
- Real-time availability checking

## Database Schema

### `fixture_lineups` Table

```sql
- fixture_id (foreign key to fixtures)
- team_id (external team ID from API)
- team_name
- formation (e.g., "4-3-3")
- starting_xi (JSON array of starting players)
- substitutes (JSON array of substitute players)
- coach (JSON object with coach info)
- raw_data (full API response)
```

## API Endpoints

### **Player Availability**

- `GET /admin/match/available-players/fixture?fixture_id=123` - Get available players for fixture
- `POST /admin/match/check-availability` - Check if specific players are available
- `GET /admin/match/player-status?player_id=456` - Get detailed player status
- `GET /admin/match/availability-summary` - Get overall availability stats

### **Lineup Management**

- `POST /admin/match/fetch-lineup` - Manually fetch lineup for fixture
- `GET /admin/match/lineup?fixture_id=123` - Get lineup data for fixture

## Console Commands

### Fetch Lineups

```bash
# Fetch lineups for all relevant fixtures
php artisan lineups:fetch

# Fetch lineup for specific fixture
php artisan lineups:fetch 12345
```

### Test Commands

```bash
# Fetch live statistics (includes lineup fetching)
php artisan statistics:fetch-live

# Test scoring calculation
php artisan scoring:test tournament 1
```

## How It Works

### 1. **Lineup Fetching Process**

1. **Cron Schedule**: `FetchFixtureLineupsJob` runs every 30 minutes
2. **API Call**: Fetches lineups from Football API for relevant fixtures
3. **Data Storage**: Stores lineup data in `fixture_lineups` table
4. **Player Tracking**: Tracks which players are selected to play

### 2. **Match Creation Process**

1. **Admin selects fixture**: Admin chooses a fixture to create matches for
2. **Lineup check**: System checks if lineup data exists, fetches if missing
3. **Available players**: Only shows players who are in the fixture lineup
4. **Validation**: Prevents creating matches for unavailable players
5. **Match creation**: Creates PlayerMatch records for selected players

### 3. **Live Statistics Process**

1. **Lineup first**: Ensures lineup data exists before fetching statistics
2. **Statistics fetch**: Gets live stats only for players in lineups
3. **Data update**: Updates PlayerStatistic records
4. **Completion check**: Marks matches as completed when fixtures finish

## Player Availability Logic

A player is considered **AVAILABLE** if:

- ✅ Player is active (`status = true`)
- ✅ Player is in the fixture lineup (starting XI or substitute)
- ✅ Player doesn't already have a match for this fixture
- ✅ Player is not currently in another ongoing match

A player is **UNAVAILABLE** if:

- ❌ Player is inactive
- ❌ Player is not in the fixture lineup
- ❌ Player already has a match for this fixture
- ❌ Player is currently in another ongoing match

## Benefits

### **Accuracy**

- Uses real lineup data instead of assumptions
- Only tracks players who are actually playing
- Eliminates false positives from team-based matching

### **Efficiency**

- Reduces API calls by focusing on relevant players
- Prevents unnecessary match creation for bench players
- Improves admin workflow with accurate player lists

### **Reliability**

- Comprehensive validation prevents conflicts
- Real-time status checking
- Automatic lineup updates

## Monitoring

Check logs for:

- Lineup fetch success/failures
- API rate limit issues
- Player availability conflicts
- Match creation validation errors

## Testing

1. **Create test fixture** with known lineup
2. **Use `/admin/match/fetch-lineup`** to get lineup data
3. **Check `/admin/match/available-players/fixture`** to see available players
4. **Try creating matches** for players in/out of lineup
5. **Verify validation** prevents conflicts

## Important Notes

- Lineups are typically available 1-2 hours before kickoff
- System gracefully handles missing lineup data
- API rate limits respected with proper delays
- All operations logged for debugging
- Lineup data cached until fixture completion
