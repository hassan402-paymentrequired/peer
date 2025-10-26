import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Search, Target, Trophy, User, Users } from 'lucide-react';
import { useState } from 'react';

interface SearchProps {
    query: string;
    type: string;
    results: {
        peers: any[];
        tournaments: any[];
        users: any[];
        players: any[];
    };
}

export default function SearchIndex({ query, type, results }: SearchProps) {
    const [searchQuery, setSearchQuery] = useState(query);
    const [searchType, setSearchType] = useState(type);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/search', { q: searchQuery, type: searchType });
    };

    const getTotalResults = () => {
        return (results.peers?.length || 0) + (results.tournaments?.length || 0) + (results.users?.length || 0) + (results.players?.length || 0);
    };

    return (
        <AppLayout title="Search">
            <Head title="Search" />
            <div className="min-h-screen p-4">
                <div className="mx-auto max-w-4xl space-y-6">
                    {/* Search Header */}
                    <div className="text-center">
                        <div className="mb-4 inline-flex rounded-full bg-blue-100 p-4">
                            <Search className="h-8 w-8 text-blue-600" />
                        </div>
                        <h1 className="mb-2 text-2xl font-bold text-gray-900">Search</h1>
                        <p className="text-gray-600">Find peers, tournaments, users, and players</p>
                    </div>

                    {/* Search Form */}
                    <Card>
                        <CardContent className="p-6">
                            <form onSubmit={handleSearch} className="space-y-4">
                                <div className="flex gap-4">
                                    <div className="flex-1">
                                        <Input
                                            type="text"
                                            placeholder="Search for anything..."
                                            value={searchQuery}
                                            onChange={(e) => setSearchQuery(e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <Button type="submit" className="px-6">
                                        <Search className="mr-2 h-4 w-4" />
                                        Search
                                    </Button>
                                </div>

                                {/* Search Type Filter */}
                                <div className="flex flex-wrap gap-2">
                                    {[
                                        { value: 'all', label: 'All' },
                                        { value: 'peers', label: 'Peers' },
                                        { value: 'tournaments', label: 'Tournaments' },
                                    ].map((option) => (
                                        <Button
                                            key={option.value}
                                            type="button"
                                            variant={searchType === option.value ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setSearchType(option.value)}
                                        >
                                            {option.label}
                                        </Button>
                                    ))}
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Search Results */}
                    {query && (
                        <div className="space-y-6">
                            <div className="flex items-center justify-between">
                                <h2 className="text-xl font-semibold">Search Results for "{query}"</h2>
                                <Badge variant="outline">{getTotalResults()} results</Badge>
                            </div>

                            {/* Peers Results */}
                            {results.peers && results.peers.length > 0 && (
                                <div className="space-y-3">
                                    <h3 className="flex items-center gap-2 text-lg font-medium">
                                        <Target className="h-5 w-5" />
                                        Peers ({results.peers.length})
                                    </h3>
                                    <div className="grid gap-3">
                                        {results.peers.map((peer) => (
                                            <Card key={peer.id} className="transition-shadow hover:shadow-md">
                                                <CardContent className="p-4">
                                                    <div className="flex items-center justify-between">
                                                        <div>
                                                            <h4 className="font-semibold capitalize">{peer.name}</h4>
                                                            <p className="text-sm text-gray-600">
                                                                by @{peer.created_by?.name} • {peer.users_count} participants
                                                            </p>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            <Badge>₦{peer.amount}</Badge>
                                                            <Link href={`/peers/${peer.peer_id}`}>
                                                                <Button size="sm">View</Button>
                                                            </Link>
                                                        </div>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Tournaments Results */}
                            {results.tournaments && results.tournaments.length > 0 && (
                                <div className="space-y-3">
                                    <h3 className="flex items-center gap-2 text-lg font-medium">
                                        <Trophy className="h-5 w-5" />
                                        Tournaments ({results.tournaments.length})
                                    </h3>
                                    <div className="grid gap-3">
                                        {results.tournaments.map((tournament) => (
                                            <Card key={tournament.id} className="transition-shadow hover:shadow-md">
                                                <CardContent className="p-4">
                                                    <div className="flex items-center justify-between">
                                                        <div>
                                                            <h4 className="font-semibold capitalize">{tournament.name}</h4>
                                                            <p className="text-sm text-gray-600">
                                                                {tournament.users_count} participants • Status: {tournament.status}
                                                            </p>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            <Badge>₦{tournament.amount}</Badge>
                                                            <Link href="/tournament">
                                                                <Button size="sm">View</Button>
                                                            </Link>
                                                        </div>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Users Results */}
                            {results.users && results.users.length > 0 && (
                                <div className="space-y-3">
                                    <h3 className="flex items-center gap-2 text-lg font-medium">
                                        <User className="h-5 w-5" />
                                        Users ({results.users.length})
                                    </h3>
                                    <div className="grid gap-3">
                                        {results.users.map((user) => (
                                            <Card key={user.id} className="transition-shadow hover:shadow-md">
                                                <CardContent className="p-4">
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex items-center gap-3">
                                                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                                                                <span className="font-semibold text-blue-600">
                                                                    {user.name.substring(0, 2).toUpperCase()}
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <h4 className="font-semibold">@{user.name}</h4>
                                                                <p className="text-sm text-gray-600">{user.email}</p>
                                                            </div>
                                                        </div>
                                                        <Button size="sm" variant="outline">
                                                            View Profile
                                                        </Button>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Players Results */}
                            {results.players && results.players.length > 0 && (
                                <div className="space-y-3">
                                    <h3 className="flex items-center gap-2 text-lg font-medium">
                                        <Users className="h-5 w-5" />
                                        Players ({results.players.length})
                                    </h3>
                                    <div className="grid gap-3">
                                        {results.players.map((player) => (
                                            <Card key={player.id} className="transition-shadow hover:shadow-md">
                                                <CardContent className="p-4">
                                                    <div className="flex items-center justify-between">
                                                        <div>
                                                            <h4 className="font-semibold">{player.name}</h4>
                                                            <p className="text-sm text-gray-600">
                                                                Position: {player.position} • Team: {player.team?.name || 'N/A'}
                                                            </p>
                                                        </div>
                                                        <Badge variant="outline">{player.position}</Badge>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* No Results */}
                            {getTotalResults() === 0 && (
                                <Card>
                                    <CardContent className="p-8 text-center">
                                        <div className="mb-4 inline-flex rounded-full bg-gray-100 p-4">
                                            <Search className="h-8 w-8 text-gray-400" />
                                        </div>
                                        <h3 className="mb-2 text-lg font-semibold">No results found</h3>
                                        <p className="text-gray-600">Try adjusting your search terms or search type</p>
                                    </CardContent>
                                </Card>
                            )}
                        </div>
                    )}

                    {/* Empty State */}
                    {!query && (
                        <Card>
                            <CardContent className="p-8 text-center">
                                <div className="mb-4 inline-flex rounded-full bg-blue-100 p-4">
                                    <Search className="h-8 w-8 text-blue-600" />
                                </div>
                                <h3 className="mb-2 text-lg font-semibold">Start searching</h3>
                                <p className="text-gray-600">Enter a search term above to find peers, tournaments, users, and players</p>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
