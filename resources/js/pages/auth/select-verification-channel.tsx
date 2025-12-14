import { Button } from '@/components/ui/button';
import { Field, FieldContent, FieldDescription, FieldGroup, FieldLabel, FieldSet, FieldTitle } from '@/components/ui/field';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import AuthLayout from '@/layouts/auth-layout';
import { Head, router } from '@inertiajs/react';
import { Loader2, MessageSquare, Phone } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { toast } from 'sonner';
import { send } from '@/routes/phone/verification';

interface Props {
    phone: string;
}

export default function SelectVerificationChannel({ phone }: Props) {
    const [selectedChannel, setSelectedChannel] = useState<'sms' | 'whatsapp'>('sms');
    const [loading, setLoading] = useState(false);

    const sendOtp = async (e: FormEvent) => {
        e.preventDefault();
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
                toast.success(data.message);
                // Redirect to OTP entry page
                router.visit('/verify-phone/enter-otp');
            } else {
                toast.error(data.message);
            }
        } catch {
            toast.error('Failed to send OTP. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthLayout
            title="Verify Your Phone"
            description="Choose how to receive your verification code"
        >
            <Head title="Verify Phone Number" />

            <form onSubmit={sendOtp}>
                <FieldGroup>
                    <FieldSet>
                        <RadioGroup value={selectedChannel} onValueChange={(value) => setSelectedChannel(value as 'sms' | 'whatsapp')}>
                            <FieldLabel htmlFor="sms-option">
                                <Field orientation="horizontal" className='w-full py-2 rounded border border-gray-300'>
                                    <FieldContent>
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-10 items-center justify-center rounded">
                                                <Phone size={13} />
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
        </AuthLayout>
    );
}
