import NewPasswordController from '@/actions/App/Http/Controllers/Auth/NewPasswordController';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface ResetPasswordProps {
    phone: string;
    status?: string;
}

export default function ResetPassword({ phone, status }: ResetPasswordProps) {
    return (
        <AuthLayout title="Reset password" description="Enter the OTP sent to your phone and your new password">
            <Head title="Reset password" />

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}

            <Form
                {...NewPasswordController.store.form()}
                resetOnSuccess={['password', 'password_confirmation', 'otp']}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="phone">Phone number</Label>
                            <Input
                                id="phone"
                                type="tel"
                                name="phone"
                                autoComplete="tel"
                                defaultValue={phone}
                                placeholder="08012345678"
                                pattern="^0[7-9][0-1][0-9]{8}$"
                                className="mt-1 block w-full"
                            />
                            <InputError message={errors.phone} className="mt-2" />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="otp">OTP</Label>
                            <Input
                                id="otp"
                                type="text"
                                name="otp"
                                autoComplete="one-time-code"
                                className="mt-1 block w-full"
                                autoFocus
                                placeholder="Enter 6-digit OTP"
                                maxLength={6}
                                // pattern="[0-9]{6}"
                            />
                            <InputError message={errors.otp} />
                            <p className="text-xs text-muted-foreground">Enter the 6-digit code sent to your phone</p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">New Password</Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                placeholder="Password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Confirm password</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                placeholder="Confirm password"
                            />
                            <InputError message={errors.password_confirmation} className="mt-2" />
                        </div>

                        <Button type="submit" className="mt-4 w-full" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Reset password
                        </Button>
                    </div>
                )}
            </Form>
        </AuthLayout>
    );
}
