# Tournament Changes Summary

## Changes Made:

### 1. Scoring Logic Update

- **Before**: Both main and sub player points were added together
- **After**: Use main player points if they played, otherwise use sub player points
- **Logic**: Check if main player has `did_play = true` and `is_injured = false`

### 2. Multiple Tournament Entries

- **Before**: Users could only join a tournament once
- **After**: Users can join tournaments multiple times
- **Display Logic**:
    - Current user sees all their entries with entry numbers
    - Other users only see the best entry with total entry count badge

## Key Files Modified:

1. **app/Jobs/CalculateCompetitionScoresJob.php**
    - Updated `calculateParticipantScore()` method
    - Added `didPlayerPlay()` helper method
    - Now uses main OR sub player, not both

2. **app/Http/Controllers/Tournament/TournamentController.php**
    - Removed tournament join restriction in `create()` method
    - Updated `index()` method to handle multiple entries per user
    - Updated `leaderboard()` method to show best entry per user
    - Cleaned up unused imports

3. **app/Models/User.php**
    - Added `getTournamentEntriesCount()` method
    - Kept existing `AlreadyJoinedTodayTournament()` for backward compatibility

4. **resources/js/pages/tournament/index.tsx**
    - Updated UI to show "Join Again" option
    - Added entry count badges for users with multiple entries
    - Modified join button logic

## Testing Checklist:

- [ ] User can join tournament multiple times
- [ ] Scoring uses main player OR sub player (not both)
- [ ] Current user sees all their entries
- [ ] Other users only see best entry with count badge
- [ ] Leaderboard shows best entry per user
- [ ] Prize distribution works correctly
- [ ] Winner determination works with new scoring

## Database Impact:

- No schema changes required
- Existing data remains valid
- Multiple `tournament_users` records per user now allowed
