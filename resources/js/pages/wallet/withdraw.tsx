// resources/js/Components/WithdrawModal.jsx
import { useEffect, useState } from "react";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { router, useForm, usePage } from "@inertiajs/react";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { toast } from "sonner";
import { Loader } from "lucide-react";

export default function WithdrawModal() {
    const { data: a, flash } = usePage<{
        data: {
            account_name: string;
            account_number: string;
            bank_id: string | number;
        };
        flash: { success: string; error: string };
    }>().props;

    const [code, setCode] = useState("");
    const [accN, setAccN] = useState("");
    const [loading, setLoading] = useState(false);
    const [banks, setBanks] = useState<
        { id: number; name: string; code: string, slug: string }[]
    >([]);

    const { data, setData, post, processing, reset, transform } = useForm({
        amount: "",
        account_number: "",
        bank_code: "",
        account_name: "",
    });

    useEffect(() => {
        if (a) {
            setData("account_name", a.account_name);
            setData("account_number", a.account_number);
            setAccN(a.account_number);
            // Find and set the bank code based on bank_id
            const selectedBank = banks.find(
                (bank) => bank.id.toString() === a.bank_id.toString()
            );
            if (selectedBank) {
                setCode(selectedBank.code);
                setData('bank_code', selectedBank.code)
            }
        }

        if (flash?.error) {
            toast.error(flash.error);
        }

        if (flash?.success) {
            toast.success(flash.success);
        }

        getBanks();
    }, [a, flash, banks.length]);

    const getBanks = async () => {
        try {
            const res = await fetch(
                "https://api.paystack.co/bank?currency=NGN"
            );
            const responseData = await res.json();

            if (responseData.status) {
                setBanks(responseData.data);
            } else {
                toast.error("Failed to fetch banks");
            }
        } catch (error) {
            console.error("Error fetching banks:", error);
            toast.error("Failed to fetch banks");
        }
    };

    const handleWithdraw = (e) => {
        e.preventDefault();

        transform((data) => ({
            ...data,
            account_number: accN,
            bank_account_id: code,
        }));

        post(route("fund.withdraw"), {
            onSuccess: () => {
                reset();
                setCode("");
                setAccN("");
                toast.success("Withdrawal request submitted successfully");
            },
            onError: (errors) => {
                console.error("Withdrawal errors:", errors);
            },
        });
    };

    const VerifyBank = async (e) => {
        e.preventDefault();

        if (!accN || !code) {
            toast.error("Please select a bank and enter account number");
            return;
        }

        setLoading(true);

        try {
            await router.post(
                route("bank.account.verify"),
                {
                    accountNumber: accN,
                    bankCode: code,
                },
                {
                    onSuccess: (response) => {
                        toast.success("Bank account verified successfully");
                    },
                    onError: (errors) => {
                        console.error("Verification errors:", errors);
                        toast.error("Failed to verify bank account");
                    },
                    onFinish: () => {
                        setLoading(false);
                    },
                }
            );
        } catch (error) {
            console.error("Verification error:", error);
            toast.error("Failed to verify bank account");
            setLoading(false);
        }
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline" size={'sm'}>Withdraw</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Withdraw Funds</DialogTitle>
                </DialogHeader>
                <form
                    onSubmit={a?.account_name ? handleWithdraw : VerifyBank}
                    className="space-y-4"
                >
                    <div>
                        <Label>Bank Name</Label>
                        <Select
                            value={code}
                            onValueChange={(value) => {
                                setCode(value);
                                // Update form data when bank changes
                                setData("bank_account_id", value);
                            }}
                            required
                        >
                            <SelectTrigger className="w-full bg-amber-50">
                                <SelectValue placeholder="Select bank" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectLabel>Nigerian Banks</SelectLabel>
                                    {banks.map((bank) => (
                                        <SelectItem
                                            key={bank.slug}
                                            value={bank.code}
                                            className="text-black"
                                        >
                                            {bank.name}
                                        </SelectItem>
                                    ))}
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label>Account Number</Label>
                        <Input
                            type="text"
                            value={accN}
                            onChange={(e) => {
                                setAccN(e.target.value);
                                setData("account_number", e.target.value);
                            }}
                            placeholder="Enter account number"
                            maxLength={10}
                            required
                        />
                    </div>

                    {a?.account_name && (
                        <>
                            <div>
                                <Label>Account Name</Label>
                                <Input
                                    type="text"
                                    value={data.account_name}
                                    readOnly
                                    className="bg-gray-100"
                                />
                            </div>
                            <div>
                                <Label>Amount</Label>
                                <Input
                                    type="number"
                                    value={data.amount}
                                    onChange={(e) =>
                                        setData("amount", e.target.value)
                                    }
                                    required
                                    placeholder="Enter amount"
                                    min="1"
                                    step="0.01"
                                />
                            </div>
                        </>
                    )}

                    <DialogFooter>
                        {a?.account_name ? (
                            <Button
                                className="w-full"
                                type="submit"
                                disabled={
                                    processing || !data.amount || !accN || !code
                                }
                            >
                                {processing && (
                                    <Loader className="animate-spin mr-2 h-4 w-4" />
                                )}
                                {processing ? "Processing..." : "Withdraw"}
                            </Button>
                        ) : (
                            <Button
                                className="w-full"
                                type="submit"
                                disabled={loading || !accN || !code}
                            >
                                {loading && (
                                    <Loader className="animate-spin mr-2 h-4 w-4" />
                                )}
                                {loading
                                    ? "Verifying..."
                                    : "Verify Bank Account"}
                            </Button>
                        )}
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
