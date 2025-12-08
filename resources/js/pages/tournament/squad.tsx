import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

const SquadField = ({ user }: { user: any }) => {
    // 5 positions layout - Adjusted for better spacing on a full mobile screen
    const positions = [
        { top: '10%', left: '50%', transform: 'translate(-50%, 0)' }, // Top Center (Striker)
        { top: '35%', left: '10%' }, // Mid Left
        { top: '35%', left: '90%', transform: 'translate(-100%, 0)' }, // Mid Right
        { top: '65%', left: '20%' }, // Def Left
        { top: '65%', left: '80%', transform: 'translate(-100%, 0)' }, // Def Right
    ];

    return (
        <AppLayout title={`${user?.username}'s Squad - ${user?.total_point}`}>
            <Head title={`${user?.username}'s Squad`} />

            <div className="flex h-screen flex-col bg-gray-900">
                {/* Header */}

                {/* Main Content - No Scrollbar */}
                <div className="relative h-full w-full overflow-hidden overflow-y-auto bg-gradient-to-b from-emerald-600 to-emerald-700">
                    {/* Field Background with Patterns */}
                    <div className="absolute inset-0 h-full w-full">
                        {/* Grass Striping - Horizontal bands */}
                        <div
                            className="absolute inset-0 opacity-15"
                            style={{
                                backgroundImage:
                                    'repeating-linear-gradient(90deg, transparent, transparent 39px, rgba(0,0,0,0.1) 40px, transparent 41px, transparent 79px, rgba(0,0,0,0.15) 80px)',
                            }}
                        ></div>

                        {/* Perimeter/Touchline */}
                        <div className="absolute inset-4 border-4 border-white/50"></div>

                        {/* Halfway Line */}
                        <div className="absolute left-4 right-4 top-1/2 h-1 -translate-y-1/2 bg-white/50"></div>

                        {/* Center Circle */}
                        <div className="absolute left-1/2 top-1/2 h-32 w-32 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white/50"></div>
                        <div className="absolute left-1/2 top-1/2 h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/70"></div>

                        {/* Top Penalty Area (18-yard box) */}
                        <div className="absolute left-1/2 top-4 h-24 w-56 -translate-x-1/2 border-2 border-t-0 border-white/50"></div>

                        {/* Top Goal Area (6-yard box) */}
                        <div className="absolute left-1/2 top-4 h-12 w-32 -translate-x-1/2 border-2 border-t-0 border-white/50"></div>

                        {/* Top Penalty Spot */}
                        <div className="absolute left-1/2 top-20 h-2 w-2 -translate-x-1/2 rounded-full bg-white/70"></div>

                        {/* Top Penalty Arc */}
                        <div
                            className="absolute left-1/2 top-28 h-16 w-16 -translate-x-1/2 rounded-full border-2 border-white/50"
                            style={{ clipPath: 'polygon(0 100%, 100% 100%, 100% 0, 0 0)' }}
                        ></div>

                        {/* Bottom Penalty Area (18-yard box) */}
                        <div className="absolute bottom-4 left-1/2 h-24 w-56 -translate-x-1/2 border-2 border-b-0 border-white/50"></div>

                        {/* Bottom Goal Area (6-yard box) */}
                        <div className="absolute bottom-4 left-1/2 h-12 w-32 -translate-x-1/2 border-2 border-b-0 border-white/50"></div>

                        {/* Bottom Penalty Spot */}
                        <div className="absolute bottom-20 left-1/2 h-2 w-2 -translate-x-1/2 rounded-full bg-white/70"></div>

                        {/* Bottom Penalty Arc */}
                        <div
                            className="absolute bottom-28 left-1/2 h-16 w-16 -translate-x-1/2 rounded-full border-2 border-white/50"
                            style={{ clipPath: 'polygon(0 0, 100% 0, 100% 100%, 0 100%)' }}
                        ></div>

                        {/* Corner Arcs */}
                        <div className="absolute left-4 top-4 h-6 w-6 rounded-br-full border-b-2 border-r-2 border-white/50"></div>
                        <div className="absolute right-4 top-4 h-6 w-6 rounded-bl-full border-b-2 border-l-2 border-white/50"></div>
                        <div className="absolute bottom-4 left-4 h-6 w-6 rounded-tr-full border-r-2 border-t-2 border-white/50"></div>
                        <div className="absolute bottom-4 right-4 h-6 w-6 rounded-tl-full border-l-2 border-t-2 border-white/50"></div>

                        {/* Goals */}
                        <div className="absolute left-1/2 top-4 h-1 w-20 -translate-x-1/2 -translate-y-full bg-white/60"></div>
                        <div className="absolute bottom-4 left-1/2 h-1 w-20 -translate-x-1/2 translate-y-full bg-white/60"></div>
                    </div>

                    {/* Players Layer */}
                    <div className="absolute inset-0 z-10">
                        {user.squads.map((squad, index) => {
                            const style = positions[index] || { top: '50%', left: '50%' };
                            const mainPlayer = squad.main_player;
                            const subPlayer = squad.sub_player;

                            return (
                                <div
                                    key={squad.id}
                                    className="absolute flex w-32 flex-col items-center"
                                    style={style}
                                >
                                    {/* Player Card Container */}
                                    <div className="relative flex flex-col items-center group">

                                        {/* Main Player Avatar */}
                                        <div className="relative mb-1">
                                            <div className="h-14 w-14 overflow-hidden rounded-full border-2 border-white bg-gray-800 shadow-xl ring-2 ring-black/20">
                                                {mainPlayer?.image ? (
                                                    <img src={mainPlayer.image} alt={mainPlayer.name} className="h-full w-full object-cover" />
                                                ) : (
                                                    <div className="flex h-full w-full items-center justify-center bg-gray-700 font-bold text-white">
                                                        {mainPlayer?.name ? mainPlayer.name.charAt(0) : '?'}
                                                    </div>
                                                )}
                                            </div>
                                            {/* Points Badge */}
                                            <div className="absolute -right-1 -top-1 flex h-auto min-w-[20px] items-center justify-center rounded-full bg-blue-600 px-1 py-0.5 text-[10px] font-bold text-white shadow-sm border border-white">
                                                {mainPlayer?.statistics?.total_point || 0}
                                            </div>
                                        </div>

                                        {/* Main Player Name & Points */}
                                        <div className="mb-1 flex max-w-full flex-col items-center rounded-lg bg-gray-900/90 px-2 py-1 text-center backdrop-blur-sm shadow-lg border border-white/10">
                                            <span className="max-w-[100px] truncate text-xs font-bold text-white">
                                                {mainPlayer?.name?.split(' ').slice(-1)[0] || 'Unknown'}
                                            </span>
                                            <span className="text-[10px] font-medium text-emerald-400">
                                                {mainPlayer?.statistics?.total_points || 0} pts
                                            </span>
                                        </div>

                                        {/* Sub Player Section - Connected visually */}
                                        <div className="mt-1 flex items-center gap-1.5 rounded-full bg-white/90 px-2 py-0.5 pr-3 shadow-md backdrop-blur-sm transition-transform hover:scale-105">
                                            <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-200 text-[9px] font-bold text-gray-700 ring-1 ring-gray-300">
                                                Sub
                                            </div>
                                            <div className="flex flex-col leading-none">
                                                <span className="max-w-[80px] truncate text-[9px] font-bold text-gray-800">
                                                    {subPlayer?.name?.split(' ').slice(-1)[0] || 'None'}
                                                </span>
                                                <span className="text-[8px] font-semibold text-gray-500">
                                                    {subPlayer?.statistics?.total_points || 0} pts
                                                </span>
                                            </div>
                                            {/* Swap Icon indicating substitution possibility (visual only for now) */}
                                            <svg className="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                            </svg>
                                        </div>

                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>



            </div>
        </AppLayout>
    );
};

export default SquadField;
