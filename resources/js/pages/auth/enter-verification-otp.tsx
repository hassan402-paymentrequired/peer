import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldGroup, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { dashboard, logout } from '@/routes';
import { verify as verifyRoute } from '@/routes/phone/verification';
import { Head, router } from '@inertiajs/react';
import { Loader2, LogOut } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';
import { toast } from 'sonner';
import { send } from '@/routes/phone/verification';

interface Props {
    phone: string;
}

export default function EnterVerificationOtp({ phone }: Props) {
    const [otp, setOtp] = useState(['', '', '', '', '', '']);
    const [loading, setLoading] = useState(false);
    const [countdown, setCountdown] = useState(60);

    useEffect(() => {
        if (countdown > 0) {
            const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
            return () => clearTimeout(timer);
        }
    }, [countdown]);

    const handleOtpChange = (index: number, value: string) => {
        if (value.length > 1) return;
        // if (!/^\d*$/.test(value)) return;

        const newOtp = [...otp];
        newOtp[index] = value;
        setOtp(newOtp);

        if (value && index < 5) {
            const nextInput = document.getElementById(`otp-${index + 1}`);
            nextInput?.focus();
        }
    };

    const handleKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Backspace' && !otp[index] && index > 0) {
            const prevInput = document.getElementById(`otp-${index - 1}`);
            prevInput?.focus();
        }
    };

    const verifyOtp = async (e: FormEvent) => {
        e.preventDefault();

        const otpValue = otp.join('');
        if (otpValue.length !== 6) {
            toast.error('Please enter a 6-digit OTP');
            return;
        }

        setLoading(true);
        try {
            const response = await fetch(verifyRoute().url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ otp: otpValue }),
            });

            const data = await response.json();

            if (data.success) {
                toast.success(data.message);
                setTimeout(() => router.visit(dashboard().url), 1500);
            } else {
                toast.error(data.message);
                setOtp(['', '', '', '', '', '']);
                document.getElementById('otp-0')?.focus();
            }
        } catch {
            toast.error('Verification failed. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const handleResend = async () => {
        setOtp(['', '', '', '', '', '']);
        setLoading(true);
        try {
            const response = await fetch(send().url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ channel: 'sms' }),
            });

            const data = await response.json();

            if (data.success) {
                setCountdown(60);
                toast.success(data.message);
            } else {
                toast.error(data.message);
            }
        } catch {
            toast.error('Failed to send OTP. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const handleLogout = () => {
        router.visit(logout().url, {
            method: 'post',
        });
    };

    return (
        <AuthLayout
            title="Enter Verification Code"
            description={`We sent a code to ${phone}`}
        >
            <Head title="Enter Verification Code" />

            <form onSubmit={verifyOtp}>
                <FieldGroup>
                    <FieldSet>
                        <Field>
                            <div className="flex justify-center gap-2">
                                {otp.map((digit, index) => (
                                    <Input
                                        key={index}
                                        id={`otp-${index}`}
                                        type="text"
                                        maxLength={1}
                                        value={digit}
                                        onChange={(e) => handleOtpChange(index, e.target.value)}
                                        onKeyDown={(e) => handleKeyDown(index, e)}
                                        className="size-12 text-center text-lg font-semibold"
                                        disabled={loading}
                                        autoFocus={index === 0}
                                    />
                                ))}
                            </div>
                        </Field>
                    </FieldSet>

                    <Field>
                        <Button type="submit" className="w-full" disabled={loading || otp.join('').length !== 6}>
                            {loading && <Loader2 className="mr-2 size-4 animate-spin" />}
                            Verify Phone Number
                        </Button>
                    </Field>

                    <Field className="flex items-center justify-between">
                        <Button variant="ghost" onClick={handleLogout} type="button" size="sm">
                            <LogOut className="mr-2 size-4" />
                            Logout
                        </Button>

                        {countdown > 0 ? (
                            <FieldDescription>
                                Resend in <strong>{countdown}s</strong>
                            </FieldDescription>
                        ) : (
                            <Button variant="ghost" onClick={handleResend} disabled={loading} type="button" size="sm">
                                {loading && <Loader2 className="mr-2 size-4 animate-spin" />}
                                Resend Code
                            </Button>
                        )}
                    </Field>
                </FieldGroup>
            </form>
        </AuthLayout>
    );
}
