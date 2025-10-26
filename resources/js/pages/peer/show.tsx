/* eslint-disable @typescript-eslint/no-explicit-any */
import { joinPeer } from '@/actions/App/Http/Controllers/Peer/PeerController';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Collapsible, CollapsibleContent } from '@/components/ui/collapsible';
import AppLayout from '@/layouts/app-layout';
import { PeerShowProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowDownRight, Award, ChevronDown, ChevronUp, Copy, Crown, Flame, Star, Trophy, Users } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function PeerShow({ peer, users }: PeerShowProps) {
    const {
        auth: { user },
    } = usePage<{ auth: { user: any } }>().props;

    const [expandedUsers, setExpandedUsers] = useState(new Set());

    const sortedUsers = [...users].sort((a, b) => b.total_points - a.total_points);

    const getStatusColor = (status: string) => {
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

    const toggleUserExpanded = (userId: number) => {
        const newExpanded = new Set(expandedUsers);
        if (newExpanded.has(userId)) {
            newExpanded.delete(userId);
        } else {
            newExpanded.add(userId);
        }
        setExpandedUsers(newExpanded);
    };

    const handleCopy = async () => {
        const fullUrl = `${window.location.origin}/peers/join/${peer.peer_id}`;
        await navigator.clipboard.writeText(fullUrl);
        toast.success('Peer link copied ✅');
    };

    const getPlayerStatusIcon = (didPlay: boolean) => {
        return didPlay ? (
            <div className="flex items-center gap-1">
                <div className="h-2 w-2 rounded-full bg-green-500"></div>
                <span className="text-[10px] font-medium text-green-600 md:text-xs">Playing</span>
            </div>
        ) : (
            <div className="flex items-center gap-1">
                <div className="h-2 w-2 rounded-full bg-gray-400"></div>
                <span className="text-xs text-gray-500">Bench</span>
            </div>
        );
    };

    console.log(users);

    return (
        <AppLayout title={`Peer: ${peer.name}`}>
            <Head title={`Peer: ${peer.name}`} />
            <div className="min-h-screen p-4">
                <div className="mx-auto max-w-4xl space-y-6">
                    {/* Header Section */}
                    <div className="">
                        <div className="">
                            <div className="mb-4 flex items-center justify-between">
                                <div className="flex items-center gap-4">
                                    <div className="rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 p-3 text-white">
                                        <Trophy className="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h1 className="font-semibold text-slate-900 capitalize md:text-2xl lg:font-bold">{peer.name}</h1>
                                        <p className="flex items-center gap-2 text-xs text-slate-600 lg:text-sm">
                                            <Users className="h-4 w-4" />
                                            {users.length} players competing
                                        </p>
                                    </div>
                                </div>
                                <Badge
                                    className={`rounded-sm border px-1.5 py-1 text-[10px] font-semibold md:px-3 md:text-sm ${getStatusColor(peer.status)}`}
                                >
                                    {peer.status === 'open' ? '🟢 Active' : peer.status}
                                </Badge>
                            </div>

                            {/* Peer ID Copy */}
                            <div className="flex items-center justify-between rounded border border-slate-200 bg-slate-50 p-2">
                                <div>
                                    <span className="text-xs text-slate-600 md:text-sm">Share Peer:</span>
                                    <span className="ml-2 font-mono text-sm font-bold text-slate-900">{peer.peer_id?.substring(0, 10)}...</span>
                                </div>
                                <Button onClick={handleCopy} variant="outline" size="sm" className="flex items-center gap-2 hover:bg-slate-100">
                                    <Copy className="h-4 w-4" />
                                    Copy Link
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Players Leaderboard */}
                    <div className="space-y-4">
                        <h2 className="flex items-center gap-2 text-base font-semibold text-slate-900 md:text-xl md:font-bold">
                            <Award className="h-5 w-5" />
                            Peers
                        </h2>

                        {sortedUsers.length > 0 ? (
                            sortedUsers.map((userItem, index) => (
                                <Card key={userItem.id} className="gap-0 overflow-hidden rounded border-0 bg-white/80 py-0 backdrop-blur-sm">
                                    <div
                                        className="cursor-pointer transition-colors hover:bg-slate-50"
                                        onClick={() => toggleUserExpanded(userItem.id)}
                                    >
                                        <div className="flex items-center justify-between px-2 py-3">
                                            <div className="flex items-center gap-2">
                                                <div className="flex items-center gap-3">
                                                    <Avatar className="shadow-md ring-2 ring-white md:h-12 md:w-12">
                                                        <AvatarFallback className="bg-gradient-to-r from-blue-500 to-purple-600 text-xs font-bold text-white md:text-base">
                                                            {userItem.username.substring(0, 2).toUpperCase()}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                </div>
                                                <div>
                                                    <div className="text-sm font-semibold text-slate-900 md:text-lg md:font-bold">
                                                        @{userItem.username}
                                                    </div>
                                                    <div className="flex items-center gap-2 text-xs text-slate-600 md:text-sm">
                                                        <Flame className="h-4 w-4 text-orange-500" />
                                                        {userItem.squads?.length || 0} stars selected
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-4">
                                                <div className="text-right">
                                                    <div className="text-sm font-bold text-slate-900 sm:text-xl">{userItem.total_points}</div>
                                                    <div className="text-xs text-slate-500 md:text-sm">points</div>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    {index === 0 && <Crown className="h-5 w-5 text-yellow-500" />}
                                                    {expandedUsers.has(userItem.id) ? (
                                                        <ChevronUp className="h-5 w-5 text-slate-400" />
                                                    ) : (
                                                        <ChevronDown className="h-5 w-5 text-slate-400" />
                                                    )}
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
                                                            <div key={squad.id} className="rounded border border-slate-200 bg-white p-2 shadow">
                                                                <div className="mb-4 flex items-center gap-2">
                                                                    <Star className="h-5 w-5 text-yellow-500" />
                                                                    <h4 className="font-bold text-slate-900">Star {idx + 1}</h4>
                                                                </div>

                                                                <div className="grid gap-4">
                                                                    {/* Main Player */}
                                                                    <div className="rounded border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-4">
                                                                        <div className="mb-3 flex items-center justify-between">
                                                                            <div className="flex items-center gap-3">
                                                                                <Badge className="bg-blue-600 px-2 py-1 text-white">Main</Badge>
                                                                                <span className="text-sm font-bold text-slate-900 lg:text-base">
                                                                                    {squad.main_player?.name}
                                                                                </span>
                                                                                <Badge variant="outline" className="text-[10px] md:text-xs">
                                                                                    {squad.main_player?.position}
                                                                                </Badge>
                                                                            </div>
                                                                            {getPlayerStatusIcon(squad.main_player?.statistics?.did_play)}
                                                                        </div>

                                                                        <div className="grid grid-cols-4 gap-3 text-xs md:grid-cols-7 md:text-sm">
                                                                            <div className="text-center">
                                                                                <div className="text-sm font-bold text-slate-900 md:text-lg">
                                                                                    {squad.main_player?.statistics?.goals_total ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">Goals</div>
                                                                            </div>
                                                                            <div className="text-center">
                                                                                <div className="text-sm font-bold text-slate-900 md:text-lg">
                                                                                    {squad.main_player?.statistics?.goals_assists ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">Assists</div>
                                                                            </div>
                                                                            <div className="text-center">
                                                                                <div className="text-sm font-bold text-slate-900 md:text-lg">
                                                                                    {squad.main_player?.statistics?.shots_on_target ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">Shots On</div>
                                                                            </div>
                                                                            <div className="text-center">
                                                                                <div className="text-sm font-bold text-slate-900 md:text-lg">
                                                                                    {squad.main_player?.statistics?.shots_total ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">Shots</div>
                                                                            </div>
                                                                            <div className="text-center">
                                                                                <div className="text-sm font-bold text-slate-900 md:text-lg">
                                                                                    {squad.main_player?.statistics?.tackles_total ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">Tackles</div>
                                                                            </div>
                                                                            <div className="text-center">
                                                                                <div className="text-sm font-bold text-slate-900 md:text-lg">
                                                                                    {squad.main_player?.statistics?.goals_saves ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">Saves</div>
                                                                            </div>

                                                                            <div className="text-center">
                                                                                <div className="text-sm md:text-lg">
                                                                                    {squad.main_player?.statistics?.red_cards ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">🟥 Cards</div>
                                                                            </div>

                                                                            <div className="text-center">
                                                                                <div className="text-sm md:text-lg">
                                                                                    {squad.main_player?.statistics?.yellow_cards ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">🟨 Cards</div>
                                                                            </div>

                                                                            {squad.main_player?.statistics?.position === 'G' ||
                                                                                (squad.main_player?.statistics?.position === 'D' && (
                                                                                    <div className="text-center">
                                                                                        <div className="text-sm font-bold text-green-600 md:text-lg">
                                                                                            {squad.main_player?.statistics?.clean_sheet ?? 0}
                                                                                        </div>
                                                                                        <div className="text-slate-600">Clean sheet</div>
                                                                                    </div>
                                                                                ))}

                                                                            <div className="text-center">
                                                                                <div className="text-sm font-bold text-green-600 md:text-lg">
                                                                                    {squad.main_player?.statistics?.total_point ?? 0}
                                                                                </div>
                                                                                <div className="text-slate-600">Points</div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    {/* Sub Player */}
                                                                    {squad.sub_player && (
                                                                        <div className="rounded border border-slate-200 bg-gradient-to-r from-slate-50 to-gray-50 p-4">
                                                                            <div className="mb-3 flex items-center justify-between">
                                                                                <div className="flex items-center gap-3">
                                                                                    <Badge className="bg-slate-600 px-2 py-1 text-white">Sub</Badge>
                                                                                    <span className="text-sm font-bold text-slate-900 lg:text-base">
                                                                                        {squad.sub_player?.name}
                                                                                    </span>
                                                                                    <Badge variant="outline" className="text-[10px] md:text-xs">
                                                                                        {squad.sub_player?.position}
                                                                                    </Badge>
                                                                                </div>
                                                                                {getPlayerStatusIcon(squad.sub_player?.statistics?.did_play)}
                                                                            </div>

                                                                            <div className="grid grid-cols-4 gap-3 text-sm md:grid-cols-8">
                                                                                <div className="text-center">
                                                                                    <div className="text-lg font-bold text-slate-900">
                                                                                        {squad.sub_player?.statistics?.goals_total ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Goals</div>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <div className="text-sm font-bold text-slate-900 md:text-lg">
                                                                                        {squad.sub_player?.statistics?.goals_assists ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Assists</div>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <div className="text-sm font-bold text-slate-900 md:text-lg">
                                                                                        {squad.sub_player?.statistics?.shots_on_target ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Shots On</div>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <div className="text-sm font-bold text-slate-900 md:text-lg">
                                                                                        {squad.sub_player?.statistics?.shots_total ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Shots</div>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <div className="text-sm font-bold text-slate-900 md:text-lg">
                                                                                        {squad.sub_player?.statistics?.tackles_total ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Tackles</div>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <div className="text-sm font-bold text-slate-900 md:text-lg">
                                                                                        {squad.sub_player?.statistics?.goals_saves ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Saves</div>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <div className="text-sm md:text-lg">
                                                                                        {squad.sub_player?.statistics?.red_cards ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">🟥 Cards</div>
                                                                                </div>

                                                                                <div className="text-center">
                                                                                    <div className="text-sm md:text-lg">
                                                                                        {squad.sub_player?.statistics?.yellow_cards ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">🟨 Cards</div>
                                                                                </div>

                                                                                {squad.main_player?.statistics?.position === 'G' ||
                                                                                    (squad.main_player?.statistics?.position === 'D' && (
                                                                                        <div className="text-center">
                                                                                            <div className="text-sm font-bold text-green-600 md:text-lg">
                                                                                                {squad.sub_player?.statistics?.clean_sheet ?? 0}
                                                                                            </div>
                                                                                            <div className="text-slate-600">Clean sheet</div>
                                                                                        </div>
                                                                                    ))}
                                                                                <div className="text-center">
                                                                                    <div className="text-sm font-bold text-green-600 md:text-lg">
                                                                                        {squad.sub_player?.statistics?.total_point ?? 0}
                                                                                    </div>
                                                                                    <div className="text-slate-600">Points</div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        ))
                                                    ) : (
                                                        <div className="py-8 text-center text-slate-500">No squad data available</div>
                                                    )}
                                                </div>
                                            </div>
                                        </CollapsibleContent>
                                    </Collapsible>
                                </Card>
                            ))
                        ) : (
                            <CardContent className="flex flex-col items-center justify-center">
                                <div className="mb-6 animate-bounce text-6xl">🏆</div>
                                <h3 className="mb-2 text-xl font-bold text-slate-900">Be the First Champion!</h3>
                                <p className="mb-6 max-w-md text-center text-slate-600">
                                    No players have joined this peer yet. Join now and claim your spot in the competition.
                                </p>
                                <Link href={joinPeer(peer.peer_id)} prefetch>
                                    <Button className="transform rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 px-8 py-3 font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-blue-600 hover:to-purple-700">
                                        Join Peer
                                        <ArrowDownRight className="ml-2 h-5 w-5" />
                                    </Button>
                                </Link>
                            </CardContent>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
