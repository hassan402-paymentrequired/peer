import { joinPeer } from '@/actions/App/Http/Controllers/Peer/PeerController';
import { show } from '@/routes/peers';
import { Peer } from '@/types';
import { Link } from '@inertiajs/react';
import { ArrowDownRightSquareIcon, Copy, HandCoins, Target, Users } from 'lucide-react';
import { toast } from 'sonner';
import { Avatar, AvatarFallback } from '../ui/avatar';
import { Button } from '../ui/button';
import { Card } from '../ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '../ui/collapsible';

export const handleCopyPeerLink = async (peerId: string) => {
    const fullUrl = `${window.location.origin}/peers/join/${peerId}`;
    await navigator.clipboard.writeText(fullUrl);
    toast.success('Peer link copied ✅');
};

const PeerCard = ({ peer }: { peer: Peer }) => {
    return (
        <Card className="group mb-3 rounded border bg-background/10 p-0 ring ring-background">
            <Collapsible>
                <CollapsibleTrigger className="flex w-full cursor-pointer items-center justify-between rounded p-2 transition hover:bg-[var(--clr-surface-a10)]">
                    <div className="flex items-center gap-2">
                        <Avatar className="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--clr-surface-a20)] shadow-sm ring ring-[#c4c4c4]">
                            <AvatarFallback className="rounded shadow-md">{peer.name.substring(0, 2).toUpperCase()}</AvatarFallback>
                        </Avatar>
                        <div className="flex flex-col items-start">
                            <div className="text-sm font-semibold text-gray-600 md:text-base">{peer.name}</div>
                            <div className="text-[10px] text-gray-600 lg:text-xs">by @{peer.created_by.username}</div>
                        </div>
                    </div>
                    <span className="text-sm font-medium text-gray-600 md:text-base">{new Date(peer.created_at).toLocaleDateString()}</span>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <div className="grid grid-cols-2 gap-4 border-t border-border px-4 py-3">
                        <div className="flex items-center gap-2">
                            <div className="bg-default flex size-10 items-center justify-center rounded-full shadow ring ring-background">
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
                    <div className="grid grid-cols-3 gap-2 border-t border-border px-4 py-3">
                        <Link href={show(peer?.peer_id)} className="w-full" prefetch>
                            <Button className="w-full text-xs font-medium" size="sm" variant="outline">
                                <Target className="mr-1 h-3 w-3" />
                                View
                            </Button>
                        </Link>
                        <Link href={joinPeer(peer.peer_id)} className="w-full" prefetch>
                            <Button className="w-full text-xs font-medium" size="sm">
                                Join
                                <ArrowDownRightSquareIcon className="mr-1 h-3 w-3 transition duration-100 group-hover:-rotate-45" />
                            </Button>
                        </Link>
                        <Button onClick={() => handleCopyPeerLink(peer.peer_id)} className="w-full text-xs font-medium" size="sm" variant="outline">
                            <Copy className="mr-1 h-3 w-3" />
                            Copy
                        </Button>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </Card>
    );
};

export default PeerCard;
