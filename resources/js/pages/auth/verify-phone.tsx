import { Button } from '@/components/ui/button';
import { Field, FieldContent, FieldDescription, FieldGroup, FieldLabel, FieldSet, FieldTitle } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import AuthLayout from '@/layouts/auth-layout';
import { dashboard } from '@/routes';
import { send, verify as verifyRoute } from '@/routes/phone/verification';
import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, Loader2, MessageSquare, Phone, Shield } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';
import { toast } from 'sonner';
import { logout } from '@/routes';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { ChangeNumber } from '@/components/features/change-number';

interface Props {
    phone: string;
    isVerified: boolean;
}

export default function VerifyPhone({ phone, isVerified }: Props) {
    const [step, setStep] = useState<'channel' | 'otp'>('channel');
    const [selectedChannel, setSelectedChannel] = useState<'sms' | 'whatsapp'>('sms');
    const [otp, setOtp] = useState(['', '', '', '', '', '']);
    const [loading, setLoading] = useState(false);
    const [countdown, setCountdown] = useState(0);
    const cleanup = useMobileNavigation();

    useEffect(() => {
        if (countdown > 0) {
            const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
            return () => clearTimeout(timer);
        }
    }, [countdown]);


    const sendOtp = async () => {
        setLoading(true);
        try {
            const response = await fetch(send().url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ channel: selectedChannel }),
            });

            const data = await response.json();

            if (data.success) {
                setStep('otp');
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

    const handleOtpChange = (index: number, value: string) => {
        if (value.length > 1) return;
        if (!/^\d*$/.test(value)) return;

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
        await sendOtp();
    };

    if (isVerified) {
        return (
            <AuthLayout title="Phone Verified" description="Your account is ready to use">
                <Head title="Phone Verified" />
                <div className="flex flex-col items-center gap-6 text-center">
                    <div className="flex size-16 items-center justify-center rounded-full bg-green-100">
                        <CheckCircle2 className="size-8 text-green-600" />
                    </div>
                    <FieldGroup>
                        <FieldDescription>Your phone number is already verified. You can now access your account.</FieldDescription>
                        <Button onClick={() => router.visit(dashboard().url)} className="w-full">
                            Go to Dashboard
                        </Button>
                    </FieldGroup>
                </div>
            </AuthLayout>
        );
    }

     const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <AuthLayout
            title={step === 'channel' ? 'Verify Your Phone' : 'Enter Verification Code'}
            description={step === 'channel' ? 'Choose how to receive your verification code' : `We sent a code to ${phone}`}
        >
            <Head title="Verify Phone Number" />

            {step === 'channel' ? (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        sendOtp();
                    }}
                >
                    <FieldGroup>
                        <FieldSet>
                            <RadioGroup value={selectedChannel} onValueChange={(value) => setSelectedChannel(value as 'sms' | 'whatsapp')}>
                                <FieldLabel htmlFor="sms-option">
                                    <Field orientation="horizontal" className='w-full py-2 rounded border border-gray-300'>
                                        <FieldContent>
                                            <div className="flex items-center gap-3">
                                                <div className="flex size-10 items-center justify-center rounded">
                                                    <Phone size={13}/>
                                                </div>
                                                <div>
                                                    <FieldTitle className='text-sm'>SMS Message</FieldTitle>
                                                    <FieldDescription className='text-xs'>Receive code via text message  
                                                       </FieldDescription>
                                                </div>
                                            </div>
                                        </FieldContent>
                                        <RadioGroupItem value="sms" id="sms-option" />
                                    </Field>
                                </FieldLabel>
                                <FieldLabel htmlFor="whatsapp-option" className='mt-3'>
                                    <Field orientation="horizontal" className='w-full py-2 rounded border border-gray-300'>
                                        <FieldContent>
                                            <div className="flex items-center gap-3">
                                                <div className="flex size-10 items-center justify-center rounded">
                                                    <MessageSquare size={13} />
                                                </div>
                                                <div>
                                                    <FieldTitle className='text-sm'>WhatsApp</FieldTitle>
                                                    <FieldDescription className='text-xs'>Receive code via WhatsApp</FieldDescription>
                                                </div>
                                            </div>
                                        </FieldContent>
                                        <RadioGroupItem value="whatsapp" id="whatsapp-option" />
                                    </Field>
                                </FieldLabel>
                            </RadioGroup>
                        </FieldSet>

                        <Field>
                            <Button type="submit" className="w-full" disabled={loading}>
                                {loading && <Loader2 className="mr-2 size-4 animate-spin" />}
                                Send Verification Code
                            </Button>
                            
                        </Field>

                        <FieldDescription className="rounded-lg border border-blue-100 bg-blue-50 p-3 text-xs text-blue-800">
                            <strong>Quick Information:</strong>
                            <br />• SMS: Instant delivery (8 AM - 8 PM)
                            <br />• WhatsApp: Available 24/7
                        </FieldDescription>
                    
                    </FieldGroup>
                </form>
            ) : (
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
                                            inputMode="numeric"
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

                        <Field className=" flex items-center justify-between">
                           <ChangeNumber />

                            {countdown > 0 ? (
                                <FieldDescription>
                                    Resend code in <strong>{countdown}s</strong>
                                </FieldDescription>
                            ) : (
                                <Button variant="ghost" onClick={handleResend} disabled={loading} type="button">
                                    {loading && <Loader2 className="mr-2 size-4 animate-spin" />}
                                    Resend Code
                                </Button>
                            )}
                        </Field>
                    </FieldGroup>
                </form>
            )}
        </AuthLayout>
    );
}
