/* eslint-disable @typescript-eslint/no-explicit-any */
import PeerCard from '@/components/features/peer-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Search, Target } from 'lucide-react';
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

export default function SearchIndex({ query,  results }: SearchProps) {
    const [searchQuery, setSearchQuery] = useState(query);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/search', { q: searchQuery });
    };

    const getTotalResults = () => {
        return (results.peers?.length || 0);
    };

    return (
        <AppLayout title="Search">
            <Head title="Search" />
            <div className="min-h-screen p-4">
                <div className="mx-auto max-w-4xl space-y-6">
                    {/* Search Form */}

                    <form onSubmit={handleSearch} className="space-y-4">
                        <div className="flex gap-4">
                            <div className="flex-1">
                                <Input
                                    type="text"
                                    placeholder="Search peers..."
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
                    </form>

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
                                            <PeerCard peer={peer} key={peer.peer_id} />
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* No Results */}
                            {getTotalResults() === 0 && (
                                <div className="rounded border-2 border-dashed border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
                                    <CardContent className="p-4 text-center">
                                        <div className="mb-3 inline-flex rounded-full bg-gray-200 p-4 dark:bg-gray-700">
                                            <Search className="h-8 w-8 text-gray-400" />
                                        </div>
                                        <h3 className="text-lg font-semibold text-gray-700 dark:text-gray-300">No results found</h3>
                                        <p className="text-xs text-gray-600 dark:text-gray-400">Try adjusting your search terms or search type!</p>
                                    </CardContent>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Empty State */}
                    {!query && (
                        <div className="rounded border-2 border-dashed border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
                            <CardContent className="p-4 text-center">
                                <div className="mb-3 inline-flex rounded-full bg-gray-200 p-4 dark:bg-gray-700">
                                    <Search className="h-8 w-8 text-gray-400" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-700 dark:text-gray-300">Start searching</h3>
                                <p className="text-xs text-gray-600 dark:text-gray-400">Enter a search term above to find peers!</p>
                            </CardContent>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
