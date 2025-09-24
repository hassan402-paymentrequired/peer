import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { create } from '@/routes/tournament';
import { Head, Link, usePage } from '@inertiajs/react';

const Tournament = ({ tournament, users }) => {
    console.log(users);
    const {
        auth: {
            user: { id },
        },
    } = usePage().props;

    const isAmoung = () => {
        return tournament && users?.some((user) => user.id.toString() === id.toString());
    };

    return (
        <AppLayout>
            <Head title={tournament?.name ? tournament.name + 's' + ' Tournament' : 'Tournament'} />

            {!tournament ? (
                <div className="flex h-screen flex-col items-center justify-center">
                    <div className="flex max-w-md flex-col items-center p-6 text-center">
                        <span className="mb-4 text-6xl">🏆</span>
                        <h2 className="text-muted-white mb-2 text-xl font-bold">No Tournament Today</h2>
                        <p className="mb-6 text-muted">
                            There's no active tournament at the moment. Check back later or stay tuned for upcoming competitions!
                        </p>
                        <div className="rounded-lg bg-gray-50 p-4">
                            <p className="text-sm text-muted">
                                💡 Tournaments are usually announced in advance. Make sure to follow updates so you don't miss out!
                            </p>
                        </div>
                    </div>
                </div>
            ) : (
                <div className="flex h-screen flex-col">
                    <div className="flex items-center justify-between p-3">
                        <div className="mt-3 mb-2 flex flex-col items-start">
                            <h2 className="text-muted-white text-base font-bold capitalize">{tournament.name}'s Tournament</h2>
                            <p className="text-xs font-semibold text-muted">Join other users and compete globally!</p>
                        </div>
                        <div>₦{tournament.amount}</div>
                    </div>

                    {!isAmoung() ? (
                        <div className="flex justify-center py-8">
                            <div className="flex max-w-xs flex-col items-center p-6">
                                <span className="mb-2 animate-bounce text-4xl">🌍</span>
                                <div className="mb-3 text-center font-semibold text-muted">You haven't joined {tournament.name} yet!</div>
                                <p className="mb-4 text-center text-muted">
                                    Be part of the excitement—join the contest and compete with other players.
                                </p>
                                <Link
                                    className="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-primary transition"
                                    prefetch
                                    href={create()}
                                >
                                    <Button className="">
                                        <span>Join {tournament.name}</span>
                                        <span className="text-lg">⚔️</span>
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    ) : (
                        <div className="h-screen w-full bg-white">
                            <div className="grid h-8 grid-cols-4 items-center bg-gray-100 px-3">
                                <div className="col-span-1 text-sm font-semibold capitalize">No.</div>
                                <div className="col-span-2 text-sm font-semibold capitalize">Username.</div>
                                <div className="col-span-1 text-sm font-semibold capitalize">Point.</div>
                            </div>
                            <div className="divide-y divide-background">
                                {users.map((user, i) => (
                                    <div key={user.id} className="grid h-9 grid-cols-4 items-center px-3">
                                        <span className="col-span-1">{i + 1}</span>
                                        <h4 className="col-span-2 text-sm md:text-base">@{user.username.substring(0, 17)}</h4>
                                        <div className="col-span-1 text-left">{user.total_point}</div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}
        </AppLayout>
    );
};

export default Tournament;
