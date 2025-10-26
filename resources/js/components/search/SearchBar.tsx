import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { Search, Target, Trophy, User, Users, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface SearchResult {
    peers: any[];
    tournaments: any[];
    users: any[];
    players: any[];
}

export default function SearchBar({ className }: { className?: string }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult>({ peers: [], tournaments: [], users: [], players: [] });
    const [isOpen, setIsOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const searchRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (searchRef.current && !searchRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    useEffect(() => {
        const searchTimeout = setTimeout(async () => {
            if (query.length >= 2) {
                setIsLoading(true);
                try {
                    const response = await fetch(`/search/api?q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    setResults(data);
                    setIsOpen(true);
                } catch (error) {
                    console.error('Search error:', error);
                } finally {
                    setIsLoading(false);
                }
            } else {
                setResults({ peers: [], tournaments: [], users: [], players: [] });
                setIsOpen(false);
            }
        }, 300);

        return () => clearTimeout(searchTimeout);
    }, [query]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (query.trim()) {
            router.get('/search', { q: query.trim() });
            setIsOpen(false);
            inputRef.current?.blur();
        }
    };

    const clearSearch = () => {
        setQuery('');
        setResults({ peers: [], tournaments: [], users: [], players: [] });
        setIsOpen(false);
        inputRef.current?.focus();
    };

    const getTotalResults = () => {
        return results.peers.length + results.tournaments.length + results.users.length + results.players.length;
    };

    return (
        <div ref={searchRef} className={cn('relative', className)}>
            <form onSubmit={handleSubmit} className="relative">
                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <Input
                    ref={inputRef}
                    type="text"
                    placeholder="Search peers, tournaments, users..."
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    className="pr-10 pl-10"
                />
                {query && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={clearSearch}
                        className="absolute top-1/2 right-1 h-6 w-6 -translate-y-1/2 p-0"
                    >
                        <X className="h-3 w-3" />
                    </Button>
                )}
            </form>

            {/* Search Results Dropdown */}
            {isOpen && query.length >= 2 && (
                <div className="absolute top-full right-0 left-0 z-50 mt-1 max-h-96 overflow-y-auto rounded-md border bg-white shadow-lg">
                    {isLoading ? (
                        <div className="p-4 text-center text-sm text-gray-500">Searching...</div>
                    ) : getTotalResults() > 0 ? (
                        <div className="p-2">
                            {/* Peers */}
                            {results.peers.length > 0 && (
                                <div className="mb-3">
                                    <div className="mb-2 flex items-center gap-2 px-2 text-xs font-medium text-gray-500">
                                        <Target className="h-3 w-3" />
                                        PEERS
                                    </div>
                                    {results.peers.slice(0, 3).map((peer) => (
                                        <Link
                                            key={peer.id}
                                            href={`/peers/${peer.peer_id}`}
                                            className="block rounded px-3 py-2 hover:bg-gray-50"
                                            onClick={() => setIsOpen(false)}
                                        >
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <div className="font-medium capitalize">{peer.name}</div>
                                                    <div className="text-xs text-gray-500">by @{peer.created_by?.name}</div>
                                                </div>
                                                <Badge variant="outline" className="text-xs">
                                                    ₦{peer.amount}
                                                </Badge>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            )}

                            {/* Tournaments */}
                            {results.tournaments.length > 0 && (
                                <div className="mb-3">
                                    <div className="mb-2 flex items-center gap-2 px-2 text-xs font-medium text-gray-500">
                                        <Trophy className="h-3 w-3" />
                                        TOURNAMENTS
                                    </div>
                                    {results.tournaments.slice(0, 3).map((tournament) => (
                                        <Link
                                            key={tournament.id}
                                            href="/tournament"
                                            className="block rounded px-3 py-2 hover:bg-gray-50"
                                            onClick={() => setIsOpen(false)}
                                        >
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <div className="font-medium capitalize">{tournament.name}</div>
                                                    <div className="text-xs text-gray-500">{tournament.users_count} participants</div>
                                                </div>
                                                <Badge variant="outline" className="text-xs">
                                                    ₦{tournament.amount}
                                                </Badge>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            )}

                            {/* Users */}
                            {results.users.length > 0 && (
                                <div className="mb-3">
                                    <div className="mb-2 flex items-center gap-2 px-2 text-xs font-medium text-gray-500">
                                        <User className="h-3 w-3" />
                                        USERS
                                    </div>
                                    {results.users.slice(0, 3).map((user) => (
                                        <div key={user.id} className="rounded px-3 py-2 hover:bg-gray-50">
                                            <div className="flex items-center gap-2">
                                                <div className="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100">
                                                    <span className="text-xs font-medium text-blue-600">
                                                        {user.name.substring(0, 1).toUpperCase()}
                                                    </span>
                                                </div>
                                                <div>
                                                    <div className="font-medium">@{user.name}</div>
                                                    <div className="text-xs text-gray-500">{user.email}</div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Players */}
                            {results.players.length > 0 && (
                                <div className="mb-3">
                                    <div className="mb-2 flex items-center gap-2 px-2 text-xs font-medium text-gray-500">
                                        <Users className="h-3 w-3" />
                                        PLAYERS
                                    </div>
                                    {results.players.slice(0, 3).map((player) => (
                                        <div key={player.id} className="rounded px-3 py-2 hover:bg-gray-50">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <div className="font-medium">{player.name}</div>
                                                    <div className="text-xs text-gray-500">{player.position}</div>
                                                </div>
                                                <Badge variant="outline" className="text-xs">
                                                    {player.position}
                                                </Badge>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* View All Results */}
                            <div className="border-t pt-2">
                                <Link
                                    href={`/search?q=${encodeURIComponent(query)}`}
                                    className="block rounded px-3 py-2 text-center text-sm font-medium text-blue-600 hover:bg-blue-50"
                                    onClick={() => setIsOpen(false)}
                                >
                                    View all {getTotalResults()} results
                                </Link>
                            </div>
                        </div>
                    ) : (
                        <div className="p-4 text-center text-sm text-gray-500">No results found for "{query}"</div>
                    )}
                </div>
            )}
        </div>
    );
}
