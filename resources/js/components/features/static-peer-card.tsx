import { Peer } from "@/types";
import { Badge } from "../ui/badge";
import { Card, CardContent } from "../ui/card";
import { Avatar, AvatarFallback } from "../ui/avatar";
import { Link } from "@inertiajs/react";
import { Button } from "../ui/button";
import { ArrowDownRightSquareIcon,  Target } from "lucide-react";
import { show } from '@/routes/peers';
import {  joinPeer } from '@/actions/App/Http/Controllers/Peer/PeerController';

const StaticPeerCard = ({peer}: {peer: Peer}) => {
    return (
        <div className="relative z-0 rounded-sm border bg-white/10 p-1 backdrop-blur-[1px]">
            <Card className="bg-default/10 group z-50 w-full cursor-pointer rounded border-input p-0 transition-all duration-300">
                <CardContent className="p-3">
                    {/* Header */}
                    <div className="mb-3 flex items-start justify-between">
                        <div className="flex-1">
                            <div className="mb-1 flex items-center gap-2">
                                <h4 className="truncate text-sm font-semibold text-gray-600 capitalize">{peer.name}</h4>
                            </div>
                            <p className="text-xs text-gray-600">by @{peer.created_by.name}</p>
                        </div>
                        <Badge className={`text-default rounded border bg-gray-50 px-2 py-1 text-xs tracking-wider`}>
                            ₦{Number(peer.amount).toFixed()}
                        </Badge>
                    </div>

                    {/* Prize Pool */}
                    <div className="mb-4 rounded-lg bg-gray-50 p-3 dark:bg-gray-700/50">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-medium text-gray-600 dark:text-gray-400">Participants</span>
                            {peer?.users_count > 0 ? (
                                <div className="flex items-center gap-2">
                                    <div className="flex -space-x-2">
                                        {Array.from({
                                            length: Math.min(Number(peer?.users_count || 0), 3),
                                        }).map((_, idx) => (
                                            <Avatar key={idx} className="h-7 w-7 rounded-full border-2 border-white dark:border-gray-800">
                                                <AvatarFallback className="text-xs font-semibold">{idx + 1}</AvatarFallback>
                                            </Avatar>
                                        ))}
                                    </div>
                                    {peer?.users_count > 3 && (
                                        <span className="text-xs font-semibold text-gray-600 dark:text-gray-400">+{peer?.users_count - 3}</span>
                                    )}
                                </div>
                            ) : (
                                <span className="text-xs font-medium text-gray-500 dark:text-gray-400">Be the first!</span>
                            )}
                        </div>
                    </div>

                    {/* Action Button */}
                    <div className="grid grid-cols-2 gap-2">
                        <Link href={show(peer?.peer_id)} prefetch>
                            <Button className="w-full rounded-sm text-xs" size="sm" variant="outline">
                                <Target className="mr-1 h-3 w-3" />
                                View
                            </Button>
                        </Link>
                        <Link href={joinPeer(peer.peer_id)} prefetch>
                            <Button className="w-full rounded-sm text-xs" size="sm">
                                Join
                                <ArrowDownRightSquareIcon className="mr-1 h-3 w-3 transition duration-100 group-hover:-rotate-45" />
                            </Button>
                        </Link>
                       
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default StaticPeerCard;
