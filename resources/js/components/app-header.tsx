import { index } from '@/actions/App/Http/Controllers/Wallet/WalletController';
import { Icon } from '@/components/icon';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import peers from '@/routes/peers';
import tournament from '@/routes/tournament';
import { type BreadcrumbItem, type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Users, Trophy, Wallet, Grid3x3 } from 'lucide-react';

const mainNavItems: NavItem[] = [
    {
        title: 'Peers',
        href: dashboard(),
        icon: Users,
    },
    {
        title: 'Contest',
        href: peers.contents(),
        icon: Grid3x3,
    },
    {
        title: 'Tournament',
        href: tournament.index(),
        icon: Trophy,
    },
    {
        title: 'Wallet',
        href: index(),
        icon: Wallet,
    },
];

interface AppHeaderProps {
    breadcrumbs?: BreadcrumbItem[];
}

export function AppHeader() {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const getInitials = useInitials();

    const isActive = (href: string | { url: string }) => {
        const targetUrl = typeof href === 'string' ? href : href.url;
        return page.url === targetUrl;
    };

    return (
        <>
            {/* Mobile Bottom Tab Bar */}
            <div className=" bottom-0 left-0 right-0 z-50 border-t border-border bg-background/95 backdrop-blur-md shadow-lg">
                <nav className="mx-auto ">
                    <ul className="flex items-center justify-around px-2  pb-safe">
                        {mainNavItems.map((item, index) => {
                            const active = isActive(item.href);
                            return (
                                <li key={index} className="flex-1">
                                    <Link
                                        href={item.href}
                                        className={cn(
                                            'flex flex-col items-center justify-center gap-1 rounded-xl px-2 py-2 transition-all duration-200 active:scale-95',
                                            active
                                                ? 'text-primary'
                                                : 'text-muted-foreground active:text-foreground'
                                        )}
                                    >
                                        <div
                                            className={cn(
                                                'relative flex h-10 w-10 items-center justify-center rounded transition-all duration-200',
                                                active
                                                    ? 'bg-primary/15 '
                                                    : ''
                                            )}
                                        >
                                            {item.icon && (
                                                <Icon
                                                    iconNode={item.icon}
                                                    className={cn(
                                                        'h-5 w-5 transition-transform duration-200',
                                                        active ? 'scale-110' : ''
                                                    )}
                                                />
                                            )}
                                            {active && (
                                                <span className="absolute -top-0.5 -right-0.5 h-2 w-2 rounded-full bg-primary animate-pulse shadow-sm" />
                                            )}
                                        </div>
                                        <span
                                            className={cn(
                                                'text-[10px] font-medium leading-tight transition-all',
                                                active ? 'font-semibold' : ''
                                            )}
                                        >
                                            {item.title}
                                        </span>
                                    </Link>
                                </li>
                            );
                        })}
                        
                        {/* Profile Tab */}
                        <li className="flex-1">
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <button className="flex w-full flex-col items-center justify-center gap-1 rounded-xl px-2 py-2 text-muted-foreground transition-all duration-200 active:scale-95 active:text-foreground">
                                        <div className="relative flex h-11 w-11 items-center justify-center rounded-xl transition-all">
                                            <Avatar className="h-8 w-8 overflow-hidden rounded-full ring-2 ring-background">
                                                <AvatarImage src={auth.user.avatar} alt={auth.user.name} />
                                                <AvatarFallback className="rounded-full bg-gradient-to-br from-blue-600 to-purple-600 text-xs font-semibold text-white">
                                                    {getInitials(auth.user.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                        </div>
                                        <span className="text-[10px] font-medium leading-tight">Profile</span>
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent className="w-56 mb-2" align="end" side="top">
                                    <UserMenuContent user={auth.user} />
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </li>
                    </ul>
                </nav>
            </div>

            {/* Bottom spacer to prevent content from being hidden behind the fixed nav */}
            {/* <div className="h-20" aria-hidden="true" /> */}
        </>
    );
}