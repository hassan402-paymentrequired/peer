

import React, { useEffect, useState } from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
    Trophy,
    Users,
    DollarSign,
    Target,
    TrendingUp,
    Crown,
    Medal,
    ArrowLeft,
    ChevronDown,
    ChevronUp,
    Type,
    ArrowDownRightSquareIcon,
    Copy,
} from "lucide-react";
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from "@/components/ui/collapsible";
import { toast } from "sonner";
import AppLayout from "@/layouts/app-layout";

interface PeerUser {
    id: number;
    user: {
        id: number;
        username: string;
        avatar?: string;
    };
    total_points: number;
    is_winner: boolean;
    created_at: string;
}

interface Peer {
    id: number;
    peer_id: string;
    name: string;
    amount: string;
    private: boolean;
    limit: number;
    sharing_ratio: number;
    status: "open" | "closed" | "finished";
    winner_user_id?: number;
    created_by: {
        id: number;
        username: string;
    };
    users: PeerUser[];
    users_count: number;
    created_at: string;
}

interface PeerShowProps  {
    peer: Peer;
    users: any[];
}

export default function PeerShow({ peer, users }: PeerShowProps) {
    const {
        auth: { user },
    } = usePage<{ auth: { user: any } }>().props;

    const sortedUsers = [...users].sort(
        (a, b) => b.total_points - a.total_points
    );

    const getMatch = async () => {
        await fetch(
            "https://www.sofascore.com/api/v1/event/12436883/player/975079/statistics",

            {
                headers: {
                    "Access-Control-Allow-Origin": "*",
                },
            }
        )
            .then((response) => response.json())
            .then((data) => console.log(data))
            .catch((e) => console.log(e));
    };

    useEffect(() => {
        const id = setInterval(() => {
            getMatch();
        }, 10000);

        return () => {
            clearInterval(id);
        };
    }, []);

    // Collapse state for each user row
    const getRankIcon = (index: number) => {
        switch (index) {
            case 0:
                return <Crown className="w-5 h-5 text-yellow-500" />;
            case 1:
                return <Medal className="w-5 h-5 text-gray-400" />;
            case 2:
                return <Medal className="w-5 h-5 text-amber-600" />;
            default:
                return (
                    <span className="text-base font-bold text-gray-500">
                        {index + 1}
                    </span>
                );
        }
    };

    const handleCopy = async () => {
        await navigator.clipboard.writeText(peer.peer_id);
        toast.success("Peer code copied ✅")
    }

    return (
        <AppLayout
        //  successDiv={
        //     <div className="w-full flex border-b py-1 items-center justify-between px-2">
        //         <span>Peer Code: <strong>{peer?.peer_id}</strong> </span>
        //         <div onClick={handleCopy} className="flex items-center gap-1 px-2 rounded border border-background">
        //             copy <Copy size={15} />
        //         </div>
        //     </div>
        // }
        >
            <Head title={`Peer: ${peer.name}`} />

            <div className="space-y-4 p-3">
                {/* Header */}
                <div className="flex items-center gap-3 mt-2">
                    <div className="flex-1">
                        <h1 className="text-xl capitalize font-bold text-[var(--clr-light-a0)]">
                            {peer.name}
                        </h1>
                        <p className="text-sm text-[var(--clr-surface-a50)]">
                            Peer Competition
                        </p>
                    </div>
                    <Badge
                    >
                        {peer.status === "open" ? "Active" : peer.status}
                    </Badge>
                </div>

                {/* table */}

                {users.length > 0 &&
                    users.map((user) => (
                        <div
                            key={user.id}
                            className="p-2 rounded-sm bg-white "
                        >
                            <Card className="p-0 border bg-white shadow rounded">
                                <Collapsible>
                                    <CollapsibleTrigger className="w-full flex items-center justify-between p-2 cursor-pointer hover:bg-[var(--clr-surface-a10)] transition rounded">
                                        <div className="flex items-center gap-2">
                                            <Avatar className="w-8 h-8 rounded-full bg-[var(--clr-surface-a20)] flex items-center justify-center">
                                                <AvatarFallback className="rounded uppercase bg-background ring-2 ring-foreground shadow">
                                                    {user.username.substring(
                                                        0,
                                                        2
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="items-start flex flex-col">
                                                <div className="font-semibold text-muted-white text-base">
                                                    {user.username}
                                                </div>
                                                <div className="text-xs text-muted-white">
                                                    by @
                                                    {peer.created_by.username}
                                                </div>
                                            </div>
                                        </div>
                                        <span className="font-medium text-muted">
                                            {new Date(
                                                peer.created_at
                                            ).toLocaleDateString()}
                                        </span>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-border bg-transparent text-muted rounded shadow">
                                                <thead className="bg-[var(--clr-surface-a10)]">
                                                    <tr>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-muted-white uppercase tracking-wider">
                                                            #
                                                        </th>

                                                        <th className="px-3 py-2 text-left text-xs font-medium text-muted-white uppercase tracking-wider">
                                                            Player
                                                        </th>

                                                        <th className="px-3 py-2 text-left text-xs font-medium text-muted-white uppercase tracking-wider">
                                                            type
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-muted-white uppercase tracking-wider">
                                                            Position
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-muted-white uppercase tracking-wider">
                                                            Goals
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-muted-white uppercase tracking-wider">
                                                            Shot On
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-muted-white uppercase tracking-wider">
                                                            GK Save
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-muted-white uppercase tracking-wider">
                                                            Assists
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-muted-white uppercase tracking-wider">
                                                            Tackle
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-muted-white uppercase tracking-wider">
                                                            Shots
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-muted-white uppercase tracking-wider">
                                                            Yellow Card
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-muted-white uppercase tracking-wider">
                                                            Red Card
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-medium text-muted-white uppercase tracking-wider">
                                                            TP %
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className=" divide-y divide-border bg-transparent">
                                                    {user.squads &&
                                                    user.squads.length > 0 ? (
                                                        user.squads.map(
                                                            (squad, idx) => (
                                                                <>
                                                                    <tr
                                                                        key={
                                                                            squad.id +
                                                                            idx +
                                                                            "-star"
                                                                        }
                                                                    >
                                                                        <td
                                                                            colSpan={
                                                                                12
                                                                            }
                                                                            className=" text-sm text-muted px-3 py-1"
                                                                        >
                                                                            Star{" "}
                                                                            {idx +
                                                                                1}{" "}
                                                                            ⭐
                                                                        </td>
                                                                    </tr>

                                                                    <tr
                                                                        key={
                                                                            squad.id +
                                                                            "-main"
                                                                        }
                                                                    >
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {squad
                                                                                .main_player
                                                                                ?.statistics
                                                                                ?.did_play
                                                                                ? "⚽"
                                                                                : "🪑"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {
                                                                                squad
                                                                                    .main_player
                                                                                    ?.name
                                                                            }
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            Main
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {
                                                                                squad
                                                                                    .main_player
                                                                                    ?.position
                                                                            }
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {squad
                                                                                .main_player
                                                                                ?.statistics
                                                                                ?.goals ??
                                                                                0}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {squad
                                                                                .main_player
                                                                                ?.statistics
                                                                                ?.shots_on ??
                                                                                0}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {squad
                                                                                .main_player
                                                                                ?.statistics
                                                                                ?.goals_saves ??
                                                                                "0"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {squad
                                                                                .main_player
                                                                                ?.statistics
                                                                                ?.assists ??
                                                                                "0"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm ">
                                                                            {squad
                                                                                .main_player
                                                                                ?.statistics
                                                                                ?.tackles_total ??
                                                                                "0"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm ">
                                                                            {squad
                                                                                .main_player
                                                                                ?.statistics
                                                                                ?.shots ??
                                                                                "0"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm ">
                                                                            {squad
                                                                                .main_player
                                                                                ?.statistics
                                                                                ?.cards_yellow ??
                                                                                "0"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm ">
                                                                            {squad
                                                                                .main_player
                                                                                ?.statistics
                                                                                ?.cards_red ??
                                                                                "0"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm ">
                                                                            {/* Card between the stars */}
                                                                            {squad
                                                                                .main_player
                                                                                ?.statistics
                                                                                ?.cards_yellow ||
                                                                            squad
                                                                                .main_player
                                                                                ?.statistics
                                                                                ?.cards_red ? (
                                                                                <>
                                                                                    {squad
                                                                                        .main_player
                                                                                        ?.statistics
                                                                                        ?.cards_yellow >
                                                                                        0 && (
                                                                                        <span className="inline-block mr-1 text-yellow-500">
                                                                                            🟨
                                                                                        </span>
                                                                                    )}
                                                                                    {squad
                                                                                        .main_player
                                                                                        ?.statistics
                                                                                        ?.cards_red >
                                                                                        0 && (
                                                                                        <span className="inline-block text-red-500">
                                                                                            🟥
                                                                                        </span>
                                                                                    )}
                                                                                </>
                                                                            ) : (
                                                                                0
                                                                            )}
                                                                        </td>
                                                                    </tr>
                                                                    <tr
                                                                        key={
                                                                            squad.id +
                                                                            "-sub"
                                                                        }
                                                                    >
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {squad
                                                                                .sub_player
                                                                                ?.statistics
                                                                                ?.did_play
                                                                                ? "⚽"
                                                                                : "🪑"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {
                                                                                squad
                                                                                    .sub_player
                                                                                    ?.name
                                                                            }
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            Sub
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {
                                                                                squad
                                                                                    .sub_player
                                                                                    ?.position
                                                                            }
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {squad
                                                                                .sub_player
                                                                                ?.statistics
                                                                                ?.goals ??
                                                                                0}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {squad
                                                                                .sub_player
                                                                                ?.statistics
                                                                                ?.shots_on ??
                                                                                0}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {squad
                                                                                .sub_player
                                                                                ?.statistics
                                                                                ?.goals_saves ??
                                                                                "0"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm text-muted">
                                                                            {squad
                                                                                .sub_player
                                                                                ?.statistics
                                                                                ?.assists ??
                                                                                "0"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm ">
                                                                            {squad
                                                                                .sub_player
                                                                                ?.statistics
                                                                                ?.tackles_total ??
                                                                                "0"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm ">
                                                                            {squad
                                                                                .sub_player
                                                                                ?.statistics
                                                                                ?.shots ??
                                                                                "0"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm ">
                                                                            {squad
                                                                                .sub_player
                                                                                ?.statistics
                                                                                ?.cards_yellow ??
                                                                                "0"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm ">
                                                                            {squad
                                                                                .sub_player
                                                                                ?.statistics
                                                                                ?.cards_red ??
                                                                                "0"}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-sm ">
                                                                            {/* Card between the stars */}
                                                                            {squad
                                                                                .sub_player
                                                                                ?.statistics
                                                                                ?.cards_yellow ||
                                                                            squad
                                                                                .sub_player
                                                                                ?.statistics
                                                                                ?.cards_red ? (
                                                                                <>
                                                                                    {squad
                                                                                        .sub_player
                                                                                        ?.statistics
                                                                                        ?.cards_yellow >
                                                                                        0 && (
                                                                                        <span className="inline-block mr-1 text-yellow-500">
                                                                                            🟨
                                                                                        </span>
                                                                                    )}
                                                                                    {squad
                                                                                        .sub_player
                                                                                        ?.statistics
                                                                                        ?.cards_red >
                                                                                        0 && (
                                                                                        <span className="inline-block text-red-500">
                                                                                            🟥
                                                                                        </span>
                                                                                    )}
                                                                                </>
                                                                            ) : (
                                                                                0
                                                                            )}
                                                                        </td>
                                                                    </tr>
                                                                </>
                                                            )
                                                        )
                                                    ) : (
                                                        <tr>
                                                            <td
                                                                colSpan={12}
                                                                className="px-3 py-4 text-center text-gray-400"
                                                            >
                                                                No squad data
                                                                available.
                                                            </td>
                                                        </tr>
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </CollapsibleContent>
                                </Collapsible>
                            </Card>
                        </div>
                    ))}

                {users.length === 0 && (
                    <div className="flex justify-center py-8">
                        <div className=" p-6 flex flex-col items-center max-w-xs">
                            <span className="text-4xl mb-2 animate-bounce">
                                🤷‍♂️
                            </span>
                            <div className="text-center text-muted mb-3">
                                No player have joined this peer yet
                                <br />
                                Be the first to join
                            </div>
                            <Link
                                href={route("peers.join", {
                                    peer: peer.id,
                                })}
                                prefetch
                            >
                                <Button
                                    className="w-full hover:bg-blue-600 text-foreground text-sm font-medium"
                                    size="sm"
                                >
                                    Join Peer
                                    <ArrowDownRightSquareIcon className="w-3 h-3 mr-1 transition duration-100 group-hover:-rotate-45" />
                                </Button>
                            </Link>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
