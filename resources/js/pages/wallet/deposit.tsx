import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import wallet from "@/routes/wallet";
import { useForm, usePage } from "@inertiajs/react";
import { useEffect } from "react";
import { toast } from "sonner";

export function Deposit() {
    const {
        flash: { success, error },
    } = usePage<{ flash: { success: string; error: string } }>().props;
    const { data, post, processing, errors, setData } = useForm({
        amount: 0,
    });

    useEffect(() => {
        if (success && success.startsWith("https")) {
            window.location.href = success;
        }


    }, [success, error]);


    const handlePayment = () => {
        if (Number(data.amount) <= 100) {
            toast.error(
                "Please enter a valid amount and greater than 100 naira"
            );
            return;
        }

        console.log(data.amount);
        post(wallet.fund(), {
            preserveScroll: true,
        });
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size={'sm'}>Deposit</Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Deposit</DialogTitle>
                    <DialogDescription>
                        Enter the amount you want to deposit
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4">
                    <div className="grid gap-3">
                        <Label htmlFor="amount">Amount</Label>
                        <Input
                            id="amount"
                            value={data.amount}
                            onChange={(e) => setData("amount", e.target.value)}
                            name="amount"
                            type="text"
                        />
                    </div>
                </div>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button type="submit" onClick={handlePayment}>
                        Deposit
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
