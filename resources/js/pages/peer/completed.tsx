import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/peers';
import { Peer } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Calendar, ChevronDown, Crown, HandCoins, Target, Trophy, Users } from 'lucide-react';

interface Props {
    history: {
        data: Peer[];
    };
}

const CompletedPeerCard = ({ peer }: { peer: Peer }) => {
    const hasWinner = peer.winner_user_id !== null && peer.winner;
    const completedDate = new Date(peer.created_at);
    const totalPrizePool = Number(peer.amount) * (peer.users_count || 0);
    const winnerShare = totalPrizePool * (peer.sharing_ratio / 100);

    return (
        <Card className="mb-3 overflow-hidden border bg-background/10 p-0 shadow">
            <Collapsible>
                <CollapsibleTrigger className="group w-full cursor-pointer rounded transition hover:bg-[var(--clr-surface-a10)]">
                    <div className="flex items-center justify-between p-3">
                        <div className="flex items-center gap-3">
                            <Avatar className="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--clr-surface-a20)] shadow-sm ring ring-[#c4c4c4]">
                                <AvatarFallback className="rounded shadow-md">{peer.name.substring(0, 2).toUpperCase()}</AvatarFallback>
                            </Avatar>
                            <div className="flex flex-col items-start">
                                <div className="flex items-center gap-2">
                                    <span className="text-sm font-semibold text-gray-600 capitalize md:text-base">{peer.name}</span>
                                    {peer.status === 'finished' && (
                                        <Badge variant="outline" className="bg-green-50 text-xs text-green-700 dark:bg-green-900/20">
                                            <Trophy className="mr-1 h-3 w-3" />
                                            Finished
                                        </Badge>
                                    )}
                                </div>
                                <div className="text-[10px] text-gray-600 lg:text-xs">by @{peer.created_by.name}</div>
                            </div>
                        </div>
                        <ChevronDown className="h-5 w-5 text-muted transition-transform group-data-[state=open]:rotate-180" />
                    </div>
                </CollapsibleTrigger>

                <CollapsibleContent>
                    <div className="border-t border-border px-4 py-3">
                        {/* Stats Grid */}
                        <div className="mb-4 grid grid-cols-2 gap-4">
                            <div className="flex items-center gap-2">
                                <div className="flex size-10 items-center justify-center rounded-full shadow ring ring-background">
                                    <Users size={18} />
                                </div>
                                <div className="flex flex-col items-start">
                                    <small className="text-muted">Participants</small>
                                    <span className="text-muted-white font-semibold">{peer.users_count || 0}</span>
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <div className="flex size-10 items-center justify-center rounded-full shadow ring ring-background">
                                    <HandCoins size={18} />
                                </div>
                                <div className="flex flex-col items-start">
                                    <small className="text-muted">Entry Fee</small>
                                    <span className="text-muted-white font-semibold">₦{Number(peer.amount).toFixed()}</span>
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <div className="flex size-10 items-center justify-center rounded-full shadow ring ring-background">
                                    <Trophy size={18} />
                                </div>
                                <div className="flex flex-col items-start">
                                    <small className="text-muted">Prize Pool</small>
                                    <span className="text-muted-white font-semibold">₦{totalPrizePool.toFixed()}</span>
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <div className="flex size-10 items-center justify-center rounded-full shadow ring ring-background">
                                    <Calendar size={18} />
                                </div>
                                <div className="flex flex-col items-start">
                                    <small className="text-muted">Completed</small>
                                    <span className="text-muted-white text-xs font-semibold">{completedDate.toLocaleDateString()}</span>
                                </div>
                            </div>
                        </div>

                        {/* Winner Section */}
                        {hasWinner && (
                            <div className="mb-4 rounded-lg border border-yellow-200 bg-gradient-to-r from-yellow-50 to-amber-50 p-3 dark:border-yellow-800 dark:from-yellow-900/20 dark:to-amber-900/20">
                                <div className="mb-2 flex items-center gap-2">
                                    <Crown className="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                                    <span className="text-xs font-semibold text-yellow-800 dark:text-yellow-300">Contest Winner</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <Avatar className="h-10 w-10 border-2 border-yellow-400 dark:border-yellow-600">
                                            <AvatarFallback className="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                {peer.winner!.name.substring(0, 2).toUpperCase()}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="flex flex-col">
                                            <span className="text-sm font-bold text-yellow-900 dark:text-yellow-100">@{peer.winner!.name}</span>
                                            <div className="flex items-center gap-2">
                                                <span className="text-xs font-semibold text-yellow-800 dark:text-yellow-200">
                                                    Won ₦{winnerShare.toFixed(2)}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <Badge className="bg-yellow-600 text-white">{peer.sharing_ratio}%</Badge>
                                </div>
                            </div>
                        )}

                        {/* Participants Preview */}
                        {peer.users && peer.users.length > 0 && (
                            <div className="mb-4 rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                <div className="mb-2 text-xs font-medium text-gray-600 dark:text-gray-400">Top Participants</div>
                                <div className="flex -space-x-2">
                                    {peer.users.slice(0, 5).map((peerUser) => (
                                        <Avatar
                                            key={peerUser.id}
                                            className="h-8 w-8 rounded-full border-2 border-white dark:border-gray-800"
                                            title={peerUser.user.username || ''}
                                        >
                                            <AvatarFallback className="text-xs font-semibold">
                                                {(peerUser.user.username || '?').substring(0, 2).toUpperCase()}
                                            </AvatarFallback>
                                        </Avatar>
                                    ))}
                                    {peer.users.length > 5 && (
                                        <div className="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-gray-200 text-xs font-semibold text-gray-600 dark:border-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            +{peer.users.length - 5}
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Action Button */}
                        <Link href={show(peer.peer_id)} className="w-full" prefetch>
                            <Button className="w-full text-sm font-medium" size="sm">
                                <Target className="mr-1 h-3 w-3" />
                                View Details
                            </Button>
                        </Link>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </Card>
    );
};

const Contests = ({ history }: Props) => {
    const totalCompleted = history?.data?.length || 0;
    const totalPrizeMoney = history?.data?.reduce((sum, peer) => sum + Number(peer.amount) * (peer.users_count || 0), 0) || 0;

    return (
        <AppLayout title="My Contests">
            <Head title="Peers - completed contests" />
            <div className="mt-2 flex w-full flex-col">
                <div className="mb-3 flex items-center justify-between px-3">
                    <div>
                        <h2 className="font-['Google Sans Code'] text-lg font-semibold">Completed Peers</h2>
                        {totalCompleted > 0 && (
                            <p className="text-xs text-muted-foreground">
                                {totalCompleted} {totalCompleted === 1 ? 'peer' : 'peers'} • ₦{totalPrizeMoney.toFixed()} total prize pool
                            </p>
                        )}
                    </div>
                </div>

                <div className="w-full p-3">
                    {!history?.data?.length && (
                        <div className="relative overflow-hidden rounded border-2 border-dashed border-gray-300 bg-gradient-to-br from-gray-50 to-gray-100 dark:border-gray-700 dark:from-gray-900 dark:to-gray-800">
                            <CardContent className="p-4">
                                <div className="flex flex-col items-center text-center">
                                    <div className="mb-4 rounded-full bg-gray-200 p-4 dark:bg-gray-800">
                                        <Trophy className="h-8 w-8 text-gray-400" />
                                    </div>
                                    <h3 className="mb-2 text-lg font-bold text-gray-700 dark:text-gray-300">No Completed Peers</h3>
                                    <p className="mb-6 max-w-md text-xs text-gray-600 dark:text-gray-400">
                                        You haven't completed any peer contests yet. Join active peers on the dashboard to start competing and
                                        winning!
                                    </p>
                                    <div className="flex flex-col gap-3 sm:flex-row">
                                        <Link href={dashboard()} prefetch>
                                            <Button size={'sm'}>
                                                <Users className="mr-2 h-4 w-4" />
                                                <span>Find Peers</span>
                                            </Button>
                                        </Link>
                                    </div>
                                </div>
                            </CardContent>
                        </div>
                    )}

                    {history?.data?.length > 0 && (
                        <div className="space-y-2">
                            {history.data.map((peer) => (
                                <CompletedPeerCard peer={peer} key={peer.id} />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
};

export default Contests;
