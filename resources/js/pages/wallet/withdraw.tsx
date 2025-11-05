// resources/js/Components/WithdrawModal.jsx
import { initiateWithdrawal } from '@/actions/App/Http/Controllers/Wallet/WalletController';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import {  useForm } from '@inertiajs/react';
import { Loader } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

export default function WithdrawModal() {
    // const { flash } = usePage<{
    //     data: {
    //         account_name: string;
    //         account_number: string;
    //         bank_id: string | number;
    //     };
    //     flash: { success: string; error: string };
    // }>().props;
    const [code, setCode] = useState('');
    const [accN, setAccN] = useState('');
    const [loading, setLoading] = useState(false);
    const [banks, setBanks] = useState<{ id: number; name: string; code: string; slug: string }[]>([]);
    const themagicthing = import.meta.env.VITE_PAYSTACK_SEC;

    const { data, setData, post, processing, reset, transform } = useForm({
        amount: '',
        account_number: '',
        bank_code: '',
        account_name: '',
        bank_name: ''
    });

    useEffect(() => {
      getBanks()
    }, [])


    const getBanks = async () => {
        try {
            const res = await fetch('https://api.paystack.co/bank?currency=NGN');
            const responseData = await res.json();
            console.log(responseData)

            if (responseData.status) {
                setBanks(responseData.data);
            } else {
                toast.error('Failed to fetch banks');
            }
        } catch (error) {
            console.error('Error fetching banks:', error);
            toast.error('Failed to fetch banks');
        }
    };

    const verifyBank = async () => {
        setLoading(true)
        try {
            const res = await fetch(`https://api.paystack.co/bank/resolve?account_number=${accN}&bank_code=${code}`, {
                method: 'GET',
                headers: {
                    Authorization: `Bearer ${themagicthing}`,
                    'Content-Type': 'application/json',
                },
            });

            const result = await res.json();
            console.log(result)
            setData('account_name', result.data.account_name);
            setData('account_number', result.data.account_number);
            setAccN(result.data.account_number);
            const selectedBank = banks.find((bank) => bank.id.toString() === result.data.bank_id.toString());
            if (selectedBank) {
                setCode(selectedBank.code);
                setData('bank_code', selectedBank.code);
                setData('bank_name', selectedBank.name)
            }
        } catch (error) {
            console.error('Error verifying bank:', error);
        }finally{
            setLoading(false)
        }
    };

    const handleWithdraw = (e: React.FormEvent) => {
        e.preventDefault();

        transform((data) => ({
            ...data,
            account_number: accN,
            bank_code: code,
        }));

        post(initiateWithdrawal(), {
            onSuccess: () => {
                reset();
                setCode('');
                setAccN('');
                toast.success('Withdrawal request submitted successfully');
            },
            onError: (errors) => {
                if(errors.bank_code)
                {
                    toast.error(errors.bank_code)
                }
                console.error('Withdrawal errors:', errors);
            },
        });
    };



    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline" size={'sm'}>
                    Withdraw
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Withdraw Funds</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleWithdraw} className="space-y-4">
                    <div>
                        <Label>Bank Name</Label>
                        <Select
                            value={code}
                            onValueChange={(value) => {
                                setCode(value);
                            }}
                            required
                        >
                            <SelectTrigger className="w-full rounded">
                                <SelectValue placeholder="Select bank" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectLabel>Nigerian Banks</SelectLabel>
                                    {banks.map((bank) => (
                                        <SelectItem key={bank.slug} value={bank.code} className="text-black">
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
                                setData('account_number', e.target.value);
                            }}
                            placeholder="Enter account number"
                            maxLength={10}
                            required
                        />
                    </div>

                    {data.account_name && (
                        <>
                            <div>
                                <Label>Account Name</Label>
                                <Input type="text" value={data.account_name} readOnly className="bg-gray-100" />
                            </div>
                            <div>
                                <Label>Amount</Label>
                                <Input
                                    type="number"
                                    value={data.amount}
                                    onChange={(e) => setData('amount', e.target.value)}
                                    required
                                    placeholder="Enter amount"
                                    min="1"
                                    step="0.01"
                                />
                            </div>
                        </>
                    )}

                    <DialogFooter>
                        {data?.account_name ? (
                            <Button className="w-full" type="submit" disabled={processing || !data.amount || !accN || !code}>
                                {processing && <Loader className="mr-2 h-4 w-4 animate-spin" />}
                                {processing ? 'Processing...' : 'Withdraw'}
                            </Button>
                        ) : (
                            <Button className="w-full" type="button" onClick={verifyBank} disabled={loading || !accN || !code}>
                                {loading && <Loader className="mr-2 h-4 w-4 animate-spin" />}
                                {loading ? 'Verifying...' : 'Verify Bank Account'}
                            </Button>
                        )}
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
