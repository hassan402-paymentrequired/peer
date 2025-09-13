import { storeJoinPeer } from "@/actions/App/Http/Controllers/Peer/PeerController";
import { FloatingBetSlip } from "@/components/features/floating-bet";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import AppLayout from "@/layouts/app-layout";
import { cn } from "@/lib/utils";
import join from "@/routes/join";
import { router } from "@inertiajs/react";
import { Check, Clock, Star, Users } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";


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
    player_external_id: string;
}

interface PlayerGroup {
    star: number;
    players: Player[];
}

interface SelectedPlayer extends Player {
    type: "main" | "sub";
}

interface Peer {
    id: number;
    name: string;
    amount: number;
    users_count: number;
    limit: number;
    status: string;
    created_at: string;
    updated_at: string;
}

export default function JoinPeer({
    peer,
    players,
    balance
}: {
    peer: Peer;
    players: PlayerGroup[];
    balance
}) {
    const [selectedPlayers, setSelectedPlayers] = useState<SelectedPlayer[]>(
        []
    );
    const [activeTab, setActiveTab] = useState("5");
   

    const getTierColor = (tier: number) => {
        switch (tier) {
            case 5:
                return "text-secondary";
            case 4:
                return "text-accent";
            case 3:
                return "text-primary";
            case 2:
                return "text-success";
            default:
                return "text-muted-foreground";
        }
    };



    const handlePlayerSelect = (player: Player, type: "main" | "sub") => {
        const isSelected = selectedPlayers.some(
            (p) => p.player_match_id === player.player_match_id
        );
        const tierCount = selectedPlayers.filter(
            (p) => p.player_id === player.player_id && p.type === type
        ).length;
        const typeCount = selectedPlayers.filter((p) => p.type === type).length;

        if (isSelected) {
            setSelectedPlayers((prev) =>
                prev.filter((p) => p.player_match_id !== player.player_match_id)
            );
        } else if (tierCount < 1 && typeCount < 5) {
            setSelectedPlayers((prev) => [...prev, { ...player, type }]);
        }
    };

    const isPlayerSelected = (playerMatchId: number) => {
        return selectedPlayers.some((p) => p.player_match_id === playerMatchId);
    };

    const getTierProgress = (tier: number, type: "main" | "sub") => {
        const count = selectedPlayers.filter((p) => {
            const playerGroup = players.find((group) => group.star === tier);
            return (
                playerGroup &&
                playerGroup.players.some(
                    (player) => player.player_id === p.player_id
                ) &&
                p.type === type
            );
        }).length;
        return { count, max: 1 };
    };

    const handleSubmitTeam = async () => {
        if (selectedPlayers.length !== 10) {
            toast.error(
                "Please select exactly 10 players (5 main + 5 substitutes)"
            );
            return;
        }

        const peers = [5, 4, 3, 2, 1].map((star) => {
            const mainPlayer = selectedPlayers.find(
                (p) =>
                    getPlayerStarRating(p.player_id) === star &&
                    p.type === "main"
            );
            const subPlayer = selectedPlayers.find(
                (p) =>
                    getPlayerStarRating(p.player_id) === star &&
                    p.type === "sub"
            );

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
            peer_id: peer.id,
            peers: peers,
        };

        try {
            // Use Inertia router to submit the form
            router.post(storeJoinPeer(peer.id), formData, {
                onError: (errors) => {
                    console.error("Validation errors:", errors);
                    alert(`Error: ${Object.values(errors).join(", ")}`);
                },
            });
        } catch (error) {
            console.error("Error submitting team:", error);
            toast.error("Failed to submit team. Please try again.");
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
    // console.log(players);
    return (
        <AppLayout
            // alert={
            //     Number(balance) < Number(peer.amount) && (
            //         <div className="mt-3 flex items-center gap-2">
            //             <BatteryWarning size={17} color="red" />{" "}
            //             <FormError message="Insufficient balance to join tournament. Please fund your wallet." />
            //         </div>
            //     )
            // }
        >
            <main className="p-5">
                {/* Peer Info */}
                <div className=" py-3 bg-[var(--clr-surface-a10)] border-border/10">
                    <div className="flex items-center justify-between ">
                        <h2 className="text-[var(--clr-surface-a50)] tracking-wider  font-bold">
                            {peer.name}
                        </h2>
                        <Badge className="text-foreground">Joining</Badge>
                    </div>
                    <div className="flex items-center gap-3 mt-2">
                        <div className="flex items-center text-muted tracking-wider text-xs">
                            ₦
                            <span className="">
                                {Number(peer.amount).toFixed()}
                            </span>
                        </div>
                        <div className="flex items-center text-muted gap-1 text-xs">
                            <Users className="h-4 w-4" />
                            <span>
                                {peer.users_count || 0}/{peer.limit || "-"}
                            </span>
                        </div>
                        <div className="flex items-center text-muted gap-1 text-xs ">
                            <Clock className="h-4 w-4" />
                            <span>Active</span>
                        </div>
                    </div>
                </div>

                {/* Team Selection Progress */}
                <div className="p-1 backdrop-blur-md bg-default/50  border rounded-sm">
                    <div className="p-4 bg-white  border rounded-sm">
                        <div className="flex items-center justify-between mb-3">
                            <h3 className="text-headline text-[var(--clr-light-a0)]">
                                Select Your Team
                            </h3>
                            <Badge
                                variant="outline"
                                className="text-[var(--clr-primary-a0)] border-[var(--clr-primary-a0)]"
                            >
                                {selectedPlayers.length}/10 players
                            </Badge>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <h4 className="text-body font-semibold text-[var(--clr-light-a0)]">
                                    Main Squad
                                </h4>
                                <div className="flex gap-1">
                                    {[5, 4, 3, 2, 1].map((tier) => {
                                        const { count } = getTierProgress(
                                            tier,
                                            "main"
                                        );
                                        return (
                                            <div
                                                key={tier}
                                                className="flex items-center gap-1"
                                            >
                                                <Star
                                                    className={`h-3 w-3 ${
                                                        count > 0
                                                            ? getTierColor(tier)
                                                            : "text-[var(--clr-surface-a50)]"
                                                    } ${
                                                        count > 0
                                                            ? "fill-current"
                                                            : ""
                                                    }`}
                                                />
                                                {count > 0 && (
                                                    <Check className="h-3 w-3 text-[var(--clr-success-a0)]" />
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <h4 className="text-body font-semibold text-[var(--clr-light-a0)]">
                                    Substitutes
                                </h4>
                                <div className="flex gap-1">
                                    {[5, 4, 3, 2, 1].map((tier) => {
                                        const { count } = getTierProgress(
                                            tier,
                                            "sub"
                                        );
                                        return (
                                            <div
                                                key={tier}
                                                className="flex items-center gap-1"
                                            >
                                                <Star
                                                    className={`h-3 w-3 ${
                                                        count > 0
                                                            ? getTierColor(tier)
                                                            : "text-[var(--clr-surface-a50)]"
                                                    } ${
                                                        count > 0
                                                            ? "fill-current"
                                                            : ""
                                                    }`}
                                                />
                                                {count > 0 && (
                                                    <Check className="h-3 w-3 text-[var(--clr-success-a0)]" />
                                                )}
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
                    <Tabs value={activeTab} onValueChange={setActiveTab}>
                        <TabsList className="grid w-full grid-cols-5 mb-6 bg-transparent">
                            {[5, 4, 3, 2, 1].map((tier) => {
                                const isActive = activeTab === tier.toString();
                                return (
                                    <TabsTrigger
                                        key={tier}
                                        value={tier.toString()}
                                        className="text-muted data-[state=active]:border-b-muted data-[state=active]:border-b-3 data-[state=active]:text-muted-white data-[state=active]:rounded-none data-[state=active]:bg-transparent"
                                    >
                                        <div className="flex items-center gap-1">
                                            <Star
                                                className={`h-3 w-3 ${getTierColor(
                                                    tier
                                                )} fill-current`}
                                            />
                                            <span className="font-bold">
                                                {tier}
                                            </span>
                                        </div>
                                        {isActive && (
                                            <span className="mt-1 w-6 h-1 rounded bg-[var(--clr-light-a0)] block" />
                                        )}
                                    </TabsTrigger>
                                );
                            })}
                        </TabsList>

                        {[5, 4, 3, 2, 1].map((tier) => (
                            <TabsContent
                                key={tier}
                                value={tier.toString()}
                                className="space-y-4"
                            >
                                <div className="text-center mb-4">
                                    <h3 className="text-headline text-[var(--clr-light-a0)] mb-2">
                                        {tier}-Star Players
                                    </h3>
                                    <p className="text-caption text-[var(--clr-surface-a50)]">
                                        Select 1 for main squad and 1 for
                                        substitutes
                                    </p>
                                </div>

                                <div className="grid grid-cols-1 gap-3">
                                    {getPlayersByStar(tier).map((player) => {
                                        const isSelected = isPlayerSelected(
                                            player.player_match_id
                                        );
                                        const selectedPlayer =
                                            selectedPlayers.find(
                                                (p) =>
                                                    p.player_match_id ===
                                                    player.player_match_id
                                            );
                                        const mainCount = getTierProgress(
                                            tier,
                                            "main"
                                        ).count;
                                        const subCount = getTierProgress(
                                            tier,
                                            "sub"
                                        ).count;

                                        return (
                                            <Card
                                                key={player.player_match_id}
                                                className={cn(
                                                    "bg-card/5 rounded  p-0 transition-all",
                                                    isSelected &&
                                                        "ring-2 ring-primary shadow-glow"
                                                )}
                                            >
                                                <CardContent className="p-4">
                                                    {/* Player vs Team Layout */}
                                                    <div className="flex items-center mb-3 w-full justify-between">
                                                        {/* Player Side */}
                                                        <div className="flex items-center  ">
                                                            {/* Player Avatar or Icon */}
                                                            <div className="flex gap-3 items-center justify-center">
                                                                <Avatar className="rounded">
                                                                    <AvatarImage
                                                                        src={
                                                                            player.player_avatar
                                                                        }
                                                                        alt={
                                                                            player.player_name
                                                                        }
                                                                    />
                                                                    <AvatarFallback className="rounded size-7 uppercase">
                                                                        {player.player_name.substring(
                                                                            0,
                                                                            2
                                                                        )}
                                                                    </AvatarFallback>
                                                                </Avatar>
                                                                <div>
                                                                    <div className="font-bold text-muted-white text-base">
                                                                        {
                                                                            player.player_name
                                                                        }
                                                                    </div>
                                                                    <div className="text-xs text-muted">
                                                                        {
                                                                            player.player_position
                                                                        }
                                                                        -
                                                                        {
                                                                            player.player_team
                                                                        }
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {/* VS Divider */}
                                                        <div className="flex flex-col items-center mx-4">
                                                            <span className="bg-muted text-foreground font-bold px-2 py-1 rounded-full text-xs shadow">
                                                                VS
                                                            </span>
                                                        </div>

                                                        {/* Team Side */}
                                                        <div className="flex flex-col items-end min-w-[90px]">
                                                            <div className="flex items-center gap-2">
                                                                {/* Team Logo Placeholder  */}
                                                                <Avatar className="rounded">
                                                                    <AvatarImage
                                                                        src={
                                                                            player.against_team_image
                                                                        }
                                                                        alt={
                                                                            player.player_name
                                                                        }
                                                                    />
                                                                    <AvatarFallback className="rounded size-7 uppercase">
                                                                        {player.against_team_name.substring(
                                                                            0,
                                                                            2
                                                                        )}
                                                                    </AvatarFallback>
                                                                </Avatar>
                                                                <span className="font-semibold text-muted-white text-sm">
                                                                    {
                                                                        player.against_team_name
                                                                    }
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {/* Action Buttons */}
                                                    <div className="flex gap-2 mt-2">
                                                        <Button
                                                            size="sm"
                                                            variant={
                                                                selectedPlayer?.type ===
                                                                "main"
                                                                    ? "default"
                                                                    : "outline"
                                                            }
                                                            disabled={
                                                                mainCount >=
                                                                    1 &&
                                                                selectedPlayer?.type !==
                                                                    "main"
                                                            }
                                                            onClick={() =>
                                                                handlePlayerSelect(
                                                                    player,
                                                                    "main"
                                                                )
                                                            }
                                                            className={`flex-1 h-8 ${
                                                                selectedPlayer?.type ===
                                                                "main"
                                                                    ? "bg-[var(--clr-primary-a0)] text-muted"
                                                                    : "text-muted-white"
                                                            }`}
                                                        >
                                                            {selectedPlayer?.type ===
                                                            "main"
                                                                ? "Main ✓"
                                                                : "Main Squad"}
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant={
                                                                selectedPlayer?.type ===
                                                                "sub"
                                                                    ? "secondary"
                                                                    : "outline"
                                                            }
                                                            disabled={
                                                                subCount >= 1 &&
                                                                selectedPlayer?.type !==
                                                                    "sub"
                                                            }
                                                            onClick={() =>
                                                                handlePlayerSelect(
                                                                    player,
                                                                    "sub"
                                                                )
                                                            }
                                                            className={`flex-1 h-8 ${
                                                                selectedPlayer?.type ===
                                                                "sub"
                                                                    ? "bg-[var(--clr-secondary-a0)] text-muted"
                                                                    : "text-muted-white"
                                                            }`}
                                                        >
                                                            {selectedPlayer?.type ===
                                                            "sub"
                                                                ? "Sub ✓"
                                                                : "Substitute"}
                                                        </Button>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        );
                                    })}
                                </div>
                            </TabsContent>
                        ))}
                    </Tabs>
                </div>
            </main>

            <FloatingBetSlip
                selectedPlayers={selectedPlayers}
                onRemovePlayer={(playerId) => {
                    setSelectedPlayers((prev) =>
                        prev.filter((p) => p.player_match_id !== playerId)
                    );
                }}
                players={players}
                handleSubmitTeam={handleSubmitTeam}
                processing={false}
                
            />
        </AppLayout>
    );
}
