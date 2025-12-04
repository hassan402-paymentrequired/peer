import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, LoaderCircle, Phone, Shield, User } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { PasswordInput } from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

// Password strength calculator
const calculatePasswordStrength = (password: string): { strength: number; label: string; color: string } => {
    if (!password) return { strength: 0, label: '', color: '' };

    let strength = 0;
    const checks = {
        length: password.length >= 8,
        lowercase: /[a-z]/.test(password),
        uppercase: /[A-Z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[^A-Za-z0-9]/.test(password),
    };

    strength += checks.length ? 20 : 0;
    strength += checks.lowercase ? 20 : 0;
    strength += checks.uppercase ? 20 : 0;
    strength += checks.number ? 20 : 0;
    strength += checks.special ? 20 : 0;

    if (strength <= 40) return { strength, label: 'Weak', color: 'bg-red-500' };
    if (strength <= 60) return { strength, label: 'Fair', color: 'bg-orange-500' };
    if (strength <= 80) return { strength, label: 'Good', color: 'bg-yellow-500' };
    return { strength, label: 'Strong', color: 'bg-green-500' };
};

export default function Register() {
    const [step, setStep] = useState(1);
    const [formData, setFormData] = useState({
        name: '',
        phone: '',
        password: '',
        password_confirmation: '',
    });
    const [passwordStrength, setPasswordStrength] = useState({ strength: 0, label: '', color: '' });

    const handleInputChange = (field: string, value: string) => {
        setFormData((prev) => ({ ...prev, [field]: value }));

        if (field === 'password') {
            setPasswordStrength(calculatePasswordStrength(value));
        }
    };

    const canProceedToStep2 = formData.name.trim() !== '' && /^0[7-9][0-1][0-9]{8}$/.test(formData.phone);

    return (
        <AuthLayout title="Create an account" description="Join thousands of fantasy football players">
            <Head title="Register" />

            <Form
                {...RegisteredUserController.store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        {/* Show errors from any step */}
                        {(errors.name || errors.phone) && step === 2 && (
                            <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                                <p className="font-medium">Registration Error:</p>
                                {errors.name && <p>• {errors.name}</p>}
                                {errors.phone && <p>• {errors.phone}</p>}
                                <button
                                    type="button"
                                    onClick={() => setStep(1)}
                                    className="mt-2 text-xs underline hover:no-underline"
                                >
                                    Go back to fix these issues
                                </button>
                            </div>
                        )}

                        {/* Step 1: Basic Information */}
                        {step === 1 && (
                            <div className="space-y-5 duration-300 animate-in fade-in slide-in-from-right-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name" className="flex items-center gap-2 text-sm font-medium">
                                        <User className="h-4 w-4" />
                                        Username
                                    </Label>
                                    <div className="relative">
                                        <div className="absolute top-1/2 left-3 flex -translate-y-1/2 items-center text-sm text-muted-foreground">
                                            <span className="font-medium">@</span>
                                        </div>
                                        <Input
                                            id="name"
                                            type="text"
                                            required
                                            autoFocus
                                            autoComplete="name"
                                            name="name"
                                            value={formData.name}
                                            onChange={(e) => handleInputChange('name', e.target.value)}
                                            placeholder="Enter your username"
                                            className="pl-8"
                                        />
                                    </div>
                                    <InputError message={errors.name} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="phone" className="flex items-center gap-2 text-sm font-medium">
                                        <Phone className="h-4 w-4" />
                                        Phone Number
                                    </Label>
                                    <div className="relative">
                                        <div className="absolute top-1/2 left-3 flex -translate-y-1/2 items-center gap-1 text-sm text-muted-foreground">
                                            <span className="text-base">🇳🇬</span>
                                        </div>
                                        <Input
                                            id="phone"
                                            type="tel"
                                            required
                                            autoComplete="tel"
                                            name="phone"
                                            value={formData.phone}
                                            onChange={(e) => handleInputChange('phone', e.target.value)}
                                            placeholder="08012345678"
                                            pattern="^0[7-9][0-1][0-9]{8}$"
                                            className="pl-10"
                                        />
                                    </div>
                                    <InputError message={errors.phone} />
                                    <p className="text-xs text-muted-foreground">Enter a valid Nigerian phone number</p>
                                </div>

                                <Button
                                    type="button"
                                    onClick={() => canProceedToStep2 && setStep(2)}
                                    disabled={!canProceedToStep2}
                                    className="mt-4 w-full"
                                >
                                    Continue
                                    <ArrowRight className="ml-2 h-4 w-4" />
                                </Button>
                            </div>
                        )}

                        {/* Step 2: Password */}
                        {step === 2 && (
                            <div className="space-y-5 duration-300 animate-in fade-in slide-in-from-right-4">
                                {/* Hidden inputs to preserve Step 1 data */}
                                <input type="hidden" name="name" value={formData.name} />
                                <input type="hidden" name="phone" value={formData.phone} />

                                <div className="space-y-2">
                                    <Label htmlFor="password" className="flex items-center gap-2 text-sm font-medium">
                                        <Shield className="h-4 w-4" />
                                        Password
                                    </Label>
                                    <PasswordInput
                                        id="password"
                                        required
                                        autoComplete="new-password"
                                        name="password"
                                        value={formData.password}
                                        onChange={(e) => handleInputChange('password', e.target.value)}
                                        placeholder="Create a strong password"
                                    />
                                    <InputError message={errors.password} />

                                    {/* Password Strength Meter */}
                                    {formData.password && (
                                        <div className="space-y-2 pt-2">
                                            <div className="flex items-center justify-between text-xs">
                                                <span className="text-muted-foreground">Password strength:</span>
                                                <span
                                                    className={`font-semibold ${passwordStrength.strength > 60 ? 'text-green-600' : 'text-orange-600'}`}
                                                >
                                                    {passwordStrength.label}
                                                </span>
                                            </div>
                                            <div className="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                                                <div
                                                    className={`h-full transition-all duration-300 ${passwordStrength.color}`}
                                                    style={{ width: `${passwordStrength.strength}%` }}
                                                />
                                            </div>
                                            <div className="space-y-1 text-xs text-muted-foreground">
                                                <p className={formData.password.length >= 8 ? 'text-green-600' : ''}>
                                                    {formData.password.length >= 8 ? '✓' : '○'} At least 8 characters
                                                </p>
                                                <p className={/[A-Z]/.test(formData.password) ? 'text-green-600' : ''}>
                                                    {/[A-Z]/.test(formData.password) ? '✓' : '○'} One uppercase letter
                                                </p>
                                                <p className={/[0-9]/.test(formData.password) ? 'text-green-600' : ''}>
                                                    {/[0-9]/.test(formData.password) ? '✓' : '○'} One number
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="password_confirmation" className="text-sm font-medium">
                                        Confirm Password
                                    </Label>
                                    <PasswordInput
                                        id="password_confirmation"
                                        required
                                        autoComplete="new-password"
                                        name="password_confirmation"
                                        value={formData.password_confirmation}
                                        onChange={(e) => handleInputChange('password_confirmation', e.target.value)}
                                        placeholder="Confirm your password"
                                    />
                                    <InputError message={errors.password_confirmation} />
                                    {formData.password_confirmation && formData.password === formData.password_confirmation && (
                                        <p className="text-xs text-green-600">✓ Passwords match</p>
                                    )}
                                </div>

                                <div className="flex gap-3">
                                    <Button type="button" onClick={() => setStep(1)} variant="outline" className="flex-1">
                                        <ArrowLeft className="mr-2 h-4 w-4" />
                                        Back
                                    </Button>
                                    <Button type="submit" disabled={processing} className="flex-1">
                                        {processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                                        Create Account
                                    </Button>
                                </div>
                            </div>
                        )}

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink href={login()} className="font-semibold">
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
