import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/app-layout";
import { Head, Link,  usePage } from "@inertiajs/react";
import React from "react";

const Tournament = ({ tournament, users }) => {
    console.log(users);
    const {
        auth: {
            user: { id },
        },
    } = usePage().props;

    const isAmoung = () => {
        return users.some((user) => user.id.toString() === id.toString());
    };

    return (
        <AppLayout>
            <Head title={tournament.name + 's' + ' Tournament'} />

            <div className="flex flex-col h-screen">
                <div className="flex items-center justify-between p-3">
                    <div className="flex flex-col items-start mt-3 mb-2">
                        <h2 className="text-base capitalize  font-bold text-muted-white">
                            {tournament.name}'s Tournament
                        </h2>
                        <p className="text-muted text-xs font-semibold">
                            Join other users and compete globally!
                        </p>
                    </div>
                    <div>₦{tournament.amount}</div>
                </div>

                {!isAmoung() ? (
                    <div className="flex justify-center py-8">
                        <div className="p-6 flex flex-col items-center max-w-xs">
                            <span className="text-4xl mb-2 animate-bounce">
                                🌍
                            </span>
                            <div className="text-center text-muted mb-3 font-semibold">
                                You haven't joined {tournament.name} yet!
                            </div>
                            <p className="text-center text-muted mb-4">
                                Be part of the excitement—join the contest and
                                compete with other players.
                            </p>
                            <Link
                                className="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-primary transition"
                                prefetch
                            >
                                <Button className="text-foreground">
                                    <span>Join {tournament.name}</span>
                                    <span className="text-lg">⚔️</span>
                                </Button>
                            </Link>
                        </div>
                    </div>
                ) : (
                    <div className="w-full h-screen bg-white">
                        <div className="grid grid-cols-4 bg-gray-100 h-8 px-3 items-center">
                            <div className="capitalize  text-sm font-semibold col-span-1">No.</div>
                            <div className="capitalize  text-sm font-semibold col-span-2">Username.</div>
                            <div className="capitalize  text-sm font-semibold col-span-1">Point.</div>
                        </div>
                        <div className="divide-background divide-y">
                            {users.map((user, i) => (
                                <div
                                    key={user.id}
                                    className="grid grid-cols-4 items-center h-9 px-3"
                                >
                                    <span className="col-span-1">
                                        {i + 1}
                                    </span>
                                    <h4 className="col-span-2">
                                        @{user.username}
                                    </h4>
                                    <div className="col-span-1 text-left">
                                        {user.total_point}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
};

export default Tournament;
