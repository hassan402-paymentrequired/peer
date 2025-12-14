import { Button } from '@/components/ui/button';
import { FieldDescription, FieldGroup } from '@/components/ui/field';
import AuthLayout from '@/layouts/auth-layout';
import { dashboard } from '@/routes';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { useEffect } from 'react';

interface Props {
    phone: string;
    isVerified: boolean;
}

export default function VerifyPhone({ phone, isVerified }: Props) {
    useEffect(() => {
        // If not verified, redirect to channel selection page
        if (!isVerified) {
            router.visit('/verify-phone/select-channel');
        }
    }, [isVerified]);

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

    // Show loading while redirecting
    return (
        <AuthLayout title="Verify Your Phone" description="Redirecting...">
            <Head title="Verify Phone Number" />
            <div className="flex items-center justify-center py-8">
                <p className="text-muted-foreground">Redirecting to verification...</p>
            </div>
        </AuthLayout>
    );
}
