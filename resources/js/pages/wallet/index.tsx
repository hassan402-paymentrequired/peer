import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { Deposit } from "./deposit";
import { ArrowUpCircle, ArrowDownCircle, Loader } from "lucide-react";
import WithdrawModal from "./withdraw";
import AppLayout from "@/layouts/app-layout";

const Wallet = ({ transactions }) => {
    const {
        auth: { user },
    } = usePage().props;

    const formatAmount = (amount: string, type: string) => {
        const color = type === "credit" ? "text-green-500" : "text-red-500";
        return (
            <span className={`font-semibold ${color}`}>
                ₦{parseFloat(amount).toLocaleString()}
            </span>
        );
    };

    const formatDate = (date: string) => {
        return new Date(date).toLocaleString();
    };

    // console.log(transactions);

    return (
        <AppLayout title="My wallet">
            <Head title="Wallet" />
            <div className="flex flex-col items-center px-4 py-6">
                {/* Balance Card */}
                <div className=" p-1 w-full bg-white/10 rounded border mb-3">
                    <div className="w-full flex items-center justify-between p-3 bg-default/10 rounded shadow-sm ">
                        <div>
                            <h2 className="md:text-lg text-xs text-gray-400">
                                Current Balance
                            </h2>
                            <p className="lg:text-4xl md:text-2xl text-lg font-extrabold mt-2 tracking-wide">
                                ₦
                                {parseFloat(
                                    user.wallet.balance
                                ).toLocaleString()}
                            </p>
                        </div>
                        <div className="flex mt-auto gap-2 items-center ">
                            <Deposit />
                            <WithdrawModal />
                        </div>
                    </div>
                </div>

                {/* Recent Transactions */}
                <div className="w-full">
                    <h3 className="text-lg font-semibold  mb-3">
                        Recent Transactions
                    </h3>
                    {transactions.length === 0 ? (
                        <div className="text-center  text-sm py-6 border border-dashed border-gray-500 rounded-lg">
                            No transactions yet.
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {transactions.map((t) => (
                                <div
                                    key={t.id}
                                    className=" p-1 bg-white/10 rounded border"
                                >
                                    <div className="flex items-center justify-between p-3 bg-default/10 rounded shadow-sm">
                                        <div className="flex items-center gap-3">
                                            {t.status === 1 ? (
                                                <Loader
                                                    className="text-gray-600 animate-spin"
                                                    size={20}
                                                />
                                            ) : t.action_type === "credit" ? (
                                                <ArrowDownCircle
                                                    className="text-green-500"
                                                    size={20}
                                                />
                                            ) : (
                                                <ArrowUpCircle
                                                    className="text-red-500"
                                                    size={20}
                                                />
                                            )}
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {t.description}
                                                </p>
                                                <p className="text-xs text-gray-400">
                                                    {formatDate(t.created_at)}
                                                </p>
                                            </div>
                                        </div>
                                        <div>
                                            {formatAmount(
                                                t.amount,
                                                t.action_type
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
};

export default Wallet;
