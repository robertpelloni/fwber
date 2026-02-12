'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/lib/auth-context';
import { apiClient } from '@/lib/api/client';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { 
  ArrowLeft, 
  TrendingUp, 
  Users, 
  Target, 
  Coins,
  Eye,
  MousePointer,
  CheckCircle,
  Calendar,
  ChevronDown
} from 'lucide-react';
import Link from 'next/link';

interface KPIData {
  kFactor: number;
  totalReach: number;
  conversionRate: number;
  totalRevenue: number;
  revenueChange: number;
}

interface RetentionData {
  label: string;
  value: number;
  previousValue: number;
}

interface FunnelStep {
  name: string;
  value: number;
  percentage: number;
  icon: React.ReactNode;
}

interface PromotionPerformance {
  id: string;
  title: string;
  views: number;
  clicks: number;
  redemptions: number;
  revenue: number;
  conversionRate: number;
}

interface AnalyticsResponse {
  kpis: KPIData;
  retention: RetentionData[];
  promotions: PromotionPerformance[];
}

function AnalyticsSkeleton() {
  return (
    <div className="space-y-6">
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {[...Array(4)].map((_, i) => (
          <Card key={i}>
            <CardContent className="p-4">
              <Skeleton className="h-4 w-24 mb-2" />
              <Skeleton className="h-8 w-16" />
            </CardContent>
          </Card>
        ))}
      </div>
      <Card>
        <CardContent className="p-6">
          <Skeleton className="h-64 w-full" />
        </CardContent>
      </Card>
    </div>
  );
}

function KPICard({ 
  title, 
  value, 
  suffix = '', 
  change, 
  icon 
}: { 
  title: string; 
  value: string | number; 
  suffix?: string; 
  change?: number;
  icon: React.ReactNode;
}) {
  return (
    <Card>
      <CardContent className="p-4">
        <div className="flex items-center justify-between mb-2">
          <span className="text-sm text-muted-foreground">{title}</span>
          <span className="text-amber-600">{icon}</span>
        </div>
        <div className="flex items-baseline gap-1">
          <span className="text-2xl font-bold">{value}</span>
          <span className="text-sm text-muted-foreground">{suffix}</span>
        </div>
        {change !== undefined && (
          <div className={`text-xs mt-1 ${change >= 0 ? 'text-green-600' : 'text-red-600'}`}>
            {change >= 0 ? '+' : ''}{change}% vs last period
          </div>
        )}
      </CardContent>
    </Card>
  );
}

