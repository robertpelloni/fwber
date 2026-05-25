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
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { formatDistanceToNow } from 'date-fns';

interface MonitoringData {
  is_active: boolean;
  current_loop: string;
  last_action_at: string;
  tasks_completed_today: number;
  success_rate: number;
  system_integrity: string;
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

export default function AutonomousMonitoringPage() {
  const { user, isAuthenticated } = useAuth();
  const queryClient = useQueryClient();

  const isModerator = (user as any)?.is_moderator || false;

  const { data, isLoading, error } = useQuery<MonitoringData>({
    queryKey: ['autonomous-monitoring'],
    enabled: isAuthenticated && isModerator,
    queryFn: () => api.get('/monitoring/autonomous'),
    refetchInterval: 10000, // Refresh every 10s
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
                  <StatusCard
                    title="Success Rate"
                    value={`${data.success_rate}%`}
                    icon={<Zap className="w-5 h-5 text-yellow-500" />}
                  />
                  <StatusCard
                    title="Integrity"
                    value={data.system_integrity}
                    icon={<ShieldCheck className="w-5 h-5 text-green-500" />}
                  />
                </div>

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
                            </div>
                            <div>
                              <p className="text-sm font-medium">{action.task}</p>
                              <p className="text-xs text-muted-foreground">
                                {formatDistanceToNow(new Date(action.timestamp), { addSuffix: true })}
                              </p>
                            </div>
                          </div>
                          <Badge variant="outline" className="text-xs">
                            {action.status}
                          </Badge>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              </div>

              {/* Sidebar: Controls & Adjustments */}
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
      </CardContent>
    </Card>
  );
}
