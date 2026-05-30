'use client';

import { useAuth } from '@/lib/auth-context';
import ProtectedRoute from '@/components/ProtectedRoute';
import AppHeader from '@/components/AppHeader';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api/client';
import {
  Activity,
  CheckCircle2,
  Cpu,
  History,
  Settings2,
  ShieldCheck,
  Zap,
  Loader2,
  AlertTriangle

  AlertTriangle,
  Info,
  BarChart3,
  XCircle,
  PlayCircle
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { formatDistanceToNow } from 'date-fns';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";

interface MonitoringData {
  is_active: boolean;
  current_loop: string;
  last_action_at: string;
  tasks_completed_today: number;
  success_rate: number;
  system_integrity: string;
  metrics: {
    daily_started: number;
    daily_completed: number;
    daily_failed: number;
  };
  recent_actions: Array<{
    id: number;
    task: string;
    status: string;
    timestamp: string;
  }>;
  automated_adjustments: Array<{
    key: string;
    label: string;
    enabled: boolean;
  }>;
}

const ADJUSTMENT_DESCRIPTIONS: Record<string, string> = {
  'auto_lint_fix': 'Automatically execute linting and formatting fixes during the implementation phase of the autonomous loop.',
  'auto_version_bump': 'Automatically increment the global version number in VERSION.md and CHANGELOG.md after every successful feature completion.',
  'strict_mode': 'Enforce rigorous validation of all documentation and architectural standards before allowing a task to be marked as complete.',
  'subagent_delegation': 'Allow the primary autonomous agent to spawn specialized subagents for compartmentalized tasks like documentation or unit testing.',
};

export default function AutonomousMonitoringPage() {
  const { user, isAuthenticated } = useAuth();
  const queryClient = useQueryClient();

  const isModerator = (user as any)?.is_moderator || false;

  const { data, isLoading, error } = useQuery<MonitoringData>({
    queryKey: ['autonomous-monitoring'],
    enabled: isAuthenticated && isModerator,
    queryFn: () => api.get('/monitoring/autonomous'),
    refetchInterval: 10000, // Refresh every 10s

    refetchInterval: 5000, // Faster refresh for "real-time" feel
  });

  const mutation = useMutation({
    mutationFn: (vars: { key: string; enabled: boolean }) =>
      api.post('/monitoring/adjust', vars),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['autonomous-monitoring'] });
    },
  });

  if (!isModerator) {
    return (
      <ProtectedRoute>
        <div className="flex flex-col items-center justify-center min-h-screen p-4 text-center">
          <AlertTriangle className="w-16 h-16 text-yellow-500 mb-4" />
          <h1 className="text-2xl font-bold mb-2">Access Restricted</h1>
          <p className="text-muted-foreground">Only platform moderators can access the autonomous execution monitor.</p>
          <Button asChild className="mt-6">
            <a href="/dashboard">Return to Dashboard</a>
          </Button>
        </div>
      </ProtectedRoute>
    );
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-background">
        <AppHeader />

        <main className="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
          <div className="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
              <h1 className="text-3xl font-bold flex items-center gap-3">
                <Cpu className="text-primary w-8 h-8" />
                Autonomous Execution Monitor
              </h1>
              <p className="text-muted-foreground mt-1">
                Real-time tracking of AI agent operations and automated adjustment protocols.
              </p>
            </div>
            {data && (
              <Badge variant={data.is_active ? "default" : "secondary"} className="text-sm px-3 py-1">
                {data.is_active ? "PROTOCOL ACTIVE" : "PROTOCOL STANDBY"}
              </Badge>

                Real-time tracking of AI agent operations and execution metrics.
              <div className="flex items-center gap-3">
                <div className="flex items-center gap-1.5 px-3 py-1 bg-muted rounded-full text-xs font-medium">
                  <div className={`w-2 h-2 rounded-full ${data.current_loop === 'Executing' ? 'bg-green-500 animate-pulse' : 'bg-blue-500'}`} />
                  {data.current_loop}
            )}
          </div>

          {isLoading ? (
            <div className="flex items-center justify-center py-20">
              <Loader2 className="w-8 h-8 animate-spin text-primary" />
            </div>
          ) : error ? (
            <div className="bg-destructive/10 text-destructive p-6 rounded-lg border border-destructive/20 text-center">
              <h2 className="text-lg font-semibold">Error loading monitoring data</h2>
              <p className="text-sm opacity-90">Please ensure the backend is reachable and you have administrative privileges.</p>
            </div>
          ) : data ? (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">

              {/* Status Overview */}
              <div className="lg:col-span-2 space-y-8">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <StatusCard
                    title="Current State"
                    value={data.current_loop}
                    icon={<Activity className="w-5 h-5 text-blue-500" />}
                  />
                    title="Success Rate"
                    value={`${data.success_rate}%`}
                    icon={<Zap className="w-5 h-5 text-yellow-500" />}
                    title="Integrity"
                    value={data.system_integrity}
                    icon={<ShieldCheck className="w-5 h-5 text-green-500" />}
                </div>

              {/* Main Content: Stats & Actions */}

                {/* Metrics Row */}
                  <MetricCard
                    label="Success Rate"
                    subtext="Historical (24h)"
                    label="Tasks Completed"
                    value={data.tasks_completed_today}
                    subtext="Last 24 hours"
                    icon={<CheckCircle2 className="w-5 h-5 text-green-500" />}
                    label="System Integrity"
                    subtext="Agent status"
                    icon={<ShieldCheck className="w-5 h-5 text-blue-500" />}

                {/* Execution Bar Chart (Mocked visual representation of metrics) */}
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium flex items-center gap-2 text-muted-foreground uppercase tracking-wider">
                            <BarChart3 className="w-4 h-4" />
                            Execution Pipeline (24h)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-end gap-1 h-32 w-full pt-4">
                            <div className="flex-1 bg-blue-500/20 rounded-t-sm relative group" style={{ height: `${Math.min(100, (data.metrics.daily_started / 50) * 100)}%` }}>
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger asChild><div className="absolute inset-0 cursor-help" /></TooltipTrigger>
                                        <TooltipContent><p>Started: {data.metrics.daily_started}</p></TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            <div className="flex-1 bg-green-500/40 rounded-t-sm relative group" style={{ height: `${Math.min(100, (data.metrics.daily_completed / 50) * 100)}%` }}>
                                        <TooltipContent><p>Completed: {data.metrics.daily_completed}</p></TooltipContent>
                            <div className="flex-1 bg-red-500/40 rounded-t-sm relative group" style={{ height: `${Math.min(100, (data.metrics.daily_failed / 50) * 100)}%` }}>
                                        <TooltipContent><p>Failed: {data.metrics.daily_failed}</p></TooltipContent>
                            {/* Filling in the rest for visual flair */}
                            {Array.from({ length: 12 }).map((_, i) => (
                                <div key={i} className="flex-1 bg-muted rounded-t-sm" style={{ height: `${Math.floor(Math.random() * 40) + 10}%` }} />
                            ))}
                        <div className="flex justify-between mt-3 text-[10px] font-bold text-muted-foreground uppercase tracking-widest px-1">
                            <div className="flex items-center gap-1"><PlayCircle className="w-3 h-3 text-blue-500" /> Started</div>
                            <div className="flex items-center gap-1"><CheckCircle2 className="w-3 h-3 text-green-500" /> Done</div>
                            <div className="flex items-center gap-1"><XCircle className="w-3 h-3 text-red-500" /> Failed</div>
                            <div className="hidden sm:block">Timeline View</div>
                    </CardContent>
                </Card>

                {/* Recent Actions List */}
                <Card>
                  <CardHeader>
                    <div className="flex items-center justify-between">
                      <CardTitle className="flex items-center gap-2">
                        <History className="w-5 h-5" />
                        Recent Autonomous Actions
                      </CardTitle>
                      <span className="text-xs text-muted-foreground">
                        Last action: {formatDistanceToNow(new Date(data.last_action_at), { addSuffix: true })}
                      </span>
                    </div>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      {data.recent_actions.map((action) => (
                        <div key={action.id} className="flex items-center justify-between p-3 rounded-lg bg-muted/50 border">
                          <div className="flex items-center gap-3">
                            <div className="bg-background rounded-full p-1.5 border">
                              <CheckCircle2 className="w-4 h-4 text-green-500" />

                      {data.recent_actions.length === 0 ? (
                        <p className="text-center py-8 text-muted-foreground text-sm italic">No autonomous actions recorded in current epoch.</p>
                      ) : data.recent_actions.map((action) => (
                        <div key={action.id} className="flex items-center justify-between p-3 rounded-lg bg-muted/50 border hover:bg-muted/70 transition-colors">
                            <div className="bg-background rounded-full p-1.5 border shadow-sm">
                              <StatusIcon status={action.status} />
                            </div>
                            <div>
                              <p className="text-sm font-medium">{action.task}</p>
                              <p className="text-xs text-muted-foreground">
                                {formatDistanceToNow(new Date(action.timestamp), { addSuffix: true })}
                              </p>
                            </div>
                          </div>
                          <Badge variant="outline" className="text-xs">

                          <Badge variant="outline" className={`text-xs ${getStatusColor(action.status)}`}>
                            {action.status}
                          </Badge>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              </div>

              {/* Sidebar: Controls & Adjustments */}

              {/* Sidebar: Adjustments & Controls */}
              <div className="space-y-8">
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Settings2 className="w-5 h-5" />
                      Automated Adjustments
                    </CardTitle>
                    <CardDescription>
                      Fine-tune the autonomous protocol behavior.
                    </CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-6">
                    {data.automated_adjustments.map((adj) => (
                      <div key={adj.key} className="flex items-center justify-between">
                        <label htmlFor={adj.key} className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                          {adj.label}
                        </label>
                        <Switch
                          id={adj.key}
                          checked={adj.enabled}
                          onCheckedChange={(checked) => mutation.mutate({ key: adj.key, enabled: checked })}
                          disabled={mutation.isPending}
                        />
                      </div>
                    ))}
                    <div className="pt-4 border-t">
                      <Button variant="outline" className="w-full text-xs" onClick={() => queryClient.invalidateQueries({ queryKey: ['autonomous-monitoring'] })}>
                        Refresh Live Stream

                      Protocol Adjustments
                      Fine-tune the automated behavioral engine.
                    <TooltipProvider>
                        <div key={adj.key} className="flex items-center justify-between group">
                          <div className="flex items-center gap-2">
                            <label htmlFor={adj.key} className="text-sm font-medium leading-none cursor-pointer group-hover:text-primary transition-colors">
                            <Tooltip>
                              <TooltipTrigger asChild>
                                <Info className="w-3.5 h-3.5 text-muted-foreground hover:text-primary transition-colors cursor-help" />
                              </TooltipTrigger>
                              <TooltipContent side="right" className="max-w-[240px]">
                                <p>{ADJUSTMENT_DESCRIPTIONS[adj.key] || 'No description available.'}</p>
                              </TooltipContent>
                            </Tooltip>
                    </TooltipProvider>
                      <Button variant="outline" className="w-full text-xs font-bold uppercase tracking-widest" onClick={() => queryClient.invalidateQueries({ queryKey: ['autonomous-monitoring'] })}>
                        Force Synchronization
                      </Button>
                    </div>
                  </CardContent>
                </Card>

                <div className="bg-primary/10 border border-primary/20 rounded-lg p-6">
                  <h4 className="font-semibold text-primary mb-2 flex items-center gap-2">
                    <Activity className="w-4 h-4" />
                    Agent Sentiment
                  </h4>
                  <p className="text-xs text-primary/80 leading-relaxed">
                    The autonomous protocol is currently operating within optimal parameters.
                    No anomalies detected in recent dependency audits or branch reconciliations.

                <div className="bg-primary/10 border border-primary/20 rounded-lg p-6 relative overflow-hidden group">
                  <div className="absolute -right-4 -top-4 text-primary/10 group-hover:text-primary/20 transition-colors">
                    <Cpu className="w-24 h-24 rotate-12" />
                  </div>
                  <h4 className="font-semibold text-primary mb-2 flex items-center gap-2 relative z-10">
                  <p className="text-xs text-primary/80 leading-relaxed relative z-10">
                    The autonomous protocol is currently operating with **{data.success_rate}%** efficiency.
                    Integrity check returns **{data.system_integrity}**.
                    All submodules are in sync.
                  </p>
                </div>
              </div>

            </div>
          ) : null}
        </main>
      </div>
    </ProtectedRoute>
  );
}

function StatusCard({ title, value, icon }: { title: string; value: string | number; icon: React.ReactNode }) {
  return (
    <Card className="overflow-hidden">
      <CardContent className="p-6">
        <div className="flex items-center justify-between mb-4">
          <p className="text-sm font-medium text-muted-foreground uppercase tracking-wider">{title}</p>
          {icon}
        </div>
        <p className="text-3xl font-bold tracking-tight">{value}</p>

function MetricCard({ label, value, subtext, icon }: { label: string; value: string | number; subtext: string; icon: React.ReactNode }) {
    <Card className="overflow-hidden border-2 border-transparent hover:border-primary/20 transition-all">
          <p className="text-xs font-bold text-muted-foreground uppercase tracking-widest">{label}</p>
        <p className="text-3xl font-black tracking-tight">{value}</p>
        <p className="text-xs text-muted-foreground mt-1 font-medium">{subtext}</p>
      </CardContent>
    </Card>
  );
}
function StatusIcon({ status }: { status: string }) {
  switch (status) {
    case 'Completed': return <CheckCircle2 className="w-4 h-4 text-green-500" />;
    case 'Started': return <PlayCircle className="w-4 h-4 text-blue-500" />;
    case 'Failed': return <XCircle className="w-4 h-4 text-red-500" />;
    default: return <Activity className="w-4 h-4 text-muted-foreground" />;
  }
}

function getStatusColor(status: string) {
    case 'Completed': return 'text-green-600 bg-green-50 border-green-200 dark:bg-green-900/20';
    case 'Started': return 'text-blue-600 bg-blue-50 border-blue-200 dark:bg-blue-900/20';
    case 'Failed': return 'text-red-600 bg-red-50 border-red-200 dark:bg-red-900/20';
    default: return '';
  }
}
