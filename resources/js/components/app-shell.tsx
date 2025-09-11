import { Link } from "@inertiajs/react";
import AppLogoIcon from "./app-logo-icon";

interface AppShellProps {
    children: React.ReactNode;
    variant?: 'header' | 'sidebar';
}

export function AppShell({ children, variant = 'header' }: AppShellProps) {
    return (
        <div className="grid shadow  w-full lg:max-w-4xl mx-auto h-screen lg:grid-cols-3 overflow-hidden">
            {/* Left panel (branding / tagline / illustration) */}
            <div className="relative hidden flex-col justify-center bg-muted p-12 text-white lg:col-span-1 lg:flex">
                <Link href={'#'} className="mb-8 flex items-center text-lg font-medium text-black">
                    <AppLogoIcon className="mr-2 size-8 fill-current" />
                    Starpick
                </Link>

                <div className="relative z-10 space-y-6">
                    <h1 className="text-3xl leading-tight font-bold text-black">Play. Compete. Win.</h1>
                    <ul className="space-y-2 text-sm text-muted-foreground/90">
                        {/* <li>✅ Organize tasks effortlessly</li> */}
                        <li>🎯 Pick 5 Star Players</li>
                        <li>⚡ Compete with Friends</li>
                        <li>🏆 Win Big with Real Stats</li>
                    </ul>
                </div>

                {/* Illustration (replace with your own) */}
                <img src="/images/auth.png" alt="Scheduler illustration" className="mt-auto w-full max-w-sm self-center" />
            </div>

            {/* Right panel (form area) */}
            <div className="flex flex-col  min-h-screen w-full flex-col col-span-3 lg:col-span-2">{children}</div>
        </div>
    );
}
