import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/components/ui/collapsible";
import { show } from "@/routes/peers";
import { Link } from "@inertiajs/react";
import { HandCoins, Target, Users } from "lucide-react";
import React from "react";

const Ongoing = ({ peer }) => {
    return (
        <Card className="mb-3 p-0 bg-background/10 shadow border rounded">
            <Collapsible open={true}>
                <CollapsibleTrigger className="w-full flex items-center justify-between p-2 cursor-pointer hover:bg-[var(--clr-surface-a10)] transition rounded">
                    <div className="flex items-center gap-2">
                        <Avatar className="flex ring ring-[#c4c4c4] shadow-sm h-8 w-8 items-center justify-center rounded-full bg-[var(--clr-surface-a20)]">
                                                <AvatarFallback className="rounded   shadow-md">
                                                    {peer.name.substring(0, 2).toUpperCase()}
                                                </AvatarFallback>
                                            </Avatar>
                       <div className="flex flex-col items-start">
                                                <div className="text-gray-600 text-sm md:text-base font-semibold">{peer.name}</div>
                                                <div className="text-gray-600 text-[10px] lg:text-xs">by @{peer.created_by.name}</div>
                                            </div>
                    </div>
                    <span className="font-medium text-sm md:text-base text-muted">
                        {new Date(peer.created_at).toLocaleDateString()}
                    </span>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <div className="px-4  py-3 border-t border-border grid grid-cols-2 gap-4">
                        <div className="flex items-center gap-2">
                            <div className="size-10 rounded-full ring ring-background shadow flex items-center justify-center">
                                <Users size={18} />
                            </div>
                            <div className="flex flex-col items-start">
                                <small className="text-muted">Entries</small>
                                <span className="text-muted-white">
                                    {peer.users_count}
                                </span>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="size-10 rounded-full ring ring-background shadow flex items-center justify-center">
                                <HandCoins size={18} />
                            </div>
                            <div className="flex flex-col items-start">
                                <small className="text-muted">Fees</small>
                                <span className="text-muted-white">
                                    ₦{Number(peer.amount).toFixed()}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="px-4 py-3 flex gap-3 border-t border-border">
                        <Link
                            href={show(peer.peer_id)}
                            className="w-full"
                            prefetch
                        >
                            <Button
                                className="w-full  text-sm font-medium"
                                size="sm"
                            >
                                <Target className="w-3 h-3 mr-1" />
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
