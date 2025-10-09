/* eslint-disable @typescript-eslint/no-explicit-any */
import { Button } from '@/components/ui/button';
import { CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { Head, Link } from '@inertiajs/react';
import Ongoing from './on-going';

interface Props {
    history: {
        data: any;
    };
}

const Contests = ({ history }: Props) => {
    return (
        <AppLayout title="My Contests">
            <Head title="Peers - completed contests" />
            <div className="mt-2 flex w-full flex-col">
                <div className="flex items-center justify-between px-3">
                    <h2 className="font-['Google Sans Code'] text-lg font-semibold">Completed Peers</h2>
                </div>
                <div className="w-full p-3">
                    {!history?.data?.length && (
                        // <div className="flex w-full flex-col items-center  py-8">
                            <div className="relative overflow-hidden rounded border-2 border-dashed border-gray-300 bg-gradient-to-br from-gray-50 to-gray-100 dark:border-gray-700 dark:from-gray-900 dark:to-gray-800">
                                <CardContent className="p-4">
                                    <div className="flex flex-col items-center text-center">
                                        <h3 className="mb-2 text-lg font-bold text-gray-700 dark:text-gray-300">No Peers Found</h3>
                                        <p className="mb-6 max-w-md text-xs text-gray-600 dark:text-gray-400">
                                            It looks like you don't have any peers in your history right now. Don't worry, you can always find more
                                            peers on the dashboard!
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
                        // {/* </div> */}
                    )}
                    {history?.data?.length > 0 && history?.data?.map((p: any) => <Ongoing peer={p} key={p.id} />)}
                </div>
            </div>
        </AppLayout>
    );
};

export default Contests;
