import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Loader2, MessageSquare, Smartphone } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';
import { toast } from 'sonner';
import { dashboard } from '@/routes';
import { notice, send, verify as verifyRoute, resend } from '@/routes/phone/verification';

interface Props {
    phone: string;
    isVerified: boolean;
}

export default function VerifyPhone({ phone, isVerified }: Props) {
    const [otp, setOtp] = useState('');
    const [loading, setLoading] = useState(false);
    const [countdown, setCountdown] = useState(0);
    const [deliveryMethod, setDeliveryMethod] = useState<string | null>(null);

    useEffect(() => {
        if (countdown > 0) {
            const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
            return () => clearTimeout(timer);
        }
    }, [countdown]);

    const sendOtp = async () => {
        setLoading(true);
        try {
            const response = await fetch(send.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (data.success) {
                setDeliveryMethod(data.delivery_method);
                setCountdown(60);
                toast.success(data.message);
            } else {
                toast.error(data.message);
            }
        } catch (error) {
            toast.error('Failed to send OTP. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const verifyOtp = async (e: FormEvent) => {
        e.preventDefault();

        if (otp.length !== 6) {
            toast.error('Please enter a 6-digit OTP');
            return;
        }

        setLoading(true);
        try {
            const response = await fetch(verifyRoute.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ otp }),
            });

            const data = await response.json();

            if (data.success) {
                toast.success(data.message);
                // Redirect to dashboard after successful verification
                setTimeout(() => router.visit(dashboard.url), 1500);
            } else {
                toast.error(data.message);
            }
        } catch (error) {
            toast.error('Verification failed. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    if (isVerified) {
        return (
            <>
                <Head title="Phone Verified" />
                <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
                    <Card className="w-full max-w-md">
                        <CardHeader className="text-center">
                            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                                <CheckCircle2 className="h-8 w-8 text-green-600" />
                            </div>
                            <CardTitle>Phone Verified</CardTitle>
                            <CardDescription>Your phone number is already verified</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button onClick={() => router.visit(dashboard.url)} className="w-full">
                                Go to Dashboard
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Verify Phone Number" />
            <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
                <Card className="w-full max-w-md">
                    <CardHeader className="text-center">
                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">
                            <Smartphone className="h-8 w-8 text-blue-600" />
                        </div>
                        <CardTitle>Verify Your Phone Number</CardTitle>
                        <CardDescription>We'll send a verification code to {phone}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {deliveryMethod && (
                            <div className="flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                                {deliveryMethod === 'WhatsApp' ? (
                                    <MessageSquare className="h-4 w-4" />
                                ) : (
                                    <Smartphone className="h-4 w-4" />
                                )}
                                <span>OTP sent via {deliveryMethod}</span>
                            </div>
                        )}

                        <form onSubmit={verifyOtp} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="otp">Enter 6-Digit Code</Label>
                                <Input
                                    id="otp"
                                    type="text"
                                    inputMode="numeric"
                                    maxLength={6}
                                    value={otp}
                                    onChange={(e) => setOtp(e.target.value.replace(/\D/g, ''))}
                                    placeholder="000000"
                                    className="text-center text-2xl tracking-widest"
                                    disabled={loading}
                                />
                            </div>

                            <Button type="submit" className="w-full" disabled={loading || otp.length !== 6}>
                                {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                Verify Phone Number
                            </Button>
                        </form>

                        <div className="text-center">
                            {countdown > 0 ? (
                                <p className="text-sm text-gray-600">Resend code in {countdown}s</p>
                            ) : (
                                <Button
                                    variant="ghost"
                                    onClick={sendOtp}
                                    disabled={loading}
                                    className="text-sm"
                                >
                                    {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    {deliveryMethod ? 'Resend Code' : 'Send Verification Code'}
                                </Button>
                            )}
                        </div>

                        {!deliveryMethod && (
                            <div className="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600">
                                <p className="font-medium">Note:</p>
                                <ul className="mt-1 list-inside list-disc space-y-1">
                                    <li>SMS delivery: 8 AM - 8 PM (Nigeria time)</li>
                                    <li>WhatsApp delivery: 8 PM - 8 AM (off-hours)</li>
                                </ul>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
