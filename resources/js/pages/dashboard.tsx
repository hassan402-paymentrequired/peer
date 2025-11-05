import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboardBreadcrumbs } from '@/lib/breadcrumbs';
import 'swiper/css';
import { create } from '@/actions/App/Http/Controllers/Peer/PeerController';
import { create as joinTour } from '@/actions/App/Http/Controllers/Tournament/TournamentController';
import { Head, Link } from '@inertiajs/react';
import { CupSoda,  Users } from 'lucide-react';
import { Swiper, SwiperSlide } from 'swiper/react';
import PeerCard from '@/components/features/peer-card';
import StaticPeerCard from '@/components/features/static-peer-card';
import { Peer } from '@/types';

interface Props {
    tournament: {
        id: number,
        name: string,
        amount: number
    },
    recents: Peer[],
    peers: {
        data: Peer[]
    }
}

export default function Dashboard({ tournament, recents, peers }: Props) {

    return (
        <AppLayout breadcrumbs={dashboardBreadcrumbs}>
            <Head title="Peers" />
            <div className="space-y-4 p-3">
                {tournament ? (
                    <Card className="relative overflow-hidden rounded border-0 shadow-lg">
                        <div
                            className="absolute inset-0 z-0"
                            style={{
                                backgroundImage: "url('/images/tour.jpg')",
                                backgroundSize: 'cover',
                                backgroundPosition: 'center',
                            }}
                            aria-hidden="true"
                        />
                        <div className="absolute inset-0 z-[1] bg-gradient-to-br from-black/60 via-black/40 to-black/60" />
                        <CardHeader className="relative z-10 pb-2">
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <h3 className="text-lg leading-tight font-bold text-white capitalize md:text-2xl lg:text-3xl">
                                        {tournament?.name}
                                    </h3>
                                    <p className="flex items-center gap-2 text-xs text-gray-200">Join other users in today's tournament</p>
                                </div>
                                <div className="rounded-lg border border-white/20 bg-white/10 p-3 text-right backdrop-blur-sm">
                                    <div className="text-base font-bold text-white">₦{tournament?.amount}</div>
                                    <div className="text-xs text-gray-200">Prize Pool</div>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent className="relative z-10 pt-0">
                            <div className="grid grid-cols-2 gap-4">
                                <Link href={joinTour()} prefetch>
                                    <Button
                                        size={'default'}
                                        className="w-full transform rounded bg-gradient-to-r from-blue-600 to-blue-700 font-semibold tracking-wide shadow-lg transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:to-blue-800"
                                    >
                                        Join {tournament?.name}
                                    </Button>
                                </Link>
                                <Link href={create()} prefetch>
                                    <Button
                                        size={'default'}
                                        variant="outline"
                                        className="w-full transform rounded border-white/30 bg-white/10 font-semibold tracking-wide text-white shadow-lg backdrop-blur-sm transition-all duration-200 hover:scale-105 hover:border-white/50 hover:bg-white/20"
                                    >
                                        Create Peer
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="relative overflow-hidden rounded border-2 border-dashed border-gray-300 bg-gradient-to-br from-gray-50 to-gray-100 dark:border-gray-700 dark:from-gray-900 dark:to-gray-800">
                        <CardContent className="p-4">
                            <div className="flex flex-col items-center text-center">
                                <h3 className="mb-2 text-lg font-bold text-gray-700 dark:text-gray-300">No Tournament Today</h3>
                                <p className="mb-6 max-w-md text-xs text-gray-600 dark:text-gray-400">
                                    There's no active tournament at the moment. Check back tomorrow or create your own peer to get started!
                                </p>
                                <div className="flex flex-col gap-3 sm:flex-row">
                                    <Link href={create()} prefetch>
                                        <Button size={'sm'}>
                                            <Users className="mr-2 h-5 w-5" />
                                            Create a Peer
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </CardContent>
                    </div>
                )}
                {/* Recent Peers Section */}
                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h3 className="text-sm font-semibold tracking-wider md:text-lg">Recent Peers</h3>
                    </div>

                    <div className="mb-10">
                        {recents.length > 0 ? (
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
                                       <StaticPeerCard peer={peer} key={peer.peer_id} />
                                    </SwiperSlide>
                                ))}
                            </Swiper>
                        ) : (
                            <div>
                                <h2>No recent peer at the moment</h2>
                            </div>
                        )}
                    </div>
                </div>

                <div className="space-y-3 lg:mt-3">
                    <div className="flex items-center justify-between">
                        <h3 className="mg:text-lg flex text-base font-semibold text-[var(--clr-light-a0)]">
                            <CupSoda /> Top Peers
                        </h3>
                    </div>

                    <div className="mt-2 flex flex-col">
                        {(peers.data || []).length > 0 ? (
                            peers.data.map((peer, i) => (
                                <PeerCard key={i} peer={peer} />
                            ))
                        ) : (
                            <div className="rounded border-2 border-dashed border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
                                <CardContent className="p-4 text-center">
                                    <div className="mb-3 inline-flex rounded-full bg-gray-200 p-4 dark:bg-gray-700">
                                        <Users className="h-8 w-8 text-gray-400" />
                                    </div>
                                    <h3 className="text-lg font-semibold text-gray-700 dark:text-gray-300">No Peers Yet</h3>
                                    <p className="text-xs text-gray-600 dark:text-gray-400">Be the first to create a peer and start competing!</p>
                                </CardContent>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
