import AppLogoIcon from '@/components/app-logo-icon';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    title?: string;
    description?: string;
}

export default function AuthSplitLayout({ children, title, description }: PropsWithChildren<AuthLayoutProps>) {
    const { name } = usePage<SharedData>().props;

    return (
        <div className="grid h-screen lg:grid-cols-3">
            {/* Left panel (branding / tagline / illustration) */}
            <div className="relative hidden lg:flex flex-col justify-center bg-muted p-12 text-white lg:col-span-1">
                <Link href={'#'} className="mb-8 flex items-center text-lg font-medium text-black">
                    <AppLogoIcon className="mr-2 size-8 fill-current " />
                    {name}
                </Link>

                <div className="relative z-10 space-y-6">
                    <h1 className="text-3xl text-black font-bold leading-tight">
                          Play. Compete. Win.
                    </h1>
                    <ul className="space-y-2 text-sm text-muted-foreground/90">
                        {/* <li>✅ Organize tasks effortlessly</li> */}
                         <li>🎯 Pick 5 Star Players</li>
                        <li>⚡ Compete with Friends</li>
                        <li>🏆 Win Big with Real Stats</li>
                    </ul>
                </div>

                {/* Illustration (replace with your own) */}
                <img
                    src="/images/auth.png"
                    alt="Scheduler illustration"
                    className="mt-auto w-full max-w-sm self-center"
                />
            </div>

            {/* Right panel (form area) */}
            <div className="flex items-center justify-center px-6 sm:px-12 lg:col-span-2">
                <div className="w-full max-w-md space-y-6">
                    <Link href={'#'} className="flex justify-center lg:hidden">
                        <AppLogoIcon className="h-10 fill-current text-black sm:h-12" />
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        <h2 className="text-2xl font-semibold">{title}</h2>
                        <p className="text-sm text-muted-foreground">{description}</p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
