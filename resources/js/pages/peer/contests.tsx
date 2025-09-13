/* eslint-disable @typescript-eslint/no-explicit-any */
import { Head, Link } from "@inertiajs/react";
import React from "react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Button } from "@/components/ui/button";
import Ongoing from "./on-going";
import AppLayout from "@/layouts/app-layout";
import { dashboard } from "@/routes";

interface Props {
    history: any[];
    upcoming: any[];
    ongoing: any[];
}

const Contests = ({ history, upcoming, ongoing }: Props) => {
    return (
        <AppLayout>
            <Head title="Peers - contests" />
            <div className="flex w-full p-3">
                <Tabs defaultValue="live" className="w-full">
                    <TabsList className="w-full bg-transparent">
                        <TabsTrigger
                            className="text-muted w-full data-[state=active]:border-b-muted data-[state=active]:border-b-3 data-[state=active]:text-muted-white data-[state=active]:rounded-none data-[state=active]:bg-transparent"
                            value="live"
                        >
                            Live
                        </TabsTrigger>
                        <TabsTrigger
                            className="text-muted w-full data-[state=active]:border-b-muted data-[state=active]:border-b-3 data-[state=active]:text-muted-white data-[state=active]:rounded-none data-[state=active]:bg-transparent"
                            value="upcoming"
                        >
                            Finished
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="live">
                        {!ongoing.length && (
                            <div className="flex justify-center py-8">
                                <div className=" p-6 flex flex-col items-center max-w-xs">
                                    <span className="text-4xl mb-2 animate-bounce">
                                        🤷‍♂️
                                    </span>
                                    <div className="text-center text-muted mb-3">
                                        No ongoing peers found.
                                        <br />
                                        Maybe they're all hiding from you?
                                    </div>
                                    <Link
                                        href={dashboard()}
                                        className="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-foreground  transition"
                                        prefetch
                                    >
                                        <Button className="text-foreground">
                                            <span>Find some peers</span>
                                            <span className="text-lg">🕵️‍♀️</span>
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        )}

                        {ongoing.length > 0 &&
                            ongoing.map((p) => <Ongoing peer={p} key={p.id} />)}
                    </TabsContent>
                    <TabsContent value="upcoming">
                        {!history.length && (
                            <div className="flex justify-center py-8">
                                <div className=" p-6 flex flex-col items-center max-w-xs">
                                    <span className="text-4xl mb-2 animate-bounce">
                                        🤷‍♂️
                                    </span>
                                    <div className="text-center text-muted mb-3">
                                        You have't join and peer yet .
                                    </div>
                                    <Link
                                        href={dashboard()}
                                        className="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-primary  transition"
                                        prefetch
                                    >
                                        <Button>
                                            <span>Find some peers</span>
                                            <span className="text-lg">🕵️‍♀️</span>
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
};

export default Contests;
