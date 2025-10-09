/* eslint-disable @typescript-eslint/no-explicit-any */
import { completedContest } from '@/actions/App/Http/Controllers/Peer/PeerController';
import { Button } from '@/components/ui/button';
import { CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { Head, Link } from '@inertiajs/react';
import Ongoing from './on-going';

interface Props {
    ongoing: {
        data: any;
    };
}

const Contests = ({ ongoing }: Props) => {
    return (
        <AppLayout title="My Contests">
            <Head title="Peers - contests" />
            <div className="mt-2 flex w-full flex-col">
                <div className="flex items-center justify-between px-3">
                    <h2 className="font-['Google Sans Code'] text-lg font-semibold">Active Peers</h2>
                    <Link href={completedContest()}>
                        <Button className="" size={'sm'}>
                            history
                        </Button>
                    </Link>
                </div>
                <div className="w-full p-3">
                    {!ongoing?.data?.length && (
                        <div className="relative overflow-hidden rounded border-2 border-dashed border-gray-300 bg-gradient-to-br from-gray-50 to-gray-100 dark:border-gray-700 dark:from-gray-900 dark:to-gray-800">
                            <CardContent className="p-4">
                                <div className="flex flex-col items-center text-center">
                                    <h3 className="mb-2 text-lg font-bold text-gray-700 dark:text-gray-300">No Peers Found</h3>
                                    <p className="mb-6 max-w-md text-xs text-gray-600 dark:text-gray-400">
                                        It looks like you don't have any ongoing peers right now. That's okay! You can find other users to play with
                                        by clicking the button below.
                                    </p>
                                    <div className="flex flex-col gap-3 sm:flex-row">
                                        <Link href={dashboard()} prefetch>
                                            <Button size={'sm'}>
                                                <span>Find some peers</span>
                                            </Button>
                                        </Link>
                                    </div>
                                </div>
                            </CardContent>
                        </div>
                    )}

                    {ongoing?.data?.length > 0 && ongoing?.data?.map((p: any) => <Ongoing peer={p} key={p.id} />)}
                </div>
            </div>
        </AppLayout>
    );
};

export default Contests;
