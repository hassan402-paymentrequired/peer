import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import AppLayout from '@/layouts/app-layout';
import { dashboardBreadcrumbs } from '@/lib/breadcrumbs';
import "swiper/css";

import { Head, Link } from '@inertiajs/react';
import { ArrowDownRightSquareIcon, CupSoda, HandCoins, Sword, Target, Users } from 'lucide-react';
import { Swiper, SwiperSlide } from 'swiper/react';
import { joinPeer } from '@/actions/App/Http/Controllers/Peer/PeerController';

export default function Dashboard({ tournament, recents, peers }) {
    return (
        <AppLayout breadcrumbs={dashboardBreadcrumbs}>
            <Head title="Peers" />
            <div className="mt-2 space-y-4 p-3">
                {/* Global Challenge Card */}
                <Card className="relative overflow-hidden rounded p-0 py-2">
                    {/* Background Image */}
                    <div
                        className="absolute inset-0 z-0"
                        style={{
                            backgroundImage: "url('/images/tour.jpg')",
                            backgroundSize: 'cover',
                            backgroundPosition: 'center',
                            // opacity: 0.5,
                            pointerEvents: 'none',
                        }}
                        aria-hidden="true"
                    />
                    <CardHeader className="relative z-10 pb-0">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-3xl font-semibold text-white capitalize">{tournament?.name}</h3>
                                <p className="text-gray-100">
                                    Join other users in todays tournament <Sword size={16} />
                                </p>
                            </div>
                            <div className="text-right">
                                <div className="font-bold text-foreground">₦{tournament?.amount ?? 500}</div>
                                <div className="text-foreground">Prize Pool</div>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="relative z-10 pt-0">
                        <div className="grid grid-cols-2 gap-3">
                            <Link href={'#'} prefetch>
                                <Button className="w-full font-bold tracking-wider text-white">Join {tournament.name}</Button>
                            </Link>
                            <Link href={'#'} prefetch>
                                <Button variant="outline" className="w-full font-bold tracking-wider text-white">
                                    Create Peer
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                {/* Recent Peers Section */}
                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h3 className="text-lg font-semibold tracking-wider text-[var(--clr-light-a0)]">Recent Peers</h3>
                    </div>

                    <div className="mb-10">
                        <Swiper
                            spaceBetween={10}
                            slidesPerView={1.2}
                            className="w-full"
                            autoplay={{
                                delay: 3000,
                                disableOnInteraction: false,
                            }}
                            loop={true}
                            pagination={{
                                clickable: true,
                            }}
                            navigation
                            speed={1000}
                        >
                            {recents?.map((peer) => (
                                <SwiperSlide key={peer.id}>
                                    <div className="relative z-0 rounded-sm border bg-white/10 p-1 backdrop-blur-[1px]">
                                        <Card className="bg-default/10 group z-50 w-full cursor-pointer rounded border-input p-0 transition-all duration-300">
                                            <CardContent className="p-3">
                                                {/* Header */}
                                                <div className="mb-3 flex items-start justify-between">
                                                    <div className="flex-1">
                                                        <div className="mb-1 flex items-center gap-2">
                                                            <h4 className="text-gray-600 truncate text-sm font-semibold capitalize">
                                                                {peer.name}
                                                            </h4>
                                                        </div>
                                                        <p className="text-xs text-gray-600">by @{peer.created_by.username}</p>
                                                    </div>
                                                    <Badge className={`text-default rounded bg-background px-2 py-1 text-xs tracking-wider`}>
                                                        ₦{Number(peer.amount).toFixed()}
                                                    </Badge>
                                                </div>

                                                {/* Prize Pool */}
                                                <div className="iteme-center mb-3 w-full justify-center p-2">
                                                    <div className="flex -space-x-2 *:data-[slot=avatar]:ring-2 *:data-[slot=avatar]:ring-background *:data-[slot=avatar]:grayscale">
                                                        {peer?.users_count > 0 ? (
                                                            Array.from({
                                                                length: peer.users_count,
                                                            }).map((_, idx) => (
                                                                <Avatar key={idx} className="rounded">
                                                                    <AvatarFallback className="size-7 rounded">{idx + 1}</AvatarFallback>
                                                                </Avatar>
                                                            ))
                                                        ) : (
                                                            <span className="text-center text-xs text-gray-600">No one has joined yet</span>
                                                        )}
                                                    </div>
                                                </div>

                                                {/* Action Button */}
                                                <div className="grid grid-cols-2 gap-3">
                                                    <Link href={'#'} prefetch>
                                                        <Button className="w-full text-sm font-medium text-foreground hover:bg-blue-600" size="sm">
                                                            <Target className="mr-1 h-3 w-3" />
                                                            View Peer
                                                        </Button>
                                                    </Link>
                                                    <Link href={joinPeer(peer.peer_id)} prefetch>
                                                        <Button className="w-full text-sm font-medium text-foreground hover:bg-blue-600" size="sm">
                                                            Join Peer
                                                            <ArrowDownRightSquareIcon className="mr-1 h-3 w-3 transition duration-100 group-hover:-rotate-45" />
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

                <div className="mt-3 space-y-3">
                    <div className="flex items-center justify-between">
                        <h3 className="flex text-lg font-semibold text-[var(--clr-light-a0)]">
                            <CupSoda /> Top Peers
                        </h3>
                    </div>

                    <div className="mt-2 flex flex-col">
                        {(peers.data || []).map((peer, i) => (
                            <Card className="group mb-3 rounded border bg-background/10 p-0 ring ring-background" key={i}>
                                <Collapsible>
                                    <CollapsibleTrigger className="flex w-full cursor-pointer items-center justify-between rounded p-2 transition hover:bg-[var(--clr-surface-a10)]">
                                        <div className="flex items-center gap-2">
                                            <Avatar className="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--clr-surface-a20)]">
                                                <AvatarFallback className="rounded shadow ring-2 ring-foreground">
                                                    {peer.name.substring(0, 2).toUpperCase()}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="flex flex-col items-start">
                                                <div className="text-gray-600 text-base font-semibold">{peer.name}</div>
                                                <div className="text-gray-600 text-xs">by @{peer.created_by.username}</div>
                                            </div>
                                        </div>
                                        <span className="font-medium text-gray-600">{new Date(peer.created_at).toLocaleDateString()}</span>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div className="grid grid-cols-2 gap-4 border-t border-border px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <div className="flex size-10 items-center justify-center rounded-full bg-foreground shadow ring ring-background">
                                                    <Users size={18} />
                                                </div>
                                                <div className="flex flex-col items-start">
                                                    <small className="text-gray-600">Entries</small>
                                                    <span className="text-gray-600">{peer.users_count}</span>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <div className="flex size-10 items-center justify-center rounded-full shadow ring ring-background">
                                                    <HandCoins size={18} />
                                                </div>
                                                <div className="flex flex-col items-start">
                                                    <small className="text-gray-600">Entry Fee</small>
                                                    <span className="text-gray-600">₦{Number(peer.amount).toFixed()}</span>
                                                </div>
                                            </div>
                                        </div>
                                        {/* <div className="px-4 py-3 border-t border-border grid grid-cols-1 md:grid-cols-2 gap-4">

                                        </div> */}
                                        <div className="flex gap-3 border-t border-border px-4 py-3">
                                            <Link href={'#'} className="w-full" prefetch>
                                                <Button className="w-full text-sm font-medium" size="sm" variant="outline">
                                                    <Target className="mr-1 h-3 w-3" />
                                                    View Peer
                                                </Button>
                                            </Link>
                                            <Link href={joinPeer(peer.peer_id)} className="w-full" prefetch>
                                                <Button className="w-full text-sm font-medium text-foreground" size="sm">
                                                    Join Peer
                                                    <ArrowDownRightSquareIcon className="mr-1 h-3 w-3 transition duration-100 group-hover:-rotate-45" />
                                                </Button>
                                            </Link>
                                        </div>
                                    </CollapsibleContent>
                                </Collapsible>
                            </Card>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