function KFactorVisualization({ kFactor }: { kFactor: number }) {
  const generations = [1, Math.round(kFactor * 10) / 10, Math.round(kFactor * kFactor * 10) / 10];
  
  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <TrendingUp className="h-5 w-5 text-amber-600" />
          K-Factor (Viral Coefficient)
        </CardTitle>
        <CardDescription>
          Each user brings an average of {kFactor.toFixed(2)} new users
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className="flex flex-col items-center py-6">
          {/* K-Factor Score */}
          <div className="text-5xl font-bold text-amber-600 mb-6">
            {kFactor.toFixed(2)}
          </div>
          
          {/* Visual spread representation */}
          <div className="flex items-center justify-center gap-4 md:gap-8 w-full">
            {generations.map((gen, i) => (
              <div key={i} className="flex flex-col items-center">
                <div className="text-sm text-muted-foreground mb-2">
                  Gen {i + 1}
                </div>
                <div className="flex flex-wrap justify-center gap-1" style={{ maxWidth: '80px' }}>
                  {[...Array(Math.ceil(gen))].map((_, j) => (
                    <div
                      key={j}
                      className="w-3 h-3 rounded-full bg-amber-500"
                      style={{ opacity: j < Math.floor(gen) ? 1 : gen % 1 }}
                    />
                  ))}
                </div>
                <div className="text-xs text-muted-foreground mt-1">
                  {gen.toFixed(1)} users
                </div>
              </div>
            ))}
          </div>
          
          {/* Interpretation */}
          <div className="mt-6 p-4 bg-amber-50 dark:bg-amber-950/20 rounded-lg text-center">
            <p className="text-sm">
              {kFactor >= 1 ? (
                <span className="text-green-600 font-medium">
                  Viral! Your promotions are spreading organically.
                </span>
              ) : (
                <span className="text-amber-600">
                  Growing steadily. Aim for K &gt; 1 for viral growth.
                </span>
              )}
            </p>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

function RetentionChart({ data }: { data: RetentionData[] }) {
  const maxValue = 100;
  
  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Users className="h-5 w-5 text-amber-600" />
          User Retention
        </CardTitle>
        <CardDescription>
          How many users continue engaging with your promotions over time
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {data.map((item, i) => (
            <div key={i} className="space-y-1">
              <div className="flex justify-between text-sm">
                <span>{item.label}</span>
                <span className="font-medium">{item.value}%</span>
              </div>
              <div className="relative h-6 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                {/* Previous period (lighter) */}
                <div
                  className="absolute inset-y-0 left-0 bg-amber-200 dark:bg-amber-900 transition-all duration-500"
                  style={{ width: `${(item.previousValue / maxValue) * 100}%` }}
                />
                {/* Current period */}
                <div
                  className="absolute inset-y-0 left-0 bg-amber-500 transition-all duration-500"
                  style={{ width: `${(item.value / maxValue) * 100}%` }}
                />
              </div>
              <div className="flex justify-between text-xs text-muted-foreground">
                <span>Previous: {item.previousValue}%</span>
                <span className={item.value >= item.previousValue ? 'text-green-600' : 'text-red-600'}>
                  {item.value >= item.previousValue ? '+' : ''}{item.value - item.previousValue}%
                </span>
              </div>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}

function ConversionFunnel({ promotions }: { promotions: PromotionPerformance[] }) {
  const totals = promotions.reduce(
    (acc, p) => ({
      views: acc.views + p.views,
      clicks: acc.clicks + p.clicks,
      redemptions: acc.redemptions + p.redemptions,
    }),
    { views: 0, clicks: 0, redemptions: 0 }
  );

  const steps: FunnelStep[] = [
    { 
      name: 'Views', 
      value: totals.views, 
      percentage: 100,
      icon: <Eye className="h-5 w-5" />
    },
    { 
      name: 'Clicks', 
      value: totals.clicks, 
      percentage: (totals.clicks / (totals.views || 1)) * 100,
      icon: <MousePointer className="h-5 w-5" />
    },
    { 
      name: 'Redemptions', 
      value: totals.redemptions, 
      percentage: (totals.redemptions / (totals.views || 1)) * 100,
      icon: <CheckCircle className="h-5 w-5" />
    },
  ];

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Target className="h-5 w-5 text-amber-600" />
          Conversion Funnel
        </CardTitle>
        <CardDescription>
          Track how users progress from viewing to redeeming promotions
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {steps.map((step, i) => (
            <div key={i} className="relative">
              <div 
                className="flex items-center justify-between p-4 rounded-lg bg-amber-500 text-white transition-all"
                style={{ 
                  width: `${Math.max(step.percentage, 30)}%`,
                  marginLeft: `${(100 - Math.max(step.percentage, 30)) / 2}%`
                }}
              >
                <div className="flex items-center gap-2">
                  {step.icon}
                  <span className="font-medium">{step.name}</span>
                </div>
                <span className="font-bold">{step.value.toLocaleString()}</span>
              </div>
              {i < steps.length - 1 && (
                <div className="flex justify-center py-1">
                  <ChevronDown className="h-5 w-5 text-amber-400" />
                </div>
              )}
            </div>
          ))}
        </div>
        
        {/* Conversion rates */}
        <div className="grid grid-cols-2 gap-4 mt-6 pt-4 border-t">
          <div className="text-center">
            <div className="text-2xl font-bold text-amber-600">
              {((totals.clicks / (totals.views || 1)) * 100).toFixed(1)}%
            </div>
            <div className="text-sm text-muted-foreground">Click-through Rate</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-amber-600">
              {((totals.redemptions / (totals.clicks || 1)) * 100).toFixed(1)}%
            </div>
            <div className="text-sm text-muted-foreground">Redemption Rate</div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

function PromotionPerformanceTable({ promotions }: { promotions: PromotionPerformance[] }) {
  const sortedPromotions = [...promotions].sort((a, b) => b.revenue - a.revenue);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Promotion Performance</CardTitle>
        <CardDescription>
          Compare metrics across all your active promotions
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b text-left">
                <th className="pb-3 font-medium">Promotion</th>
                <th className="pb-3 font-medium text-right">Views</th>
                <th className="pb-3 font-medium text-right">Clicks</th>
                <th className="pb-3 font-medium text-right">Redemptions</th>
                <th className="pb-3 font-medium text-right">Conv. Rate</th>
                <th className="pb-3 font-medium text-right">Revenue</th>
              </tr>
            </thead>
            <tbody>
              {sortedPromotions.length === 0 ? (
                <tr>
                  <td colSpan={6} className="py-4 text-center text-muted-foreground">
                    No promotions found
                  </td>
                </tr>
              ) : (
                sortedPromotions.map((promo, i) => (
                  <tr key={promo.id} className="border-b last:border-0">
                    <td className="py-3">
                      <div className="flex items-center gap-2">
                        <div className="w-2 h-2 rounded-full bg-amber-500" />
                        <span className="font-medium truncate max-w-[200px]">{promo.title}</span>
                      </div>
                    </td>
                    <td className="py-3 text-right text-muted-foreground">
                      {promo.views.toLocaleString()}
                    </td>
                    <td className="py-3 text-right text-muted-foreground">
                      {promo.clicks.toLocaleString()}
                    </td>
                    <td className="py-3 text-right text-muted-foreground">
                      {promo.redemptions.toLocaleString()}
                    </td>
                    <td className="py-3 text-right">
                      <span className={`px-2 py-1 rounded text-xs font-medium ${
                        promo.conversionRate >= 20
                          ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                          : promo.conversionRate >= 15
                          ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                          : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400'
                      }`}>
                        {promo.conversionRate.toFixed(1)}%
                      </span>
                    </td>
                    <td className="py-3 text-right font-medium">
                      <span className="text-amber-600">{promo.revenue.toLocaleString()}</span>
                      <span className="text-xs text-muted-foreground ml-1">tokens</span>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  );
}

export default function MerchantAnalyticsPage() {
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [kpis, setKpis] = useState<KPIData | null>(null);
  const [retention, setRetention] = useState<RetentionData[]>([]);
  const [promotions, setPromotions] = useState<PromotionPerformance[]>([]);

  useEffect(() => {
    const loadAnalytics = async () => {
      setLoading(true);
      try {
        const response = await apiClient.get<AnalyticsResponse>('/merchant-portal/analytics');
        if (response.data) {
          setKpis(response.data.kpis);
          setRetention(response.data.retention);
          setPromotions(response.data.promotions);
        }
      } catch (error) {
        console.error('Failed to load analytics', error);
      } finally {
        setLoading(false);
      }
    };

    if (token) {
      loadAnalytics();
    }
  }, [token]);

  if (!token) {
    return (
      <div className="p-6 text-center">
        <p className="text-muted-foreground">Please log in to view analytics.</p>
      </div>
    );
  }

  return (
    <div className="p-4 md:p-6 max-w-7xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <Link href="/merchant/dashboard">
            <Button variant="ghost" size="icon">
              <ArrowLeft className="h-5 w-5" />
            </Button>
          </Link>
          <div>
            <h1 className="text-2xl font-bold">Analytics</h1>
            <p className="text-muted-foreground">Track your promotion performance</p>
          </div>
        </div>
        
        {/* Date range selector (mock) */}
        <Button variant="outline" className="gap-2">
          <Calendar className="h-4 w-4" />
          Last 30 days
          <ChevronDown className="h-4 w-4" />
        </Button>
      </div>

      {loading ? (
        <AnalyticsSkeleton />
      ) : (
        <>
          {/* KPI Cards */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <KPICard
              title="K-Factor"
              value={kpis?.kFactor.toFixed(2) || '0.00'}
              icon={<TrendingUp className="h-5 w-5" />}
              change={0} // Backend doesn't support historical comparison yet for K-Factor
            />
            <KPICard
              title="Total Reach"
              value={kpis?.totalReach.toLocaleString() || '0'}
              suffix="users"
              icon={<Users className="h-5 w-5" />}
              change={0}
            />
            <KPICard
              title="Conversion Rate"
              value={kpis?.conversionRate.toFixed(1) || '0.0'}
              suffix="%"
              icon={<Target className="h-5 w-5" />}
              change={0}
            />
            <KPICard
              title="Total Revenue"
              value={kpis?.totalRevenue.toLocaleString() || '0'}
              suffix="tokens"
              icon={<Coins className="h-5 w-5" />}
              change={kpis?.revenueChange}
            />
          </div>

          {/* Main Analytics Grid */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <KFactorVisualization kFactor={kpis?.kFactor || 0} />
            <ConversionFunnel promotions={promotions} />
          </div>

          {/* Retention Chart */}
          <RetentionChart data={retention} />

          {/* Promotion Performance Table */}
          <PromotionPerformanceTable promotions={promotions} />
        </>
      )}
    </div>
  );
}
