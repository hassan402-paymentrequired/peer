import { useState } from "react";
import { Star, Minus, Users, Trophy, Target, LoaderIcon } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/components/ui/sheet";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { cn } from "@/lib/utils";
import {usePage} from "@inertiajs/react"

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
}

interface SelectedPlayer extends Player {
    type: "main" | "sub";
}

interface FloatingBetSlipProps {
    selectedPlayers: SelectedPlayer[];
    onRemovePlayer: (playerId: number) => void;
    players: Array<{ star: number; players: Player[] }>;
    handleSubmitTeam: () => void;
    processing: boolean;
}

export const FloatingBetSlip = ({
    selectedPlayers,
    onRemovePlayer,
    players,
    handleSubmitTeam,
    processing
}: FloatingBetSlipProps) => {
const {flash} = usePage().props
console.log(flash)
    const [isOpen, setIsOpen] = useState(false);

    const totalSelected = selectedPlayers.length;
    const mainPlayers = selectedPlayers.filter((p) => p.type === "main");
    const subPlayers = selectedPlayers.filter((p) => p.type === "sub");

    const getPlayerStarRating = (playerId: number) => {
        for (const group of players) {
            const player = group.players.find((p) => p.player_id === playerId);
            if (player) {
                return group.star;
            }
        }
        return 1;
    };


    // Group players by star rating
    const groupedPlayers = [5, 4, 3, 2, 1]
        .map((star) => {
            const mainPlayer = mainPlayers.find(
                (p) => getPlayerStarRating(p.player_id) === star
            );
            const subPlayer = subPlayers.find(
                (p) => getPlayerStarRating(p.player_id) === star
            );

            return {
                star,
                mainPlayer,
                subPlayer,
                hasPlayers: mainPlayer || subPlayer,
            };
        })
        .filter((group) => group.hasPlayers);

    if (totalSelected === 0) return null;

    return (
        <Sheet open={isOpen} onOpenChange={setIsOpen} >
            <SheetTrigger asChild>
                <Button
                    className="fixed bottom-20 left-1/2 transform -translate-x-1/2 h-16 w-16 rounded-full bg-gradient-to-r from-primary to-secondary shadow-2xl hover:shadow-3xl transition-all duration-300 border-2 border-white/20 z-50 group"
                    size="sm"
                >
                    <div className="flex flex-col items-center relative">
                        <div className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold animate-pulse">
                            {totalSelected}
                        </div>
                        <Trophy className="h-6 w-6 fill-current text-white group-hover:scale-110 transition-transform duration-200" />
                        <span className="text-xs font-bold text-white mt-1">
                            Squard
                        </span>
                    </div>
                </Button>
            </SheetTrigger>

            <SheetContent side="bottom" className="h-[85vh]">
                <SheetHeader className=" pb-1">
                    <SheetTitle className="flex items-center gap-3">
                        <Trophy className="h-6 w-6 text-muted-white" />
                        <div>
                            <div className="text-xl font-semibold text-muted">
                                Your Squard
                            </div>
                            <div className="text-sm text-gray-500">
                                {mainPlayers.length} Main • {subPlayers.length}{" "}
                                Subs
                            </div>
                        </div>
                    </SheetTitle>
                </SheetHeader>

                <div className="space-y-4  pt-4 overflow-y-auto h-full px-5 pb-10">
                    {groupedPlayers.map((group) => (
                        <Card
                            key={group.star}
                            className="px-4 py-3 shadow border-border bg-card/30 rounded ring ring-background hover:shadow-md transition-shadow duration-200"
                        >
                            <CardHeader className="pb-3 p-0">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <div className="flex">
                                            {Array.from(
                                                { length: 5 },
                                                (_, i) => (
                                                    <Star
                                                        key={i}
                                                        className={cn(
                                                            "h-4 w-4",
                                                            i < group.star
                                                                ? "text-yellow-500 fill-current"
                                                                : "text-gray-300"
                                                        )}
                                                    />
                                                )
                                            )}
                                        </div>
                                        <span className="font-medium text-gray-900">
                                            {group.star}-Star Tier
                                        </span>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        className={cn(
                                            "text-xs",
                                            group.mainPlayer && group.subPlayer
                                                ? "bg-green-50 text-green-700 border-green-200"
                                                : "bg-orange-50 text-orange-700 border-orange-200"
                                        )}
                                    >
                                        {group.mainPlayer && group.subPlayer
                                            ? "Complete"
                                            : "Partial"}
                                    </Badge>
                                </div>
                            </CardHeader>

                            <CardContent className="space-y-3 p-0">
                                {/* Main Player */}
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <Target className="h-4 w-4 text-gray-600" />
                                        <span className="font-medium text-sm text-gray-700">
                                            Main Squad
                                        </span>
                                    </div>
                                    {group.mainPlayer ? (
                                        <div className="bg-card/5 border-2 border-border rounded-sm p-3 transition-colors duration-200">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-3 flex-1">
                                                    <div className="w-10 h-10 rounded-full bg-background flex items-center justify-center text-gray-700 font-semibold text-sm">
                                                        {group.mainPlayer.player_name
                                                            .substring(0, 2)
                                                            .toUpperCase()}
                                                    </div>
                                                    <div className="flex-1">
                                                        <div className="font-medium text-gray-900">
                                                            {
                                                                group.mainPlayer
                                                                    .player_name
                                                            }
                                                        </div>
                                                        <div className="text-xs text-muted flex items-center gap-2">
                                                            <span>
                                                                {
                                                                    group
                                                                        .mainPlayer
                                                                        .player_team
                                                                }
                                                            </span>
                                                            <span>•</span>
                                                            <span>
                                                                {
                                                                    group
                                                                        .mainPlayer
                                                                        .player_position
                                                                }
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        onRemovePlayer(
                                                            group.mainPlayer!
                                                                .player_match_id
                                                        )
                                                    }
                                                    className="h-8 w-8 p-0 hover:bg-red-50 hover:text-red-600 transition-colors duration-200"
                                                >
                                                    <Minus className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center">
                                            <span className="text-sm text-gray-700">
                                                No main player selected
                                            </span>
                                        </div>
                                    )}
                                </div>

                                {/* Sub Player */}
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <Users className="h-4 w-4 text-gray-600" />
                                        <span className="font-medium text-sm text-gray-700">
                                            Substitute
                                        </span>
                                    </div>
                                    {group.subPlayer ? (
                                        <div className="bg-card/50 border-2 border-border rounded-sm p-3 transition-colors duration-200">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-3 flex-1">
                                                    <div className="w-10 h-10 uppercase rounded-full bg-background flex items-center  justify-center text-gray-700 font-semibold text-sm">
                                                        {group.subPlayer.player_name.substring(
                                                            0,
                                                            2
                                                        )}
                                                    </div>
                                                    <div className="flex-1">
                                                        <div className="font-medium text-gray-900">
                                                            {
                                                                group.subPlayer
                                                                    .player_name
                                                            }
                                                        </div>
                                                        <div className="text-xs text-muted flex items-center gap-2">
                                                            <span>
                                                                {
                                                                    group
                                                                        .subPlayer
                                                                        .player_team
                                                                }
                                                            </span>
                                                            <span>•</span>
                                                            <span>
                                                                {
                                                                    group
                                                                        .subPlayer
                                                                        .player_position
                                                                }
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        onRemovePlayer(
                                                            group.subPlayer!
                                                                .player_match_id
                                                        )
                                                    }
                                                    className="h-8 w-8 p-0 hover:bg-red-50 hover:text-red-600 transition-colors duration-200"
                                                >
                                                    <Minus className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center">
                                            <span className="text-sm text-gray-500">
                                                No substitute selected
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    ))}

                    {/* Team Summary */}
                    {totalSelected > 0 && (
                        <Card className="bg-gray-50 border border-gray-200">
                            <CardContent className="p-4">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <div className="font-semibold text-gray-900">
                                            Team Summary
                                        </div>
                                        <div className="text-sm text-gray-500">
                                            {totalSelected}/10 players selected
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-xl font-bold text-gray-900">
                                            {Math.round(
                                                (totalSelected / 10) * 100
                                            )}
                                            %
                                        </div>
                                        <div className="text-xs text-gray-500">
                                            Complete
                                        </div>
                                    </div>
                                </div>

                                {/* Progress Bar */}
                                <div className="mt-3 w-full bg-gray-200 rounded-full h-2">
                                    <div
                                        className="bg-gray-700 h-2 rounded-full transition-all duration-500"
                                        style={{
                                            width: `${
                                                (totalSelected / 10) * 100
                                            }%`,
                                        }}
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    )}
                            {selectedPlayers.length === 10 && (

                                    <Button
                                        onClick={handleSubmitTeam}
                                        disabled={processing}
                                        className="w-full text-white tracking-wider font-bold shadow-floating"
                                    >
                                        {processing && (
                                            <LoaderIcon className="animate-spin" />
                                        )}
                                        Submit Team & Join Peer
                                    </Button>

                            )}
                </div>
            </SheetContent>
        </Sheet>
    );
};
