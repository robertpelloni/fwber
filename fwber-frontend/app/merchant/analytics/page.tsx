'use client';

import { useState, useEffect, useCallback } from 'react';
import { useAuth } from '@/lib/auth-context';
import { api } from '@/lib/api/client';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { BarChart3, TrendingUp, Users, Zap, ArrowLeft, RefreshCcw, DollarSign } from 'lucide-react';
import Link from 'next/link';

interface AnalyticsData {
    kpis: {
        kFactor: number;
        totalReach: number;
        conversionRate: number;
        totalRevenue: number;
        revenueChange: number;
    };
    retention: Array<{ label: string; value: number; previousValue: number }>;
    promotions: Array<{
        id: number;
        title: string;
        views: number;
        clicks: number;
        redemptions: number;
        revenue: number;
        conversionRate: number;
    }>;
}

export default function MerchantAnalyticsPage() {
    const { token } = useAuth();
    const [data, setData] = useState<AnalyticsData | null>(null);
    const [loading, setLoading] = useState(true);

    const fetchAnalytics = useCallback(async () => {
        if (!token) return;
        try {
            setLoading(true);
            const response = await api.get<AnalyticsData>('/merchant-portal/analytics', {
                headers: { Authorization: `Bearer ${token}` }
            });
            setData(response);
        } catch (error) {
            console.error('Failed to fetch merchant analytics:', error);
        } finally {
            setLoading(false);
        }
    }, [token]);

    useEffect(() => {
        fetchAnalytics();
    }, [fetchAnalytics]);

    return (
        <div className="max-w-6xl mx-auto space-y-8 pb-24">
            {/* Header */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                    <Link href="/merchant/dashboard">
                        <Button variant="ghost" size="icon" className="rounded-full">
                            <ArrowLeft className="w-5 h-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-black italic uppercase tracking-tighter text-zinc-900 dark:text-white">
                            Business Intelligence
                        </h1>
                        <p className="text-xs font-bold text-amber-600 uppercase tracking-widest flex items-center gap-1">
                            <BarChart3 className="w-3 h-3" /> Performance & Foot Traffic
                        </p>
                    </div>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={fetchAnalytics} disabled={loading}>
                        <RefreshCcw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                        Refresh Data
                    </Button>
                    <Badge variant="secondary" className="bg-zinc-100 dark:bg-zinc-800 font-bold uppercase tracking-widest text-[10px]">
                        Period: Last 30 Days
                    </Badge>
                </div>
            </div>

            {loading ? (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {[1, 2, 3].map((i) => <Skeleton key={i} className="h-48 rounded-2xl" />)}
                </div>
            ) : data ? (
                <>
                    {/* Key Stats */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <Card className="border-zinc-200 dark:border-zinc-800 shadow-lg">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-xs font-black uppercase tracking-widest text-zinc-500 flex items-center gap-2">
                                    <Users className="w-3 h-3" /> Area Traffic
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-4xl font-black tracking-tighter text-zinc-900 dark:text-white italic">
                                    {data.kpis.totalReach}
                                </div>
                                <p className="text-[10px] text-zinc-400 mt-1 font-bold uppercase tracking-widest">
                                    Unique Discoveries in Range
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="border-zinc-200 dark:border-zinc-800 shadow-lg">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-xs font-black uppercase tracking-widest text-zinc-500 flex items-center gap-2">
                                    <Zap className="w-3 h-3 text-amber-500 fill-current" /> Vibe Conversion
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-4xl font-black tracking-tighter text-amber-600 italic">
                                    {Math.round(data.kpis.conversionRate)}%
                                </div>
                                <p className="text-[10px] text-zinc-400 mt-1 font-bold uppercase tracking-widest">
                                    Redemption rate on merchant traffic
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="border-zinc-200 dark:border-zinc-800 shadow-lg">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-xs font-black uppercase tracking-widest text-zinc-500 flex items-center gap-2">
                                    <DollarSign className="w-3 h-3 text-blue-500" /> Revenue
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-4xl font-black tracking-tighter text-blue-600 italic uppercase">
                                    ${data.kpis.totalRevenue.toFixed(0)}
                                </div>
                                <p className="text-[10px] text-zinc-400 mt-1 font-bold uppercase tracking-widest">
                                    {data.kpis.revenueChange >= 0 ? '+' : ''}{data.kpis.revenueChange}% vs previous period
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Performance Breakdown */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <Card className="border-zinc-200 dark:border-zinc-800">
                            <CardHeader>
                                <CardTitle className="text-lg">Promotion Performance</CardTitle>
                                <CardDescription>Tracking real-world redemptions of your FWB-backed deals.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="p-6 rounded-2xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-100 dark:border-zinc-900 flex items-center justify-between">
                                    <div className="space-y-1">
                                        <span className="text-[10px] font-black uppercase tracking-widest text-zinc-500">Total Claims</span>
                                        <div className="text-3xl font-black text-zinc-900 dark:text-white tracking-tighter">
                                            {data.promotions.reduce((sum, promotion) => sum + promotion.redemptions, 0)}
                                        </div>
                                    </div>
                                    <div className="h-12 w-32 bg-amber-500/10 rounded-lg flex items-center justify-center border border-amber-500/20">
                                        <span className="text-amber-600 font-black text-xs">K-Factor {data.kpis.kFactor.toFixed(2)}</span>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <div className="flex justify-between text-[10px] font-bold uppercase tracking-widest text-zinc-400">
                                        <span>Active Reach</span>
                                        <span>{data.kpis.totalReach} users</span>
                                    </div>
                                    <div className="h-2 w-full bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                                        <div className="h-full bg-amber-500 rounded-full" style={{width: `${Math.min(100, data.kpis.conversionRate)}%`}} />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-zinc-200 dark:border-zinc-800 bg-zinc-950 text-white overflow-hidden relative">
                            <div className="absolute top-0 right-0 w-64 h-64 bg-blue-600/10 blur-[100px] rounded-full -mr-32 -mt-32" />
                            <CardHeader>
                                <CardTitle className="text-lg text-white">Retention Snapshot</CardTitle>
                                <CardDescription className="text-zinc-500">Repeat engagement across recent merchant cohorts.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-8 relative z-10">
                                <div className="flex items-center gap-4">
                                    <div className="p-4 rounded-2xl bg-white/5 border border-white/10">
                                        <TrendingUp className="w-8 h-8 text-blue-400" />
                                    </div>
                                    <div>
                                        <span className="text-[10px] font-black uppercase tracking-widest text-zinc-500">Best Cohort</span>
                                        <div className="text-2xl font-black text-white tracking-tighter">
                                            {data.retention[0]?.label || 'Day 1'}: {data.retention[0]?.value || 0}%
                                        </div>
                                    </div>
                                </div>
                                <div className="space-y-3">
                                    {data.retention.map((entry) => (
                                        <div key={entry.label} className="flex items-center justify-between text-sm">
                                            <span className="text-zinc-400">{entry.label}</span>
                                            <span className="font-semibold text-white">{entry.value}%</span>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </>
            ) : (
                <Card className="border-dashed border-2 py-20">
                    <CardContent className="flex flex-col items-center justify-center text-center">
                        <BarChart3 className="w-12 h-12 text-zinc-300 mb-4" />
                        <h3 className="text-lg font-bold">No data yet</h3>
                        <p className="text-zinc-500 max-w-sm">
                            Broadcast your first Vibe to start gathering local intelligence.
                        </p>
                    </CardContent>
                </Card>
            )}
        </div>
    );
}
