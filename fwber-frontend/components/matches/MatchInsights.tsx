import React from 'react';
import { useMatchInsights } from '@/lib/hooks/use-match-insights';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Loader2, Activity, Heart, Brain, MessageCircle, Zap } from 'lucide-react';

interface MatchInsightsProps {
  matchId: string;
}

function ScoreBar({ label, score, color = "bg-blue-500" }: { label: string; score: number; color?: string }) {
  return (
    <div className="space-y-1">
      <div className="flex justify-between text-xs">
        <span className="font-medium text-gray-700 dark:text-gray-300">{label}</span>
        <span className="text-gray-500">{score}%</span>
      </div>
      <div className="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
        <div 
          className={`h-full ${color} transition-all duration-500 ease-out`} 
          style={{ width: `${score}%` }}
        />
      </div>
    </div>
  );
}

export function MatchInsights({ matchId }: MatchInsightsProps) {
  const { data, isLoading, error } = useMatchInsights(matchId);

  if (isLoading) {
    return (
      <div className="flex justify-center p-4">
        <Loader2 className="h-6 w-6 animate-spin text-gray-400" />
      </div>
    );
  }

  if (error || !data) {
    return null; // Hide if error or no data
  }

  const getScoreColor = (score: number) => {
    if (score >= 80) return 'text-green-500';
    if (score >= 60) return 'text-blue-500';
    if (score >= 40) return 'text-yellow-500';
    return 'text-red-500';
  };

  return (
    <Card className="w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
      <CardHeader className="pb-2">
        <CardTitle className="text-lg font-semibold flex items-center justify-between">
          <span className="flex items-center gap-2">
            <Activity className="h-5 w-5 text-purple-500" />
            Compatibility
          </span>
          <span className={`text-2xl font-bold ${getScoreColor(data.total_score)}`}>
            {data.total_score}%
          </span>
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Main Breakdown */}
        <div className="space-y-3">
          <ScoreBar label="Overall Preferences" score={data.breakdown.preferences} color="bg-purple-500" />
          <ScoreBar label="Communication Style" score={data.breakdown.communication} color="bg-blue-500" />
          <ScoreBar label="Behavioral Match" score={data.breakdown.behavioral} color="bg-green-500" />
        </div>

        {/* Detailed Categories */}
        <div className="grid grid-cols-2 gap-4 pt-2 border-t border-gray-100 dark:border-gray-700">
          <div className="flex items-center gap-2">
            <div className="p-2 bg-pink-100 dark:bg-pink-900/20 rounded-lg">
              <Heart className="h-4 w-4 text-pink-500" />
            </div>
            <div>
              <p className="text-xs text-gray-500">Physical</p>
              <p className="font-semibold text-sm">{data.details.physical}%</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <div className="p-2 bg-red-100 dark:bg-red-900/20 rounded-lg">
              <Zap className="h-4 w-4 text-red-500" />
            </div>
            <div>
              <p className="text-xs text-gray-500">Sexual</p>
              <p className="font-semibold text-sm">{data.details.sexual}%</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <div className="p-2 bg-indigo-100 dark:bg-indigo-900/20 rounded-lg">
              <Brain className="h-4 w-4 text-indigo-500" />
            </div>
            <div>
              <p className="text-xs text-gray-500">Personality</p>
              <p className="font-semibold text-sm">{data.details.personality}%</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <div className="p-2 bg-orange-100 dark:bg-orange-900/20 rounded-lg">
              <MessageCircle className="h-4 w-4 text-orange-500" />
            </div>
            <div>
              <p className="text-xs text-gray-500">Lifestyle</p>
              <p className="font-semibold text-sm">{data.details.lifestyle}%</p>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
