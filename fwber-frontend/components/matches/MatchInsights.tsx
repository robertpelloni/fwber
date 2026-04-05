import React from 'react';
import { useMatchInsights, useUnlockMatchInsights } from '@/lib/hooks/use-match-insights';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Loader2, Activity, Heart, Brain, MessageCircle, Zap, Lock, Sparkles } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface MatchInsightsProps {
  matchId: string;
}

function ScoreBar({ label, score, color = 'bg-blue-500' }: { label: string; score: number; color?: string }) {
  return (
    <div className="space-y-1">
      <div className="flex justify-between text-xs">
        <span className="font-medium text-gray-700 dark:text-gray-300">{label}</span>
        <span className="text-gray-500">{score}%</span>
      </div>
      <div className="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
        <div className={`h-full ${color} transition-all duration-500 ease-out`} style={{ width: `${score}%` }} />
      </div>
    </div>
  );
}

export function MatchInsights({ matchId }: MatchInsightsProps) {
  const { data, isLoading, error } = useMatchInsights(matchId);
  const unlock = useUnlockMatchInsights(matchId);

  if (isLoading) {
    return (
      <div className="flex justify-center p-4">
        <Loader2 className="h-6 w-6 animate-spin text-gray-400" />
      </div>
    );
  }

  if (error || !data) {
    return null;
  }

  const getScoreColor = (score: number) => {
    if (score >= 80) return 'text-green-500';
    if (score >= 60) return 'text-blue-500';
    if (score >= 40) return 'text-yellow-500';
    return 'text-red-500';
  };

  if (data.is_locked) {
    return (
      <Card className="w-full border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <CardHeader className="pb-2">
          <CardTitle className="flex items-center justify-between text-lg font-semibold">
            <span className="flex items-center gap-2">
              <Activity className="h-5 w-5 text-purple-500" />
              AI Compatibility Report
            </span>
            <span className={`text-2xl font-bold ${getScoreColor(data.total_score)}`}>{data.total_score}%</span>
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="rounded-xl border border-dashed border-purple-300 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
            <div className="mb-2 flex items-center gap-2 font-semibold text-purple-700 dark:text-purple-300">
              <Lock className="h-4 w-4" />
              Premium Insight Locked
            </div>
            <p className="text-sm text-gray-700 dark:text-gray-300">{data.preview_message}</p>
          </div>

          <Button onClick={() => unlock.mutate()} disabled={unlock.isPending} className="w-full bg-purple-600 text-white hover:bg-purple-700">
            {unlock.isPending ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Unlocking...
              </>
            ) : (
              <>
                <Sparkles className="mr-2 h-4 w-4" />
                Unlock Analysis ({data.cost} Tokens)
              </>
            )}
          </Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="w-full border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
      <CardHeader className="pb-2">
        <CardTitle className="flex items-center justify-between text-lg font-semibold">
          <span className="flex items-center gap-2">
            <Activity className="h-5 w-5 text-purple-500" />
            Compatibility
          </span>
          <span className={`text-2xl font-bold ${getScoreColor(data.total_score)}`}>{data.total_score}%</span>
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-6">
        <div className="space-y-3">
          <ScoreBar label="Overall Preferences" score={data.breakdown.preferences} color="bg-purple-500" />
          <ScoreBar label="Communication Style" score={data.breakdown.communication} color="bg-blue-500" />
          <ScoreBar label="Behavioral Match" score={data.breakdown.behavioral} color="bg-green-500" />
        </div>

        <div className="grid grid-cols-2 gap-4 border-t border-gray-100 pt-2 dark:border-gray-700">
          <div className="flex items-center gap-2">
            <div className="rounded-lg bg-pink-100 p-2 dark:bg-pink-900/20">
              <Heart className="h-4 w-4 text-pink-500" />
            </div>
            <div>
              <p className="text-xs text-gray-500">Physical</p>
              <p className="text-sm font-semibold">{data.details.physical}%</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <div className="rounded-lg bg-red-100 p-2 dark:bg-red-900/20">
              <Zap className="h-4 w-4 text-red-500" />
            </div>
            <div>
              <p className="text-xs text-gray-500">Sexual</p>
              <p className="text-sm font-semibold">{data.details.sexual}%</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <div className="rounded-lg bg-indigo-100 p-2 dark:bg-indigo-900/20">
              <Brain className="h-4 w-4 text-indigo-500" />
            </div>
            <div>
              <p className="text-xs text-gray-500">Personality</p>
              <p className="text-sm font-semibold">{data.details.personality}%</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <div className="rounded-lg bg-orange-100 p-2 dark:bg-orange-900/20">
              <MessageCircle className="h-4 w-4 text-orange-500" />
            </div>
            <div>
              <p className="text-xs text-gray-500">Lifestyle</p>
              <p className="text-sm font-semibold">{data.details.lifestyle}%</p>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
