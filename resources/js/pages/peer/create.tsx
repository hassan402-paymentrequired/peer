import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import peers from '@/routes/peers';
import { SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Globe, Info, LoaderIcon, Lock, Target } from 'lucide-react';
import { toast } from 'sonner';

interface CreatePeerProps extends SharedData {
    user: {
        wallet: {
            balance: string;
        };
    };
}

export default function CreatePeer({ user }: CreatePeerProps) {
    const { flash, errors: globalErrors } = usePage<{
        flash: { error: string; success: string };
    }>().props;
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        amount: '',
        limit: '',
        sharing_ratio: '1',
        private: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (Number(user.wallet.balance) < Number(data.amount)) {
            toast.error('Insufficient balance. Please deposit to your wallet', {
                duration: 5000,
                position: 'bottom-center',
            });
            return;
        }

        // console.log(data)

        post(peers.store(), {
            preserveScroll: true,

            onFinish: () => {
                if (flash.error) {
                    toast.error(flash.error);
                }
            },
        });
    };

    const calculatePrizePool = () => {
        const amount = parseFloat(data.amount) || 0;
        const limit = parseInt(data.limit) || 0;
        return (amount * limit).toFixed(2);
    };

    // console.log(globalErrors)

    return (
        <AppLayout>
            <Head title="Create Peer" />

            <div className="space-y-4 p-5">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div>
                        <h1 className="text-xl font-bold text-muted">Create Peer</h1>
                        <p className="text-sm">Start a new group competition</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Basic Info */}
                    <div className="rounded border p-1.5 backdrop-blur-sm">
                        <Card className="bg-default/10 gap-3 rounded border-none border-border p-4 shadow-sm">
                            <CardHeader className="px-0">
                                <CardTitle className="flex items-center gap-2 text-muted">
                                    <Target className="h-5 w-5 text-[var(--clr-primary-a0)]" />
                                    Basic Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 px-0">
                                <div className="flex flex-col gap-1">
                                    <Label htmlFor="name" className="text-muted-white">
                                        Peer Name
                                    </Label>
                                    <Input
                                        id="name"
                                        type="text"
                                        placeholder="Enter peer name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className={`₦{ errors.name ? "border-red-500" : "" } text-muted-white`}
                                    />
                                    {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                                </div>

                                <div className="flex flex-col gap-1">
                                    <Label htmlFor="amount" className="text-muted-white">
                                        Entry Fee (₦)
                                    </Label>
                                    <Input
                                        id="amount"
                                        type="number"
                                        step="1"
                                        placeholder="0.00"
                                        value={data.amount}
                                        onChange={(e) => setData('amount', e.target.value)}
                                        className={`₦{ errors.amount ? "border-red-500" : "" }`}
                                    />
                                    {errors.amount && <p className="mt-1 text-sm text-red-600">{errors.amount}</p>}
                                </div>

                                <div className="flex flex-col gap-1">
                                    <Label htmlFor="limit" className="text-muted-white">
                                        Player Limit
                                    </Label>
                                    <Input
                                        id="limit"
                                        type="text"
                                        placeholder="Leave empty for unlimited"
                                        value={data.limit}
                                        onChange={(e) => setData('limit', e.target.value)}
                                        className={errors.limit ? 'border-red-500' : ''}
                                    />
                                    {errors.limit && <p className="mt-1 text-sm text-red-600">{errors.limit}</p>}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Settings */}
                    <div className="rounded border p-1.5 backdrop-blur-sm">
                        <Card className="bg-default/10 gap-3 rounded border-none border-border p-4 shadow-sm">
                            {/* <Card className="bg-background rounded p-4 gap-3 border-border"> */}
                            <CardHeader className="p-0">
                                <CardTitle className="flex items-center gap-2 text-muted">
                                    <Info className="h-5 w-5 text-[var(--clr-primary-a0)]" />
                                    Settings
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 p-0">
                                <div className="flex flex-col gap-1">
                                    <Label htmlFor="sharing_ratio" className="text-muted-white">
                                        Sharing Ratio
                                    </Label>
                                    <Select value={data.sharing_ratio} onValueChange={(e) => setData('sharing_ratio', e)}>
                                        <SelectTrigger className={`${errors.sharing_ratio ? 'border-red-500' : ''} text-muted-white w-full`}>
                                            <SelectValue placeholder="Select" className="text-muted" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">100%</SelectItem>
                                            <SelectItem value="3">Spread</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p className="mt-1 text-xs text-muted-foreground">How much of the prize pool the winner gets (1.0 = 100%)</p>
                                    {errors.sharing_ratio && <p className="mt-1 text-sm text-red-600">{errors.sharing_ratio}</p>}
                                </div>

                                <div className="flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        id="private"
                                        checked={data.private}
                                        onChange={(e) => setData('private', e.target.checked)}
                                        className="rounded border-gray-300"
                                    />
                                    <Label htmlFor="private" className="flex items-center gap-2 text-muted">
                                        <Lock className="h-4 w-4" />
                                        Private Peer
                                    </Label>
                                </div>
                                <p className="text-xs text-muted-foreground">Private peers are only visible to invited users</p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Preview */}
                    <div className="rounded border p-1.5 backdrop-blur-sm">
                        <Card className="bg-default/10 gap-3 rounded border-none border-border p-4 shadow-sm">
                            <CardHeader className="p-0">
                                <CardTitle className="text-muted-white flex items-center gap-2">
                                    <Globe className="h-5 w-5" />
                                    Peer Preview
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 p-0">
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-white text-sm">Name:</span>
                                    <span className="font-medium text-muted">{data.name || 'Your Peer Name'}</span>
                                </div>

                                <div className="flex items-center justify-between">
                                    <span className="text-muted-white text-sm">Entry Fee:</span>
                                    <span className="font-medium text-muted">₦{data.amount || '0.00'}</span>
                                </div>

                                <div className="flex items-center justify-between">
                                    <span className="text-muted-white text-sm">Player Limit:</span>
                                    <span className="font-medium text-muted">{data.limit || 'Unlimited'}</span>
                                </div>

                                <div className="flex items-center justify-between">
                                    <span className="text-muted-white text-sm">Sharing Ratio:</span>
                                    <span className="font-medium text-muted">{data.sharing_ratio || '1'}x</span>
                                </div>

                                <div className="flex items-center justify-between">
                                    <span className="text-muted-white text-sm">Privacy:</span>
                                    <Badge className={data.private ? 'bg-red-100 text-red-800' : 'bg-[var(--clr-primary-a0)] text-muted'}>
                                        {data.private ? 'Private' : 'Public'}
                                    </Badge>
                                </div>

                                {data.amount && data.limit && (
                                    <div className="border-t border-[var(--clr-primary-a0)] pt-3">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm font-medium text-muted">Total Prize Pool:</span>
                                            <span className="text-muted-white text-lg font-bold">₦{calculatePrizePool()}</span>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Wallet Info */}
                    <div className="rounded border p-1.5 backdrop-blur-sm">
                        <Card className="bg-default/10 gap-3 rounded border-none border-border p-4 shadow-sm">
                            <CardContent className="p-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div>
                                            <p className="text-sm font-medium text-muted">Your Balance</p>
                                            <p className="text-muted-white text-lg font-bold">₦{user.wallet.balance}</p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-xs text-muted">Required</p>
                                        <p className="text-muted-white text-sm font-medium">₦{data.amount || '0.00'}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    {/* Submit */}
                    <div className="space-y-3">
                        <Button type="submit" className="w-full tracking-wider" disabled={processing || !data.name || !data.amount}>
                            {processing && <LoaderIcon className="animate-spin" />}
                            Create Peer
                        </Button>

                        <p className="text-center text-xs text-muted-foreground">
                            You'll be charged ₦{data.amount || '0.00'} from your wallet when you create this peer
                        </p>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
