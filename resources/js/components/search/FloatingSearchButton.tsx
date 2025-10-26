import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { Search } from 'lucide-react';

export default function FloatingSearchButton() {
    return (
        <Link href="/search">
            <Button size="lg" className="fixed right-4 bottom-24 z-40 h-14 w-14 rounded-full shadow-lg transition-all duration-200 hover:shadow-xl">
                <Search className="h-6 w-6" />
            </Button>
        </Link>
    );
}
