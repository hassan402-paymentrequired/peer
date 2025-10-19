# Clean Sheet Logic Test Cases

## ✅ GOALKEEPER Examples

### Case 1: Goalkeeper with Clean Sheet + Saves

```
Player: Goalkeeper
Minutes: 90
Goals Conceded: 0
Goals Saved: 3

Calculation:
- Base points: (goals, assists, shots, etc.)
- Clean Sheet: 15 points ✅
- Saves: 3 × 3 = 9 points ✅
- Total Clean Sheet Points: 15 + 9 = 24 points ✅

Result: Gets clean sheet bonus + save points
```

### Case 2: Goalkeeper with Conceded Goals + Saves

```
Player: Goalkeeper
Minutes: 90
Goals Conceded: 2
Goals Saved: 4

Calculation:
- Base points: (goals, assists, shots, etc.)
- Clean Sheet: 0 points ❌ (lost due to conceding)
- Saves: 4 × 3 = 12 points ✅
- Total Clean Sheet Points: 0 + 12 = 12 points

Result: Loses clean sheet bonus, only gets save points
```

### Case 3: Goalkeeper with Clean Sheet + No Saves

```
Player: Goalkeeper
Minutes: 90
Goals Conceded: 0
Goals Saved: 0

Calculation:
- Base points: (goals, assists, shots, etc.)
- Clean Sheet: 15 points ✅
- Saves: 0 × 3 = 0 points
- Total Clean Sheet Points: 15 + 0 = 15 points ✅

Result: Gets clean sheet bonus only
```

## ✅ DEFENDER Examples

### Case 4: Defender with Clean Sheet

```
Player: Defender
Minutes: 90
Goals Conceded: 0

Calculation:
- Base points: (goals, assists, shots, etc.)
- Clean Sheet: 10 points ✅
- Total Clean Sheet Points: 10 points ✅

Result: Gets clean sheet bonus
```

### Case 5: Defender with Conceded Goals

```
Player: Defender
Minutes: 90
Goals Conceded: 1

Calculation:
- Base points: (goals, assists, shots, etc.)
- Clean Sheet: 0 points ❌ (lost due to conceding)
- Total Clean Sheet Points: 0 points

Result: Loses clean sheet bonus
```

## ✅ Edge Cases

### Case 6: Goalkeeper/Defender with Less Than 65 Minutes

```
Player: Goalkeeper/Defender
Minutes: 45
Goals Conceded: 0

Result: No clean sheet bonus (didn't play enough minutes)
```

### Case 7: Midfielder/Forward (No Clean Sheet)

```
Player: Midfielder
Minutes: 90
Goals Conceded: 0

Result: No clean sheet bonus (only for GK/Defenders)
```

## 🧪 Sample from README - Pau López (Goalkeeper)

```json
API Response:
{
  "goals": { "conceded": 2, "saves": 2 },
  "games": { "minutes": 90, "position": "G" }
}

Expected Calculation:
- Goals Conceded: 2 (no clean sheet)
- Goals Saved: 2
- Clean Sheet Points: 0 (lost due to conceding)
- Save Points: 2 × 3 = 6 points
- Total: 6 points ✅
```

## ✅ Logic Summary

**GOALKEEPER:**

- **Clean Sheet (0 conceded)**: 15 + (saves × 3) points
- **Conceded Goals**: Only (saves × 3) points, no clean sheet bonus

**DEFENDER:**

- **Clean Sheet (0 conceded)**: 10 points
- **Conceded Goals**: 0 points, no clean sheet bonus

**Requirements:**

- Must play 65+ minutes
- Only applies to Goalkeepers (G) and Defenders (D)
- Clean sheet field updated in database
