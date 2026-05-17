'use client';

import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Info, Share2, Users, Gift } from 'lucide-react';
import { toast } from 'react-toastify';

export default function ReferralsPage() {
    const [referralCode, setReferralCode] = useState<string | null>(null);
    const [stats, setStats] = useState({ totalReferrals: 0, pendingRewards: 0 });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchReferralData = async () => {
            try {
                const res = await fetch('/api/referrals');
                if (!res.ok) throw new Error('Failed to fetch referral data');
                const data = await res.json();
                setReferralCode(data.code || 'YOUR-CODE-HERE');
                setStats({
                    totalReferrals: data.stats?.total || 0,
                    pendingRewards: data.stats?.pending || 0
                });
            } catch (e: any) {
                toast.error(e.message || 'Error loading referrals');
                // Mock fallback for UI rendering if backend is unavailable
                setReferralCode('FWBER-2024-XJZ');
            } finally {
                setLoading(false);
            }
        };
        fetchReferralData();
    }, []);

    const copyToClipboard = () => {
        if (referralCode) {
            navigator.clipboard.writeText(`https://fwber.me/join?ref=${referralCode}`);
            toast.success('Referral link copied to clipboard!');
        }
    };

    return (
        <div className="container mx-auto p-4 md:p-8 max-w-4xl space-y-6">
            <h1 className="text-3xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <Users className="w-8 h-8 text-primary" />
                Refer a Friend
            </h1>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Card className="bg-white shadow-sm border border-gray-100">
                    <CardHeader>
                        <CardTitle className="text-xl flex items-center gap-2">
                            Your Invite Link
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Info className="w-4 h-4 text-gray-400 cursor-help" />
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>Share this link with friends. When they join and verify, you both earn tokens.</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </CardTitle>
                        <CardDescription>
                            Grow the community and earn rewards.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {loading ? (
                             <div className="animate-pulse h-12 bg-gray-200 rounded w-full"></div>
                        ) : (
                            <div className="flex flex-col space-y-3">
                                <div className="p-4 bg-gray-50 rounded-md border border-gray-200 font-mono text-center text-lg break-all">
                                    https://fwber.me/join?ref={referralCode}
                                </div>
                                <Button onClick={copyToClipboard} className="w-full flex items-center justify-center gap-2">
                                    <Share2 className="w-4 h-4" /> Copy Link
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="bg-white shadow-sm border border-gray-100">
                    <CardHeader>
                        <CardTitle className="text-xl">Your Rewards</CardTitle>
                        <CardDescription>Track your successful referrals</CardDescription>
                    </CardHeader>
                    <CardContent>
                         {loading ? (
                             <div className="space-y-4">
                                <div className="animate-pulse h-8 bg-gray-200 rounded w-1/2"></div>
                                <div className="animate-pulse h-8 bg-gray-200 rounded w-1/2"></div>
                             </div>
                        ) : (
                            <div className="space-y-6">
                                <div className="flex items-center justify-between p-4 bg-green-50 text-green-800 rounded-lg border border-green-100">
                                    <div className="flex items-center gap-3">
                                        <Users className="w-6 h-6" />
                                        <span className="font-semibold">Friends Joined</span>
                                    </div>
                                    <span className="text-2xl font-bold">{stats.totalReferrals}</span>
                                </div>
                                <div className="flex items-center justify-between p-4 bg-amber-50 text-amber-800 rounded-lg border border-amber-100">
                                    <div className="flex items-center gap-3">
                                        <Gift className="w-6 h-6" />
                                        <span className="font-semibold">Tokens Earned</span>
                                    </div>
                                    <span className="text-2xl font-bold">{stats.totalReferrals * 50}</span>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
