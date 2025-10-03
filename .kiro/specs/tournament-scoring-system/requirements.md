# Requirements Document

## Introduction

This feature implements an automated tournament and peer scoring system that calculates total points for all participants when their selected squads (main and sub players) have finished playing their fixtures, and determines winners based on final scores. The system works for both tournaments and peer competitions.

### System Architecture Overview

- **Tournaments**: Users join daily tournaments by paying an entry fee, select 5 squads (each with main/sub players), and compete for prizes
- **Peers**: Users create or join peer competitions with custom entry fees and participant limits
- **Squads**: Each participant selects 5 squads, each containing a main player and substitute player from specific matches (PlayerMatch)
- **Scoring**: Points calculated from PlayerStatistic records using goals, assists, shots, shots_on_target, and yellow_cards
- **Data Flow**: External Football API → Fixtures → PlayerMatches → PlayerStatistics → Squad Points → Total Points → Winners

## Requirements

### Requirement 1

**User Story:** As a system administrator, I want the system to automatically fetch live player statistics from the external API and detect when all fixtures for selected players have finished, so that final scoring can be calculated without manual intervention.

#### Acceptance Criteria

1. WHEN fixtures are ongoing THEN the system SHALL periodically fetch live player statistics from the Football API (api-sports.io) for all players selected in active tournaments/peers
2. WHEN a fixture status changes to "Match Finished" THEN the system SHALL fetch final player statistics and update the PlayerStatistic model
3. WHEN all fixtures containing players selected by tournament/peer participants have completed THEN the system SHALL automatically trigger the final scoring calculation
4. WHEN checking fixture completion status THEN the system SHALL only consider fixtures that contain players selected by participants in the specific tournament or peer
5. WHEN fetching statistics THEN the system SHALL handle API rate limits and errors gracefully

### Requirement 2

**User Story:** As a tournament/peer participant, I want my total points to be accurately calculated from all my selected players' live performances fetched from the external API, so that my final ranking is fair and correct.

#### Acceptance Criteria

1. WHEN live statistics are fetched from the Football API THEN the system SHALL update or create PlayerStatistic records with player_id, fixture_id, goals_total, goals_assists, shots_total, shots_on_target, yellow_cards, did_play, is_injured, and other relevant fields
2. WHEN final scoring is triggered THEN the system SHALL calculate each participant's total points using the PlayerStatistic model's getPointsAttribute method
3. WHEN calculating points THEN the system SHALL sum points from both main and sub players for each of the 5 squads per participant
4. WHEN calculating individual player points THEN the system SHALL use the formula from config: goals _ config('point.goal') + assists _ config('point.assist') + shots _ config('point.shot') + shots_on_target _ config('point.shot_on_target') + yellow_cards \* config('point.yellow_card')
5. WHEN points calculation is complete THEN the system SHALL update the total_points field in tournament_users or peer_users table
6. WHEN a player doesn't play (did_play = false) or is injured (is_injured = true) THEN the system SHALL award zero points for that player

### Requirement 3

**User Story:** As a tournament/peer administrator, I want the system to automatically determine and announce winners, so that results are processed quickly and accurately.

#### Acceptance Criteria

1. WHEN all participants' total points are calculated THEN the system SHALL rank participants by their total_points in descending order
2. WHEN determining winners THEN the system SHALL handle tie-breaking scenarios by comparing individual squad performances
3. WHEN winners are determined THEN the system SHALL update the is_winner field for winning participants in tournament_users or peer_users table
4. WHEN tournament is completed THEN the system SHALL update the tournament status to "close" and distribute prizes to winners
5. WHEN peer is completed THEN the system SHALL update the peer status to "finished", set winner_user_id field, and distribute prizes according to sharing_ratio

### Requirement 4

**User Story:** As a tournament/peer participant, I want to be notified when the competition is completed and see the final results, so that I know my final ranking and any prizes won.

#### Acceptance Criteria

1. WHEN tournament/peer scoring is completed THEN the system SHALL send notifications to all participants about final results
2. WHEN displaying final results THEN the system SHALL show final rankings with detailed points breakdown from each squad
3. WHEN a participant wins a prize THEN the system SHALL update their wallet balance using the addBalance method and create a transaction record
4. WHEN competition is completed THEN the system SHALL update the interface to show final results with winner badges and prize distribution
5. WHEN tournament is completed THEN the system SHALL show final leaderboard with is_winner badges for winners
6. WHEN peer is completed THEN the system SHALL display the winner_user_id as the champion and show prize distribution based on sharing_ratio

### Requirement 5

**User Story:** As a system administrator, I want comprehensive logging and error handling for the scoring process, so that any issues can be quickly identified and resolved.

#### Acceptance Criteria

1. WHEN scoring calculations begin THEN the system SHALL log the start of the process with timestamp, competition details, and participant count
2. IF any error occurs during scoring THEN the system SHALL log the error details and continue processing other participants
3. WHEN scoring is completed THEN the system SHALL log completion status, total participants processed, winners determined, and any errors encountered
4. IF scoring fails completely THEN the system SHALL maintain competition in previous state and alert administrators via notification system

### Requirement 6

**User Story:** As a system administrator, I want a dedicated job to fetch live player statistics from the Football API during active matches, so that participant scores are updated in real-time.

#### Acceptance Criteria

1. WHEN creating the statistics fetching job THEN the system SHALL create a new job class that fetches player statistics from the Football API (api-sports.io/fixtures/{fixture_id}/players)
2. WHEN the job runs THEN the system SHALL identify all active fixtures that contain players selected in tournaments/peers
3. WHEN fetching statistics THEN the system SHALL parse the API response and update PlayerStatistic records with goals, assists, shots, shots_on_target, yellow_cards, and other relevant stats
4. WHEN API calls fail THEN the system SHALL implement retry logic with exponential backoff and log errors appropriately
5. WHEN fixtures are completed THEN the system SHALL trigger the final scoring calculation for affected tournaments/peers

### Requirement 7

**User Story:** As a system administrator, I want the scoring system to work for both tournaments and peer competitions, so that the same logic can be reused across different competition types.

#### Acceptance Criteria

1. WHEN implementing scoring logic THEN the system SHALL create a shared service that can handle both Tournament and Peer models
2. WHEN calculating scores THEN the system SHALL use the same point calculation logic for both TournamentUserSquard and PeerUserSquard
3. WHEN determining winners THEN the system SHALL apply the same ranking logic but update the appropriate model (TournamentUser or PeerUser)
4. WHEN distributing prizes THEN the system SHALL handle different prize distribution rules for tournaments vs peers
