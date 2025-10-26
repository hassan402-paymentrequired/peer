import { index } from '@/actions/App/Http/Controllers/Tournament/TournamentController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Award, Calendar, DollarSign, Medal, Trophy, Users } from 'lucide-react';

interface User {
    id: number;
    username: string;
    avatar: string;
    email: string;
    created_at: string;
    squads: any[];
    total_point: number;
    is_winner: boolean;
    rank: number;
}

interface Tournament {
    id: string;
    name: string;
    amount: number;
    status: string;
    updated_at: string;
    created_at: string;
}

interface LeaderboardProps {
    tournament: Tournament | null;
    users: User[];
}

const Leaderboard = ({ tournament, users }: LeaderboardProps) => {
    const {
        auth: {
            user: { id },
        },
    } = usePage<{ auth: { user: { id: number } } }>().props;

    const getRankIcon = (position: number) => {
        if (position === 1) return <Trophy className="h-5 w-5 text-yellow-500" />;
        if (position === 2) return <Medal className="h-5 w-5 text-gray-400" />;
        if (position === 3) return <Award className="h-5 w-5 text-amber-600" />;
        return <span className="text-sm font-bold text-gray-600">#{position}</span>;
    };

    const getRankBadge = (position: number) => {
        if (position === 1) return 'bg-gradient-to-r from-yellow-400 to-yellow-600 text-white';
        if (position === 2) return 'bg-gradient-to-r from-gray-300 to-gray-500 text-white';
        if (position === 3) return 'bg-gradient-to-r from-amber-400 to-amber-600 text-white';
        return 'bg-gray-100 text-gray-600';
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    if (!tournament) {
        return (
            <AppLayout title="Tournament Leaderboard">
                <Head title="Tournament Leaderboard" />
                <div className="flex h-screen flex-col items-center justify-center">
                    <div className="flex max-w-md flex-col items-center p-6 text-center">
                        <Trophy className="mb-4 h-16 w-16 text-gray-400" />
                        <h2 className="mb-2 text-xl font-bold text-gray-700">No Recent Tournament</h2>
                        <p className="mb-6 text-gray-500">
                            There are no recently completed tournaments to display. Check back after a tournament ends!
                        </p>
                        <Link href={index()}>
                            <Button variant="outline" className="flex items-center gap-2">
                                <ArrowLeft className="h-4 w-4" />
                                Back to Tournament
                            </Button>
                        </Link>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout title={`${tournament.name} - Final Leaderboard`}>
            <Head title={`${tournament.name} - Final Leaderboard`} />

            <div className="min-h-screen bg-gray-50">
                {/* Header */}
                <div className="bg-white shadow-sm">
                    <div className="mx-auto max-w-7xl px-4 py-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <Link href={index()}>
                                    <Button variant="ghost" size="sm" className="flex items-center gap-2">
                                        <ArrowLeft className="h-4 w-4" />
                                        Back
                                    </Button>
                                </Link>
                                <div>
                                    <h1 className="text-2xl font-bold text-gray-900">{tournament.name}</h1>
                                    <p className="text-sm text-gray-500">Final Leaderboard</p>
                                </div>
                            </div>
                            <Badge variant="secondary" className="bg-green-100 text-green-800">
                                Tournament Completed
                            </Badge>
                        </div>
                    </div>
                </div>

                {/* Tournament Stats */}
                <div className="mx-auto max-w-7xl px-4 py-6">
                    {/* User's Performance Summary */}
                    {(() => {
                        const currentUser = users.find((user) => user.id.toString() === id.toString());
                        return currentUser ? (
                            <div className="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                                <h3 className="mb-2 text-sm font-semibold text-blue-900">Your Performance</h3>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className={`flex h-10 w-10 items-center justify-center rounded-full ${getRankBadge(currentUser.rank)}`}>
                                            {currentUser.rank <= 3 ? (
                                                getRankIcon(currentUser.rank)
                                            ) : (
                                                <span className="text-sm font-bold">#{currentUser.rank}</span>
                                            )}
                                        </div>
                                        <div>
                                            <p className="font-semibold text-blue-900">
                                                Rank #{currentUser.rank} of {users.length}
                                            </p>
                                            <p className="text-sm text-blue-700">{currentUser.total_point.toLocaleString()} points</p>
                                        </div>
                                    </div>
                                    {currentUser.is_winner && <Badge className="bg-yellow-100 text-yellow-800">🏆 Winner</Badge>}
                                </div>
                            </div>
                        ) : null;
                    })()}

                    <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div className="rounded-lg bg-white p-4 shadow-sm">
                            <div className="flex items-center gap-3">
                                <Calendar className="h-8 w-8 text-blue-500" />
                                <div>
                                    <p className="text-sm text-gray-500">Completed</p>
                                    <p className="font-semibold text-gray-900">{formatDate(tournament.updated_at)}</p>
                                </div>
                            </div>
                        </div>
                        <div className="rounded-lg bg-white p-4 shadow-sm">
                            <div className="flex items-center gap-3">
                                <Users className="h-8 w-8 text-green-500" />
                                <div>
                                    <p className="text-sm text-gray-500">Total Players</p>
                                    <p className="font-semibold text-gray-900">{users.length}</p>
                                </div>
                            </div>
                        </div>
                        <div className="rounded-lg bg-white p-4 shadow-sm">
                            <div className="flex items-center gap-3">
                                <DollarSign className="h-8 w-8 text-yellow-500" />
                                <div>
                                    <p className="text-sm text-gray-500">Prize Pool</p>
                                    <p className="font-semibold text-gray-900">₦{tournament.amount.toLocaleString()}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Top 3 Podium */}
                    {users.length >= 3 && (
                        <div className="mb-8 rounded-lg bg-white p-6 shadow-sm">
                            <h2 className="mb-6 text-center text-xl font-bold text-gray-900">🏆 Top 3 Champions</h2>
                            <div className="flex items-end justify-center gap-4">
                                {/* Second Place */}
                                <div className="flex flex-col items-center">
                                    <div className="mb-2 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-r from-gray-300 to-gray-500">
                                        <Medal className="h-8 w-8 text-white" />
                                    </div>
                                    <div className="h-20 w-24 rounded-t-lg bg-gradient-to-t from-gray-300 to-gray-400"></div>
                                    <div className="mt-2 text-center">
                                        <p className="font-semibold text-gray-900">@{users[1]?.username}</p>
                                        <p className="text-sm text-gray-500">{users[1]?.total_point.toLocaleString()} pts</p>
                                    </div>
                                </div>

                                {/* First Place */}
                                <div className="flex flex-col items-center">
                                    <div className="mb-2 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-r from-yellow-400 to-yellow-600">
                                        <Trophy className="h-10 w-10 text-white" />
                                    </div>
                                    <div className="h-28 w-28 rounded-t-lg bg-gradient-to-t from-yellow-400 to-yellow-500"></div>
                                    <div className="mt-2 text-center">
                                        <p className="font-bold text-gray-900">@{users[0]?.username}</p>
                                        <p className="text-sm font-semibold text-yellow-600">{users[0]?.total_point.toLocaleString()} pts</p>
                                        <Badge className="mt-1 bg-yellow-100 text-yellow-800">Champion</Badge>
                                    </div>
                                </div>

                                {/* Third Place */}
                                <div className="flex flex-col items-center">
                                    <div className="mb-2 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-r from-amber-400 to-amber-600">
                                        <Award className="h-8 w-8 text-white" />
                                    </div>
                                    <div className="h-16 w-24 rounded-t-lg bg-gradient-to-t from-amber-400 to-amber-500"></div>
                                    <div className="mt-2 text-center">
                                        <p className="font-semibold text-gray-900">@{users[2]?.username}</p>
                                        <p className="text-sm text-gray-500">{users[2]?.total_point.toLocaleString()} pts</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Full Leaderboard */}
                    <div className="rounded-lg bg-white shadow-sm">
                        <div className="border-b border-gray-200 px-6 py-4">
                            <h2 className="text-lg font-semibold text-gray-900">Complete Leaderboard</h2>
                        </div>

                        <div className="divide-y divide-gray-100">
                            {users.map((user) => {
                                const isCurrentUser = user.id.toString() === id.toString();

                                return (
                                    <div
                                        key={user.id}
                                        className={`flex items-center justify-between px-6 py-4 transition-colors hover:bg-gray-50 ${
                                            isCurrentUser ? 'border-l-4 border-l-blue-500 bg-blue-50' : ''
                                        }`}
                                    >
                                        <div className="flex items-center gap-4">
                                            {/* Rank */}
                                            <div className={`flex h-10 w-10 items-center justify-center rounded-full ${getRankBadge(user.rank)}`}>
                                                {user.rank <= 3 ? getRankIcon(user.rank) : <span className="text-sm font-bold">#{user.rank}</span>}
                                            </div>

                                            {/* Player Info */}
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <h3 className={`font-semibold ${isCurrentUser ? 'text-blue-900' : 'text-gray-900'}`}>
                                                        @{user.username}
                                                    </h3>
                                                    {isCurrentUser && (
                                                        <Badge variant="outline" className="border-blue-200 text-blue-800">
                                                            You
                                                        </Badge>
                                                    )}
                                                    {user.is_winner && <Badge className="bg-yellow-100 text-yellow-800">Winner</Badge>}
                                                </div>
                                                {user.rank <= 3 && (
                                                    <p className="text-sm text-gray-500">
                                                        {user.rank === 1 ? 'Tournament Champion' : user.rank === 2 ? 'Runner Up' : 'Third Place'}
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        {/* Points */}
                                        <div className="text-right">
                                            <div
                                                className={`inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold ${
                                                    user.rank === 1
                                                        ? 'bg-yellow-100 text-yellow-800'
                                                        : user.rank <= 3
                                                          ? 'bg-orange-100 text-orange-800'
                                                          : isCurrentUser
                                                            ? 'bg-blue-100 text-blue-800'
                                                            : 'bg-gray-100 text-gray-800'
                                                }`}
                                            >
                                                {user.total_point.toLocaleString()} pts
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {/* Empty state */}
                        {users.length === 0 && (
                            <div className="px-6 py-12 text-center">
                                <Users className="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <p className="text-gray-500">No players participated in this tournament</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
};

export default Leaderboard;
