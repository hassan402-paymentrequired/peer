import React, { useEffect, useState } from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
    Trophy,
    Users,
    DollarSign,
    Target,
    TrendingUp,
    Crown,
    Medal,
    ArrowLeft,
    ChevronDown,
    ChevronUp,
    Copy,
    Star,
    Shield,
    Zap,
    Activity,
    Award,
    ArrowDownRight,
    Timer,
    Flame
} from "lucide-react";
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from "@/components/ui/collapsible";
import { toast } from "sonner";
import AppLayout from "@/layouts/app-layout";
import { joinPeer } from "@/actions/App/Http/Controllers/Peer/PeerController";

interface PeerUser {
    id: number;
    user: {
        id: number;
        username: string;
        avatar?: string;
    };
    total_points: number;
    is_winner: boolean;
    created_at: string;
}

interface Peer {
    id: number;
    peer_id: string;
    name: string;
    amount: string;
    private: boolean;
    limit: number;
    sharing_ratio: number;
    status: "open" | "closed" | "finished";
    winner_user_id?: number;
    created_by: {
        id: number;
        username: string;
    };
    users: PeerUser[];
    users_count: number;
    created_at: string;
}

interface PeerShowProps  {
    peer: Peer;
    users: any[];
}

export default function PeerShow({ peer, users }: PeerShowProps) {
    const {
        auth: { user },
    } = usePage<{ auth: { user: any } }>().props;

    const [expandedUsers, setExpandedUsers] = useState(new Set());

    const sortedUsers = [...users].sort(
        (a, b) => b.total_points - a.total_points
    );

    const getMatch = async () => {
        await fetch(
            "https://www.sofascore.com/api/v1/event/12436883/player/975079/statistics",
            {
                headers: {
                    "Access-Control-Allow-Origin": "*",
                },
            }
        )
            .then((response) => response.json())
            .then((data) => console.log(data))
            .catch((e) => console.log(e));
    };

    useEffect(() => {
        const id = setInterval(() => {
            getMatch();
        }, 10000);

        return () => {
            clearInterval(id);
        };
    }, []);

    const getRankIcon = (index) => {
        switch (index) {
            case 0:
                return <Crown className="w-5 h-5 text-yellow-500" />;
            case 1:
                return <Medal className="w-5 h-5 text-gray-400" />;
            case 2:
                return <Medal className="w-5 h-5 text-amber-600" />;
            default:
                return (
                    <div className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                        <span className="text-sm font-bold text-slate-600">
                            {index + 1}
                        </span>
                    </div>
                );
        }
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'open':
                return 'bg-green-100 text-green-800 border-green-200';
            case 'closed':
                return 'bg-yellow-100 text-yellow-800 border-yellow-200';
            case 'finished':
                return 'bg-blue-100 text-blue-800 border-blue-200';
            default:
                return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    };

    const toggleUserExpanded = (userId) => {
        const newExpanded = new Set(expandedUsers);
        if (newExpanded.has(userId)) {
            newExpanded.delete(userId);
        } else {
            newExpanded.add(userId);
        }
        setExpandedUsers(newExpanded);
    };

    const handleCopy = async () => {
        await navigator.clipboard.writeText(peer.peer_id);
        toast.success("Peer code copied ✅");
    };

    const getPlayerStatusIcon = (didPlay) => {
        return didPlay ? (
            <div className="flex items-center gap-1">
                <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                <span className="text-[10px] md:text-xs text-green-600 font-medium">Playing</span>
            </div>
        ) : (
            <div className="flex items-center gap-1">
                <div className="w-2 h-2 bg-gray-400 rounded-full"></div>
                <span className="text-xs text-gray-500">Bench</span>
            </div>
        );
    };

    return (
        <AppLayout>
            <Head title={`Peer: ${peer.name}`} />
            <div className="min-h-screen p-4">
                <div className="max-w-4xl mx-auto space-y-6">
                    {/* Header Section */}
                    <div className="">
                        <div className="">
                            <div className="flex items-center justify-between mb-4">
                                <div className="flex items-center gap-4">
                                    <div className="p-3 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl text-white">
                                        <Trophy className="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h1 className="md:text-2xl font-semibold lg:font-bold text-slate-900 capitalize">
                                            {peer.name}
                                        </h1>
                                        <p className="text-slate-600 flex text-xs lg:text-sm items-center gap-2">
                                            <Users className="w-4 h-4" />
                                            {users.length} players competing
                                        </p>
                                    </div>
                                </div>
                                <Badge className={`px-1.5 md:px-3 py-1 text-[10px] md:text-sm font-semibold rounded-sm border ${getStatusColor(peer.status)}`}>
                                    {peer.status === "open" ? "🟢 Active" : peer.status}
                                </Badge>
                            </div>



                            {/* Peer ID Copy */}
                            <div className="bg-slate-50 rounded p-2 flex items-center justify-between border border-slate-200">
                                <div>
                                    <span className="text-xs md:text-sm text-slate-600">Peer Code:</span>
                                    <span className="ml-2 font-mono text-sm font-bold text-slate-900">{peer.peer_id?.substring(0,10)}</span>
                                </div>
                                <Button
                                    onClick={handleCopy}
                                    variant="outline"
                                    size="sm"
                                    className="flex items-center gap-2 hover:bg-slate-100"
                                >
                                    <Copy className="w-4 h-4" />
                                    Copy
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Players Leaderboard */}
                    <div className="space-y-4">
                        <h2 className="md:text-xl text-base font-semibold md:font-bold text-slate-900 flex items-center gap-2">
                            <Award className="w-5 h-5 " />
                            Peers
                        </h2>

                        {sortedUsers.length > 0 ? (
                            sortedUsers.map((userItem, index) => (
                                <Card key={userItem.id} className="overflow-hidden py-0 gap-0 rounded border-0 bg-white/80 backdrop-blur-sm">
                                    <div
                                        className=" cursor-pointer hover:bg-slate-50 transition-colors"
                                        onClick={() => toggleUserExpanded(userItem.id)}
                                    >
                                        <div className="flex items-center justify-between px-2 py-3">
                                            <div className="flex items-center gap-2 ">
                                                <div className="flex items-center gap-3">
                                                    <Avatar className="md:w-12 md:h-12 ring-2 ring-white shadow-md">
                                                        <AvatarFallback className="text-xs md:text-base bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold">
                                                            {userItem.username.substring(0, 2).toUpperCase()}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                </div>
                                                <div>
                                                    <div className="font-semibold md:font-bold text-sm md:text-lg text-slate-900">
                                                        @{userItem.username}
                                                    </div>
                                                    <div className="text-xs md:text-sm text-slate-600 flex items-center gap-2">
                                                        <Flame className="w-4 h-4 text-orange-500" />
                                                        {userItem.squads?.length || 0} stars selected
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-4">
                                                <div className="text-right">
                                                    <div className="text-2xl font-bold text-slate-900">
                                                        {userItem.total_points}
                                                    </div>
                                                    <div className="text-xs md:text-sm text-slate-500">points</div>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    {index === 0 && <Crown className="w-5 h-5 text-yellow-500" />}
                                                    {expandedUsers.has(userItem.id) ?
                                                        <ChevronUp className="w-5 h-5 text-slate-400" /> :
                                                        <ChevronDown className="w-5 h-5 text-slate-400" />
                                                    }
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <Collapsible open={expandedUsers.has(userItem.id)}>
                                        <CollapsibleContent>
                                            <div className="border-t bg-slate-50/50 p-1">
                                                <div className="space-y-6">
                                                    {userItem.squads && userItem.squads.length > 0 ? (
                                                        userItem.squads.map((squad, idx) => (
                                                            <div key={squad.id} className="bg-white rounded p-2 shadow border border-slate-200">
                                                                <div className="flex items-center gap-2 mb-4">
                                                                    <Star className="w-5 h-5 text-yellow-500" />
                                                                    <h4 className="font-bold text-slate-900">Star {idx + 1}</h4>
                                                                </div>

                                                                <div className="grid gap-4">
                                                                    {/* Main Player */}
                                                                    <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded p-4 border border-blue-200">
                                                                        <div className="flex items-center justify-between mb-3">
                                                                            <div className="flex items-center gap-3">
                                                                                <Badge className="bg-blue-600 text-white px-2 py-1">
                                                                                    Main
                                                                                </Badge>
                                                                                <span className="font-bold text-sm lg:text-base text-slate-900">
                                                                                    {squad.main_player?.name}
                                                                                </span>
                                                                                <Badge variant="outline" className="text-[10px] md:text-xs">
                                                                                    {squad.main_player?.position}
                                                                                </Badge>
                                                                            </div>
                                                                            {getPlayerStatusIcon(squad.main_player?.statistics?.did_play)}
                                                                        </div>

                                                                        <div className="grid grid-cols-4 md:grid-cols-8 gap-3 text-xs md:text-sm">
                                                                            <div className="text-center">
                                                                                <div className="font-bold text-sm md:text-lg text-slate-900">
                                                                                    {squad.main_player?.statistics?.goals ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">Goals</div>
                                                                            </div>
                                                                            <div className="text-center">
                                                                                <div className="font-bold text-sm md:text-lg text-slate-900">
                                                                                    {squad.main_player?.statistics?.assists ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">Assists</div>
                                                                            </div>
                                                                            <div className="text-center">
                                                                                <div className="font-bold text-sm md:text-lg text-slate-900">
                                                                                    {squad.main_player?.statistics?.shots_on ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">Shots On</div>
                                                                            </div>
                                                                            <div className="text-center">
                                                                                <div className="font-bold text-sm md:text-lg text-slate-900">
                                                                                    {squad.main_player?.statistics?.shots ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">Shots</div>
                                                                            </div>
                                                                            <div className="text-center">
                                                                                <div className="font-bold text-sm md:text-lg text-slate-900">
                                                                                    {squad.main_player?.statistics?.tackles_total ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">Tackles</div>
                                                                            </div>
                                                                            <div className="text-center">
                                                                                <div className="font-bold text-sm md:text-lg text-slate-900">
                                                                                    {squad.main_player?.statistics?.goals_saves ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">Saves</div>
                                                                            </div>
                                                                            <div className="text-center">
                                                                                <div className="text-sm md:text-lg">
                                                                                    {squad.main_player?.statistics?.cards_yellow > 0 && "🟨"}
                                                                                    {squad.main_player?.statistics?.cards_red > 0 && "🟥"}
                                                                                    {(!squad.main_player?.statistics?.cards_yellow && !squad.main_player?.statistics?.cards_red) && "✅"}
                                                                                </div>
                                                                                <div className="text-slate-600">Cards</div>
                                                                            </div>
                                                                            <div className="text-center">
                                                                                <div className="font-bold text-sm md:text-lg text-green-600">
                                                                                    ..%
                                                                                </div>
                                                                                <div className="text-slate-600">TP</div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    {/* Sub Player */}
                                                                    {squad.sub_player && (
                                                                        <div className="bg-gradient-to-r from-slate-50 to-gray-50 rounded p-4 border border-slate-200">
                                                                            <div className="flex items-center justify-between mb-3">
                                                                                <div className="flex items-center gap-3">
                                                                                    <Badge className="bg-slate-600 text-white px-2 py-1">
                                                                                        Sub
                                                                                    </Badge>
                                                                                    <span className="font-bold text-sm lg:text-base text-slate-900">
                                                                                        {squad.sub_player?.name}
                                                                                    </span>
                                                                                    <Badge variant="outline" className="text-[10px] md:text-xs">
                                                                                        {squad.sub_player?.position}
                                                                                    </Badge>
                                                                                </div>
                                                                                {getPlayerStatusIcon(squad.sub_player?.statistics?.did_play)}
                                                                            </div>

                                                                            <div className="grid grid-cols-4 md:grid-cols-8 gap-3 text-sm">
                                                                                <div className="text-center">
                                                                                    <div className="font-bold text-lg text-slate-900">
                                                                                        {squad.sub_player?.statistics?.goals ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Goals</div>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <div className="font-bold text-sm md:text-lg text-slate-900">
                                                                                        {squad.sub_player?.statistics?.assists ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Assists</div>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <div className="font-bold text-sm md:text-lg text-slate-900">
                                                                                        {squad.sub_player?.statistics?.shots_on ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Shots On</div>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <div className="font-bold text-sm md:text-lg text-slate-900">
                                                                                        {squad.sub_player?.statistics?.shots ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Shots</div>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <div className="font-bold text-sm md:text-lg text-slate-900">
                                                                                        {squad.sub_player?.statistics?.tackles_total ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Tackles</div>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <div className="font-bold text-sm md:text-lg text-slate-900">
                                                                                        {squad.sub_player?.statistics?.goals_saves ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Saves</div>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <div className="text-sm md:text-lg">
                                                                                        {squad.sub_player?.statistics?.cards_yellow > 0 && "🟨"}
                                                                                        {squad.sub_player?.statistics?.cards_red > 0 && "🟥"}
                                                                                        {(!squad.sub_player?.statistics?.cards_yellow && !squad.sub_player?.statistics?.cards_red) && "✅"}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Cards</div>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <div className="font-bold text-sm md:text-lg text-green-600">
                                                                                        ..%
                                                                                    </div>
                                                                                    <div className="text-slate-600">TP</div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        ))
                                                    ) : (
                                                        <div className="text-center py-8 text-slate-500">
                                                            No squad data available
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </CollapsibleContent>
                                    </Collapsible>
                                </Card>
                            ))
                        ) : (
                            <Card className="shadow-xl border-0 bg-white/80 backdrop-blur-sm">
                                <CardContent className="flex flex-col items-center justify-center py-16">
                                    <div className="text-6xl mb-6 animate-bounce">🏆</div>
                                    <h3 className="text-xl font-bold text-slate-900 mb-2">
                                        Be the First Champion!
                                    </h3>
                                    <p className="text-slate-600 mb-6 text-center max-w-md">
                                        No players have joined this peer yet. Join now and claim your spot in the competition.
                                    </p>
                                    <Link
                                        href={joinPeer(peer.peer_id)}
                                        prefetch
                                    >
                                        <Button
                                            className="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold px-8 py-3 rounded-xl shadow-lg transform hover:scale-105 transition-all duration-200"
                                        >
                                            Join Peer
                                            <ArrowDownRight className="w-5 h-5 ml-2" />
                                        </Button>
                                    </Link>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
