import { index } from '@/actions/App/Http/Controllers/Wallet/WalletController';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Icon } from '@/components/icon';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { NavigationMenu, NavigationMenuItem, NavigationMenuList, navigationMenuTriggerStyle } from '@/components/ui/navigation-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { dashboard,   } from '@/routes';
import peers from '@/routes/peers';
import tournament from '@/routes/tournament';
import { type BreadcrumbItem, type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid } from 'lucide-react';


const mainNavItems: NavItem[] = [
    {
        title: 'Peers',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'My Contest',
        href: peers.contents(),
        icon: LayoutGrid,
    },
    {
        title: 'Tournament',
        href: tournament.index(),
        icon: LayoutGrid,
    },
    {
        title: 'Wallet',
        href: index(),
        icon: LayoutGrid,
    },
];



const activeItemStyles = 'relative';

interface AppHeaderProps {
    breadcrumbs?: BreadcrumbItem[];
}

export function AppHeader({ breadcrumbs = [] }: AppHeaderProps) {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const getInitials = useInitials();
    return (
        <>
            <div className="border-t border-sidebar-border/80">
                <div className="mx-aut flex h-14 items-stretch w-full px-4 lg:max-w-7xl">

                    {/* Desktop Navigation */}
                    {/* <div className="border flex-1 flex h-full"> */}
                        <NavigationMenu className="flex h-full  flex-1  ">
                            <NavigationMenuList className="flex h-full w-full  borer md:space-x-4 ">
                                {mainNavItems.map((item, index) => (
                                    <NavigationMenuItem key={index} className='' >
                                        <Link
                                            href={item.href}
                                            className={cn(
                                                navigationMenuTriggerStyle(),
                                                page.url === (typeof item.href === 'string' ? item.href : item.href.url) && activeItemStyles,
                                                ' cursor-pointer px-3 text-xs md:text-sm ',
                                            )}
                                        >
                                            {item.icon && page.url === (typeof item.href === 'string' ? item.href : item.href.url) && <Icon iconNode={item.icon} className="mr-2 h-4 w-4" />}
                                            {item.title}
                                        </Link>

                                    </NavigationMenuItem>
                                ))}
                            </NavigationMenuList>
                        </NavigationMenu>
                    {/* </div> */}

                    <div className="-auto flex items-center space-x-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="size-10 rounded-full p-1">
                                    <Avatar className="size-8 overflow-hidden rounded-full">
                                        <AvatarImage src={auth.user.avatar} alt={auth.user.name} />
                                        <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {getInitials(auth.user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end">
                                <UserMenuContent user={auth.user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>
            {breadcrumbs.length > 1 && (
                <div className="flex w-full border-b border-sidebar-border/70">
                    <div className="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>
            )}
        </>
    );
}
