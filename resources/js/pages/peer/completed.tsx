import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { show } from '@/routes/peers';
import { Link } from '@inertiajs/react';
import { HandCoins, Target, Users } from 'lucide-react';

const Ongoing = ({ peer }) => {
    return (
        <Card className="mb-3 rounded border bg-background/10 p-0 shadow">
            <Collapsible open={true}>
                <CollapsibleTrigger className="flex w-full cursor-pointer items-center justify-between rounded p-2 transition hover:bg-[var(--clr-surface-a10)]">
                    <div className="flex items-center gap-2">
                        <Avatar className="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--clr-surface-a20)] shadow-sm ring ring-[#c4c4c4]">
                            <AvatarFallback className="rounded shadow-md">{peer.name.substring(0, 2).toUpperCase()}</AvatarFallback>
                        </Avatar>
                        <div className="flex flex-col items-start">
                            <div className="text-sm font-semibold text-gray-600 md:text-base">{peer.name}</div>
                            <div className="text-[10px] text-gray-600 lg:text-xs">by @{peer.created_by.name}</div>
                        </div>
                    </div>
                    <span className="text-sm font-medium text-muted md:text-base">{new Date(peer.created_at).toLocaleDateString()}</span>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <div className="grid grid-cols-2 gap-4 border-t border-border px-4 py-3">
                        <div className="flex items-center gap-2">
                            <div className="flex size-10 items-center justify-center rounded-full shadow ring ring-background">
                                <Users size={18} />
                            </div>
                            <div className="flex flex-col items-start">
                                <small className="text-muted">Entries</small>
                                <span className="text-muted-white">{peer.users_count}</span>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="flex size-10 items-center justify-center rounded-full shadow ring ring-background">
                                <HandCoins size={18} />
                            </div>
                            <div className="flex flex-col items-start">
                                <small className="text-muted">Fees</small>
                                <span className="text-muted-white">₦{Number(peer.amount).toFixed()}</span>
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-3 border-t border-border px-4 py-3">
                        <Link href={show(peer.peer_id)} className="w-full" prefetch>
                            <Button className="w-full text-sm font-medium" size="sm">
                                <Target className="mr-1 h-3 w-3" />
                                View Peer
                            </Button>
                        </Link>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </Card>
    );
};

export default Ongoing;
