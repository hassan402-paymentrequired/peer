import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { create } from '@/routes/tournament';
import { Head, Link, usePage } from '@inertiajs/react';

const Tournament = ({ tournament, users }) => {
    console.log(users);
    const {
        auth: {
            user: { id },
        },
    } = usePage<{ auth: { user: { id: number } } }>().props;

    const isAmoung = () => {
        return tournament && users?.some((user) => user.id.toString() === id.toString());
    };

    return (
        <AppLayout title={tournament?.name ? tournament.name + 's' + ' 🏆 Leaderboard' : 'Tournament'}>
            <Head title={tournament?.name ? tournament.name + 's' + ' Tournament' : 'Tournament'} />

            {!tournament ? (
                <div className="flex h-screen flex-col items-center justify-center">
                    <div className="flex max-w-md flex-col items-center p-6 text-center">
                        <span className="mb-4 text-6xl">🏆</span>
                        <h2 className="text-muted-white mb-2 text-xl font-bold">No Tournament Today</h2>
                        <p className="mb-6 text-muted">
                            There's no active tournament at the moment. Check back later or stay tuned for upcoming competitions!
                        </p>
                        <div className="rounded-lg bg-gray-50 p-4">
                            <p className="text-sm text-muted">
                                💡 Tournaments are usually announced in advance. Make sure to follow updates so you don't miss out!
                            </p>
                        </div>
                    </div>
                </div>
            ) : (
                <div className="flex h-screen flex-col">
                   
                    {!isAmoung() ? (
                        <div className="flex justify-center py-8">
                            <div className="flex max-w-xs flex-col items-center p-6">
                                <span className="mb-2 animate-bounce text-4xl">🌍</span>
                                <div className="mb-3 text-center font-semibold text-muted">You haven't joined {tournament.name} yet!</div>
                                <p className="mb-4 text-center text-muted">
                                    Be part of the excitement—join the contest and compete with other players.
                                </p>
                                <Link
                                    className="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-primary transition"
                                    prefetch
                                    href={create()}
                                >
                                    <Button className="">
                                        <span>Join {tournament.name}</span>
                                        <span className="text-lg">⚔️</span>
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    ) : (
                        <div className="flex-1 bg-white ">
                            <div className="ml-1 mb-4">
                                {/* <h3 className="mb-1 text-lg font-semibold text-gray-800">🏆 Leaderboard</h3> */}
                                <p className="text-sm text-muted">Current tournament standings</p>
                            </div>

                            <div className=" overflow-hidden  border border-gray-200 shadow-sm">
                                {/* Header */}
                                <div className="grid grid-cols-12 items-center border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-3">
                                    <div className="col-span-2 text-sm font-semibold text-gray-700">Rank</div>
                                    <div className="col-span-7 text-sm font-semibold text-gray-700">Player</div>
                                    <div className="col-span-3 text-right text-sm font-semibold text-gray-700">Points</div>
                                </div>

                                {/* Leaderboard Rows */}
                                <div className="divide-y divide-gray-100">
                                    {users.map((user, i) => {
                                        const isCurrentUser = user.id.toString() === id.toString();
                                        const getRankIcon = (position) => {
                                            if (position === 1) return '🥇';
                                            if (position === 2) return '🥈';
                                            if (position === 3) return '🥉';
                                            return `#${position}`;
                                        };

                                        return (
                                            <div
                                                key={user.id}
                                                className={`grid grid-cols-12 items-center px-4 py-3 transition-colors hover:bg-gray-50 ${
                                                    isCurrentUser ? 'border-l-4 border-l-blue-500 bg-blue-50' : ''
                                                }`}
                                            >
                                                {/* Rank */}
                                                <div className="col-span-2">
                                                    <span
                                                        className={`inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold ${
                                                            i < 3
                                                                ? 'bg-gradient-to-r from-yellow-400 to-yellow-600 text-white'
                                                                : isCurrentUser
                                                                  ? 'bg-blue-100 text-blue-800'
                                                                  : 'bg-gray-100 text-gray-600'
                                                        }`}
                                                    >
                                                        {i < 3 ? getRankIcon(i + 1).slice(-1) : i + 1}
                                                    </span>
                                                </div>

                                                {/* Player Info */}
                                                <div className="col-span-7 flex items-center space-x-3">
                                                    <div className="flex-1">
                                                        <div className="flex items-center space-x-2">
                                                            <h4
                                                                className={`text-sm font-medium ${isCurrentUser ? 'text-blue-900' : 'text-gray-900'}`}
                                                            >
                                                                @{user.username.substring(0, 20)}
                                                                {user.username.length > 20 && '...'}
                                                            </h4>
                                                            {isCurrentUser && (
                                                                <span className="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                                                                    You
                                                                </span>
                                                            )}
                                                        </div>
                                                        {i < 3 && (
                                                            <p className="mt-0.5 text-xs text-gray-500">
                                                                {i === 0 ? 'Tournament Leader' : i === 1 ? 'Runner Up' : 'Third Place'}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>

                                                {/* Points */}
                                                <div className="col-span-3 text-right">
                                                    <span
                                                        className={`inline-flex items-center rounded-full px-2.5 py-1 text-sm font-semibold ${
                                                            i === 0
                                                                ? 'bg-yellow-100 text-yellow-800'
                                                                : i < 3
                                                                  ? 'bg-orange-100 text-orange-800'
                                                                  : isCurrentUser
                                                                    ? 'bg-blue-100 text-blue-800'
                                                                    : 'bg-gray-100 text-gray-800'
                                                        }`}
                                                    >
                                                        {user.total_point.toLocaleString()}
                                                    </span>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>

                                {/* Empty state for no users */}
                                {users.length === 0 && (
                                    <div className="px-4 py-8 text-center">
                                        <span className="mb-2 block text-4xl">👥</span>
                                        <p className="text-sm text-gray-500">No players have joined yet</p>
                                    </div>
                                )}
                            </div>

                            {/* Tournament Stats */}
                            {users.length > 0 && (
                                <div className=" mt-4  bg-gray-50 p-3">
                                    <div className="flex items-center justify-between text-sm text-gray-600">
                                        <span>
                                            Total Players: <strong>{users.length}</strong>
                                        </span>
                                        <span>
                                            Prize Pool: <strong>₦{tournament.amount}</strong>
                                        </span>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            )}
        </AppLayout>
    );
};

export default Tournament;
