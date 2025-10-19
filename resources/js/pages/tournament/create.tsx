import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { cn } from '@/lib/utils';
import { Check, Clock, Star, Users } from 'lucide-react';
import { useState } from 'react';

import { FloatingBetSlip } from '@/components/features/floating-bet';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import AppLayout from '@/layouts/app-layout';
import { store } from '@/routes/tournament';
import { router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';

interface Player {
    player_avatar: string;
    player_position: string;
    player_match_id: number;
    player_id: number;
    player_team: string;
    player_name: string;
    against_team_name: string;
    date: string;
    time: string;
    against_team_image: string;
    player_external_id: string;
    fixture_status?: string;
}

interface PlayerGroup {
    star: number;
    players: Player[];
}

interface SelectedPlayer extends Player {
    type: 'main' | 'sub';
}

interface Tournament {
    id: number;
    name: string;
    amount: number;
    users_count: number;
    limit: number;
    status: string;
    created_at: string;
    updated_at: string;
}

export default function JoinPeer({ tournament, players }: { tournament: Tournament; players: PlayerGroup[] }) {
    const { flash } = usePage<{ flash: { error: string } }>().props;
    const [selectedPlayers, setSelectedPlayers] = useState<SelectedPlayer[]>([]);
    const [activeTab, setActiveTab] = useState('5');
    const [processing, setProcessing] = useState(false);

    const getTierColor = (tier: number) => {
        switch (tier) {
            case 5:
                return 'text-secondary';
            case 4:
                return 'text-accent';
            case 3:
                return 'text-primary';
            case 2:
                return 'text-success';
            default:
                return 'text-muted-foreground';
        }
    };

    const handlePlayerSelect = (player: Player, type: 'main' | 'sub') => {
        const isSelected = selectedPlayers.some((p) => p.player_match_id === player.player_match_id);
        const tierCount = selectedPlayers.filter((p) => p.player_id === player.player_id && p.type === type).length;
        const typeCount = selectedPlayers.filter((p) => p.type === type).length;

        if (isSelected) {
            setSelectedPlayers((prev) => prev.filter((p) => p.player_match_id !== player.player_match_id));
        } else if (tierCount < 1 && typeCount < 5) {
            setSelectedPlayers((prev) => [...prev, { ...player, type }]);
        }
    };

    const isPlayerSelected = (playerMatchId: number) => {
        return selectedPlayers.some((p) => p.player_match_id === playerMatchId);
    };

    const getTierProgress = (tier: number, type: 'main' | 'sub') => {
        const count = selectedPlayers.filter((p) => {
            const playerGroup = players.find((group) => group.star === tier);
            return playerGroup && playerGroup.players.some((player) => player.player_id === p.player_id) && p.type === type;
        }).length;
        return { count, max: 1 };
    };

    const handleSubmitTeam = async () => {
        setProcessing(true);
        console.log('clicked');
        if (selectedPlayers.length !== 10) {
            toast.error('Please select exactly 10 players (5 main + 5 substitutes)');
            return;
        }

        const peers = [5, 4, 3, 2, 1].map((star) => {
            const mainPlayer = selectedPlayers.find((p) => getPlayerStarRating(p.player_id) === star && p.type === 'main');
            const subPlayer = selectedPlayers.find((p) => getPlayerStarRating(p.player_id) === star && p.type === 'sub');

            if (!mainPlayer || !subPlayer) {
                throw new Error(`Missing players for ${star}-star tier`);
            }

            return {
                star,
                main: mainPlayer.player_id,
                sub: subPlayer.player_id,
                main_player_match_id: mainPlayer.player_match_id,
                sub_player_match_id: subPlayer.player_match_id,
            };
        });

        const formData = {
            peers: peers,
        };

        try {
            // Use Inertia router to submit the form
            router.post(store(), formData, {
                onError: (errors) => {
                    console.error('Validation errors:', errors);
                    alert(`Error: ${Object.values(errors).join(', ')}`);
                },
                onFinish: () => {
                    if (flash?.error) {
                        toast.error(flash.error);
                    }
                },
            });
        } catch (error) {
            console.error('Error submitting team:', error);
            alert('Failed to submit team. Please try again.');
        } finally {
            setProcessing(false);
        }
    };

    // Get players for a specific star rating
    const getPlayersByStar = (star: number) => {
        const group = players.find((p) => p.star === star);
        return group ? group.players : [];
    };

    // Get star rating for a player
    const getPlayerStarRating = (playerId: number) => {
        for (const group of players) {
            const player = group.players.find((p) => p.player_id === playerId);
            if (player) {
                return group.star;
            }
        }
        return 1;
    };

    return (
        <AppLayout
        // alert={
        //     Number(balance) < Number(tournament.amount) && (
        //         <div className="mt-3 flex items-center gap-2">
        //            <BatteryWarning size={17} color="red" />  <FormError message="Insufficient balance to join tournament. Please fund your wallet." />
        //         </div>
        //     )
        // }
        >
            <main className="relative p-5">
                {/* Peer Info */}
                <div className="border-border/10 bg-[var(--clr-surface-a10)] py-3">
                    <div className="flex items-center justify-between">
                        <h2 className="font-bold tracking-wider text-[var(--clr-surface-a50)]">{tournament?.name}</h2>
                        <Badge>Joining</Badge>
                    </div>
                    <div className="mt-2 flex items-center gap-3">
                        <div className="flex items-center text-xs tracking-wider text-muted">
                            ₦<span className="">{Number(tournament.amount).toFixed()}</span>
                        </div>
                        <div className="flex items-center gap-1 text-xs text-muted">
                            <Users className="h-4 w-4" />
                            <span>{tournament.users_count || 0}</span>
                        </div>
                        <div className="flex items-center gap-1 text-xs text-muted">
                            <Clock className="h-4 w-4" />
                            <span>Active</span>
                        </div>
                    </div>
                </div>

                {/* Team Selection Progress */}
                <div className="bg-default/50 rounded-sm border p-1 backdrop-blur-md">
                    <div className="rounded-sm border bg-white p-4">
                        <div className="mb-3 flex items-center justify-between">
                            <h3 className="text-headline text-[var(--clr-light-a0)]">Select Your Team</h3>
                            <Badge variant="outline" className="border-[var(--clr-primary-a0)] text-[var(--clr-primary-a0)]">
                                {selectedPlayers.length}/10 players
                            </Badge>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <h4 className="text-body font-semibold text-[var(--clr-light-a0)]">Main Squad</h4>
                                <div className="flex gap-1">
                                    {[5, 4, 3, 2, 1].map((tier) => {
                                        const { count } = getTierProgress(tier, 'main');
                                        return (
                                            <div key={tier} className="flex items-center gap-1">
                                                <Star
                                                    className={`h-3 w-3 ${count > 0 ? getTierColor(tier) : 'text-[var(--clr-surface-a50)]'} ${
                                                        count > 0 ? 'fill-current' : ''
                                                    }`}
                                                />
                                                {count > 0 && <Check className="h-3 w-3 text-[var(--clr-success-a0)]" />}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <h4 className="text-body font-semibold text-[var(--clr-light-a0)]">Substitutes</h4>
                                <div className="flex gap-1">
                                    {[5, 4, 3, 2, 1].map((tier) => {
                                        const { count } = getTierProgress(tier, 'sub');
                                        return (
                                            <div key={tier} className="flex items-center gap-1">
                                                <Star
                                                    className={`h-3 w-3 ${count > 0 ? getTierColor(tier) : 'text-[var(--clr-surface-a50)]'} ${
                                                        count > 0 ? 'fill-current' : ''
                                                    }`}
                                                />
                                                {count > 0 && <Check className="h-3 w-3 text-[var(--clr-success-a0)]" />}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Player Selection */}
                <div className="px-1">
                    {players.length === 0 ? (
                        <div className="py-12 text-center">
                            <h3 className="mb-2 text-xl font-semibold text-muted-foreground">No Players Available</h3>
                            <p className="text-muted-foreground">All matches have started. Please check back later for new matches.</p>
                        </div>
                    ) : (
                        <Tabs value={activeTab} onValueChange={setActiveTab}>
                            <TabsList className="mb-6 grid w-full grid-cols-5 bg-transparent">
                                {[5, 4, 3, 2, 1].map((tier) => {
                                    const isActive = activeTab === tier.toString();
                                    return (
                                        <TabsTrigger
                                            key={tier}
                                            value={tier.toString()}
                                            className="data-[state=active]:text-muted-white text-muted data-[state=active]:rounded-none data-[state=active]:border-b-3 data-[state=active]:border-b-muted data-[state=active]:bg-transparent"
                                        >
                                            <div className="flex items-center gap-1">
                                                <Star className={`h-3 w-3 ${getTierColor(tier)} fill-current`} />
                                                <span className="font-bold">{tier}</span>
                                            </div>
                                            {isActive && <span className="mt-1 block h-1 w-6 rounded bg-[var(--clr-light-a0)]" />}
                                        </TabsTrigger>
                                    );
                                })}
                            </TabsList>

                            {[5, 4, 3, 2, 1].map((tier) => (
                                <TabsContent key={tier} value={tier.toString()} className="space-y-4">
                                    <div className="mb-4 text-center">
                                        <h3 className="text-headline mb-2 text-[var(--clr-light-a0)]">{tier}-Star Players</h3>
                                        <p className="text-caption text-[var(--clr-surface-a50)]">Select 1 for main squad and 1 for substitutes</p>
                                    </div>

                                    <div className="grid grid-cols-1 gap-3">
                                        {getPlayersByStar(tier).length === 0 ? (
                                            <div className="py-8 text-center">
                                                <p className="text-muted-foreground">
                                                    No {tier}-star players available. All matches for this tier have started.
                                                </p>
                                            </div>
                                        ) : (
                                            getPlayersByStar(tier).map((player) => {
                                                const isSelected = isPlayerSelected(player.player_match_id);
                                                const selectedPlayer = selectedPlayers.find((p) => p.player_match_id === player.player_match_id);
                                                const mainCount = getTierProgress(tier, 'main').count;
                                                const subCount = getTierProgress(tier, 'sub').count;

                                                return (
                                                    <Card
                                                        key={player.player_match_id}
                                                        className={cn(
                                                            'rounded bg-card/5 p-0 transition-all',
                                                            isSelected && 'shadow-glow ring-2 ring-primary',
                                                        )}
                                                    >
                                                        <CardContent className="p-4">
                                                            {/* Player vs Team Layout */}
                                                            <div className="mb-3 flex w-full items-center justify-between">
                                                                {/* Player Side */}
                                                                <div className="flex items-center">
                                                                    {/* Player Avatar or Icon */}
                                                                    <div className="flex items-center justify-center gap-3">
                                                                        <Avatar className="rounded">
                                                                            <AvatarImage src={player.player_avatar} alt={player.player_name} />
                                                                            <AvatarFallback className="size-7 rounded uppercase">
                                                                                {player.player_name.substring(0, 2)}
                                                                            </AvatarFallback>
                                                                        </Avatar>
                                                                        <div>
                                                                            <div className="text-muted-white text-sm font-bold md:text-base">
                                                                                {player.player_name}
                                                                            </div>
                                                                            <div className="text-[10px] text-muted md:text-xs">
                                                                                {player.player_position}-{player.player_team}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                {/* VS Divider */}
                                                                <div className="mx-4 flex flex-col items-center">
                                                                    <span className="rounded-full bg-muted px-2 py-1 text-xs font-bold text-foreground shadow">
                                                                        VS
                                                                    </span>
                                                                </div>

                                                                {/* Team Side */}
                                                                <div className="flex min-w-[90px] flex-col items-end">
                                                                    <div className="flex items-center gap-2">
                                                                        {/* Team Logo Placeholder  */}
                                                                        <Avatar className="rounded">
                                                                            <AvatarImage src={player.against_team_image} alt={player.player_name} />
                                                                            <AvatarFallback className="size-7 rounded uppercase">
                                                                                {player.against_team_name.substring(0, 2)}
                                                                            </AvatarFallback>
                                                                        </Avatar>
                                                                        <span className="text-muted-white text-sm font-semibold">
                                                                            {player.against_team_name}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            {/* Action Buttons */}
                                                            <div className="mt-2 flex gap-2">
                                                                <Button
                                                                    size="sm"
                                                                    variant={selectedPlayer?.type === 'main' ? 'default' : 'outline'}
                                                                    disabled={mainCount >= 1 && selectedPlayer?.type !== 'main'}
                                                                    onClick={() => handlePlayerSelect(player, 'main')}
                                                                    className={`h-8 flex-1 ${
                                                                        selectedPlayer?.type === 'main'
                                                                            ? 'bg-[var(--clr-primary-a0)] text-muted'
                                                                            : 'text-muted-white'
                                                                    }`}
                                                                >
                                                                    {selectedPlayer?.type === 'main' ? 'Main ✓' : 'Main Squad'}
                                                                </Button>
                                                                <Button
                                                                    size="sm"
                                                                    variant={selectedPlayer?.type === 'sub' ? 'secondary' : 'outline'}
                                                                    disabled={subCount >= 1 && selectedPlayer?.type !== 'sub'}
                                                                    onClick={() => handlePlayerSelect(player, 'sub')}
                                                                    className={`h-8 flex-1 ${
                                                                        selectedPlayer?.type === 'sub'
                                                                            ? 'bg-[var(--clr-secondary-a0)] text-muted'
                                                                            : 'text-muted-white'
                                                                    }`}
                                                                >
                                                                    {selectedPlayer?.type === 'sub' ? 'Sub ✓' : 'Substitute'}
                                                                </Button>
                                                            </div>
                                                        </CardContent>
                                                    </Card>
                                                );
                                            })
                                        )}
                                    </div>
                                </TabsContent>
                            ))}
                        </Tabs>
                    )}
                </div>

                {/* Submit Button */}

                <FloatingBetSlip
                    selectedPlayers={selectedPlayers}
                    onRemovePlayer={(playerId) => {
                        setSelectedPlayers((prev) => prev.filter((p) => p.player_match_id !== playerId));
                    }}
                    players={players}
                    processing={processing}
                    handleSubmitTeam={handleSubmitTeam}
                />
            </main>
        </AppLayout>
    );
}
