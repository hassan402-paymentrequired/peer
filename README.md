import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import AppLayout from '@/layouts/app-layout';
import { dashboardBreadcrumbs } from '@/lib/breadcrumbs';
import 'swiper/css';

import { create, joinPeer } from '@/actions/App/Http/Controllers/Peer/PeerController';
import { create as joinTour } from '@/actions/App/Http/Controllers/Peer/PeerController';
import { show } from '@/routes/peers';
import { Head, Link } from '@inertiajs/react';
import { ArrowDownRightSquareIcon, CupSoda, HandCoins, Target, Users, Trophy, Calendar, Sparkles } from 'lucide-react';
import { Swiper, SwiperSlide } from 'swiper/react';

export default function Dashboard({ tournament, recents, peers }) {
    return (
        <AppLayout breadcrumbs={dashboardBreadcrumbs}>
            <Head title="Peers" />
            <div className="space-y-6 p-3 md:p-6">
                {/* Tournament Section */}
                {tournament ? (
                    <Card className="relative overflow-hidden rounded-xl border-0 shadow-2xl">
                        <div
                            className="absolute inset-0 z-0"
                            style={{
                                backgroundImage: "url('/images/tour.jpg')",
                                backgroundSize: 'cover',
                                backgroundPosition: 'center',
                            }}
                            aria-hidden="true"
                        />
                        <div className="absolute inset-0 z-[1] bg-gradient-to-br from-black/70 via-black/50 to-black/70" />
                        
                        {/* Animated gradient overlay */}
                        <div className="absolute inset-0 z-[2] bg-gradient-to-r from-blue-600/20 via-purple-600/20 to-pink-600/20 animate-pulse" />
                        
                        <CardHeader className="relative z-10 pb-4">
                            <div className="flex items-start justify-between gap-4">
                                <div className="flex-1">
                                    <div className="mb-2 inline-flex items-center gap-2 rounded-full bg-yellow-500/20 px-3 py-1 backdrop-blur-sm">
                                        <Sparkles className="h-4 w-4 text-yellow-400" />
                                        <span className="text-xs font-semibold text-yellow-200">Live Tournament</span>
                                    </div>
                                    <h3 className="text-2xl font-bold leading-tight text-white capitalize drop-shadow-lg md:text-3xl lg:text-4xl">
                                        {tournament?.name}
                                    </h3>
                                    <p className="mt-2 flex items-center gap-2 text-sm text-gray-200">
                                        <Trophy className="h-4 w-4" />
                                        Join other users in today's tournament and win big!
                                    </p>
                                </div>
                                <div className="rounded-xl border border-white/30 bg-white/10 p-4 text-right backdrop-blur-md shadow-xl">
                                    <div className="text-2xl font-bold text-white drop-shadow-lg">₦{tournament?.amount.toLocaleString()}</div>
                                    <div className="text-xs font-medium text-gray-200">Prize Pool</div>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent className="relative z-10 pt-0 pb-6">
                            <div className="grid grid-cols-2 gap-3 md:gap-4">
                                <Link href={joinTour()} prefetch>
                                    <Button
                                        size={'lg'}
                                        className="w-full transform rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 font-semibold tracking-wide shadow-xl transition-all duration-300 hover:scale-105 hover:from-blue-700 hover:to-blue-800 hover:shadow-2xl"
                                    >
                                        <Trophy className="mr-2 h-5 w-5" />
                                        Join Tournament
                                    </Button>
                                </Link>
                                <Link href={create()} prefetch>
                                    <Button
                                        size={'lg'}
                                        variant="outline"
                                        className="w-full transform rounded-xl border-2 border-white/40 bg-white/15 font-semibold tracking-wide text-white shadow-xl backdrop-blur-md transition-all duration-300 hover:scale-105 hover:border-white/60 hover:bg-white/25 hover:shadow-2xl"
                                    >
                                        <Users className="mr-2 h-5 w-5" />
                                        Create Peer
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    // No Tournament Placeholder
                    <Card className="relative overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 dark:border-gray-700">
                        <CardContent className="p-8 md:p-12">
                            <div className="flex flex-col items-center text-center">
                                <div className="mb-4 rounded-full bg-gray-200 p-6 dark:bg-gray-700">
                                    <Calendar className="h-12 w-12 text-gray-400 dark:text-gray-500" />
                                </div>
                                <h3 className="mb-2 text-2xl font-bold text-gray-700 dark:text-gray-300">
                                    No Tournament Today
                                </h3>
                                <p className="mb-6 max-w-md text-gray-600 dark:text-gray-400">
                                    There's no active tournament at the moment. Check back tomorrow or create your own peer to get started!
                                </p>
                                <div className="flex flex-col sm:flex-row gap-3">
                                    <Link href={create()} prefetch>
                                        <Button
                                            size={'lg'}
                                            className="w-full rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 font-semibold shadow-lg transition-all duration-300 hover:from-blue-700 hover:to-blue-800 hover:shadow-xl"
                                        >
                                            <Users className="mr-2 h-5 w-5" />
                                            Create a Peer
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Recent Peers Section */}
                {recents && recents.length > 0 && (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-pink-600">
                                    <Sparkles className="h-4 w-4 text-white" />
                                </div>
                                <h3 className="text-lg font-bold tracking-tight text-gray-800 dark:text-gray-200 md:text-xl">
                                    Recent Peers
                                </h3>
                            </div>
                        </div>

                        <div className="mb-10">
                            <Swiper
                                spaceBetween={12}
                                slidesPerView={1.2}
                                breakpoints={{
                                    640: { slidesPerView: 2.2 },
                                    1024: { slidesPerView: 3.2 },
                                }}
                                className="w-full"
                                loop={recents.length > 3}
                                speed={800}
                            >
                                {recents?.map((peer) => (
                                    <SwiperSlide key={peer.id}>
                                        <div className="relative rounded-lg border-2 border-transparent bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 p-0.5 dark:from-blue-900/20 dark:via-purple-900/20 dark:to-pink-900/20">
                                            <Card className="group h-full cursor-pointer rounded-lg border-0 bg-white p-0 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl dark:bg-gray-800">
                                                <CardContent className="p-4">
                                                    {/* Header */}
                                                    <div className="mb-4 flex items-start justify-between">
                                                        <div className="flex-1 min-w-0">
                                                            <div className="mb-1 flex items-center gap-2">
                                                                <h4 className="truncate text-base font-bold text-gray-800 capitalize dark:text-gray-200">
                                                                    {peer.name}
                                                                </h4>
                                                            </div>
                                                            <p className="text-xs text-gray-500 dark:text-gray-400">by @{peer.created_by.name}</p>
                                                        </div>
                                                        <Badge className="rounded-lg bg-gradient-to-r from-green-600 to-emerald-600 px-3 py-1 text-sm font-bold text-white shadow-md">
                                                            ₦{Number(peer.amount).toLocaleString()}
                                                        </Badge>
                                                    </div>

                                                    {/* Users Count */}
                                                    <div className="mb-4 rounded-lg bg-gray-50 p-3 dark:bg-gray-700/50">
                                                        <div className="flex items-center justify-between">
                                                            <span className="text-xs font-medium text-gray-600 dark:text-gray-400">Participants</span>
                                                            {peer?.users_count > 0 ? (
                                                                <div className="flex items-center gap-2">
                                                                    <div className="flex -space-x-2">
                                                                        {Array.from({
                                                                            length: Math.min(peer.users_count, 3),
                                                                        }).map((_, idx) => (
                                                                            <Avatar key={idx} className="h-7 w-7 rounded-full border-2 border-white dark:border-gray-800">
                                                                                <AvatarFallback className="text-xs font-semibold">{idx + 1}</AvatarFallback>
                                                                            </Avatar>
                                                                        ))}
                                                                    </div>
                                                                    {peer.users_count > 3 && (
                                                                        <span className="text-xs font-semibold text-gray-600 dark:text-gray-400">
                                                                            +{peer.users_count - 3}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            ) : (
                                                                <span className="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                                    Be the first!
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Action Buttons */}
                                                    <div className="grid grid-cols-2 gap-2">
                                                        <Link href={show(peer?.peer_id)} prefetch>
                                                            <Button variant="outline" className="w-full rounded-lg text-sm font-medium" size="sm">
                                                                <Target className="mr-1 h-3 w-3" />
                                                                View
                                                            </Button>
                                                        </Link>
                                                        <Link href={joinPeer(peer.peer_id)} prefetch>
                                                            <Button className="w-full rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 text-sm font-medium shadow-md transition-all hover:shadow-lg" size="sm">
                                                                Join
                                                                <ArrowDownRightSquareIcon className="ml-1 h-3 w-3 transition duration-300 group-hover:-rotate-45" />
                                                            </Button>
                                                        </Link>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        </div>
                                    </SwiperSlide>
                                ))}
                            </Swiper>
                        </div>
                    </div>
                )}

                {/* Top Peers Section */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-yellow-500 to-orange-600">
                                <CupSoda className="h-4 w-4 text-white" />
                            </div>
                            <h3 className="text-lg font-bold tracking-tight text-gray-800 dark:text-gray-200 md:text-xl">
                                Top Peers
                            </h3>
                        </div>
                    </div>

                    <div className="space-y-3">
                        {(peers.data || []).length > 0 ? (
                            peers.data.map((peer, i) => (
                                <Card 
                                    className="group overflow-hidden rounded-xl border border-gray-200 bg-white p-0 shadow-md transition-all duration-300 hover:shadow-xl dark:border-gray-700 dark:bg-gray-800" 
                                    key={i}
                                >
                                    <Collapsible>
                                        <CollapsibleTrigger className="flex w-full cursor-pointer items-center justify-between p-4 transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <div className="flex items-center gap-3">
                                                <Avatar className="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-purple-600 shadow-lg ring-4 ring-blue-100 dark:ring-blue-900/50">
                                                    <AvatarFallback className="rounded-xl bg-transparent text-lg font-bold text-white">
                                                        {peer.name.substring(0, 2).toUpperCase()}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div className="flex flex-col items-start">
                                                    <div className="text-base font-bold text-gray-800 capitalize dark:text-gray-200 md:text-lg">
                                                        {peer.name}
                                                    </div>
                                                    <div className="text-xs text-gray-500 dark:text-gray-400 lg:text-sm">
                                                        by @{peer.created_by.name}
                                                    </div>
                                                </div>
                                            </div>
                                            <span className="text-xs font-medium text-gray-500 dark:text-gray-400 md:text-sm">
                                                {new Date(peer.created_at).toLocaleDateString('en-US', {
                                                    month: 'short',
                                                    day: 'numeric',
                                                })}
                                            </span>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent>
                                            <div className="border-t border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50">
                                                <div className="grid grid-cols-2 gap-4 px-4 py-4">
                                                    <div className="flex items-center gap-3 rounded-lg bg-white p-3 shadow-sm dark:bg-gray-800">
                                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50">
                                                            <Users size={18} className="text-blue-600 dark:text-blue-400" />
                                                        </div>
                                                        <div className="flex flex-col">
                                                            <small className="text-xs text-gray-500 dark:text-gray-400">Entries</small>
                                                            <span className="text-lg font-bold text-gray-800 dark:text-gray-200">
                                                                {peer.users_count}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-3 rounded-lg bg-white p-3 shadow-sm dark:bg-gray-800">
                                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/50">
                                                            <HandCoins size={18} className="text-green-600 dark:text-green-400" />
                                                        </div>
                                                        <div className="flex flex-col">
                                                            <small className="text-xs text-gray-500 dark:text-gray-400">Entry Fee</small>
                                                            <span className="text-lg font-bold text-gray-800 dark:text-gray-200">
                                                                ₦{Number(peer.amount).toLocaleString()}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex gap-3 border-t border-gray-200 px-4 py-4 dark:border-gray-700">
                                                    <Link href={show(peer?.peer_id)} className="w-full" prefetch>
                                                        <Button 
                                                            className="w-full rounded-lg text-sm font-medium" 
                                                            size="sm" 
                                                            variant="outline"
                                                        >
                                                            <Target className="mr-2 h-4 w-4" />
                                                            View Peer
                                                        </Button>
                                                    </Link>
                                                    <Link href={joinPeer(peer.peer_id)} className="w-full" prefetch>
                                                        <Button 
                                                            className="w-full rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 text-sm font-medium shadow-md transition-all hover:shadow-lg" 
                                                            size="sm"
                                                        >
                                                            Join Peer
                                                            <ArrowDownRightSquareIcon className="ml-2 h-4 w-4 transition duration-300 group-hover:-rotate-45" />
                                                        </Button>
                                                    </Link>
                                                </div>
                                            </div>
                                        </CollapsibleContent>
                                    </Collapsible>
                                </Card>
                            ))
                        ) : (
                            <Card className="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
                                <CardContent className="p-8 text-center">
                                    <div className="mb-3 inline-flex rounded-full bg-gray-200 p-4 dark:bg-gray-700">
                                        <Users className="h-8 w-8 text-gray-400" />
                                    </div>
                                    <h3 className="mb-2 text-lg font-semibold text-gray-700 dark:text-gray-300">
                                        No Peers Yet
                                    </h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Be the first to create a peer and start competing!
                                    </p>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}