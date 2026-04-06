import React, { useState } from 'react';
import { useAIContent } from '@/lib/hooks/use-ai-content';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Loader2, MessageCircle, Send } from 'lucide-react';

interface ConversationStarterProps {
  targetUserId?: number;
  onSelectStarter: (text: string) => void;
}

export function ConversationStarter({ targetUserId, onSelectStarter }: ConversationStarterProps) {
  const { generateStarters } = useAIContent();
  const [context, setContext] = useState('');

  const handleGenerate = () => {
    generateStarters.mutate({
      target_user_id: targetUserId,
      context
    });
  };

  return (
    <Card className="w-full">
      <CardHeader className="pb-3">
        <CardTitle className="text-sm font-medium flex items-center gap-2">
          <MessageCircle className="h-4 w-4 text-blue-500" />
          Need an icebreaker?
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        <div className="space-y-2">
          <Label htmlFor="context" className="text-xs text-muted-foreground">
            Add context (optional)
          </Label>
          <Textarea
            id="context"
            placeholder="e.g., We both like jazz music..."
            value={context}
            onChange={(e) => setContext(e.target.value)}
            className="h-20 text-sm resize-none"
          />
        </div>

        <Button 
          size="sm" 
          className="w-full" 
          onClick={handleGenerate}
          disabled={generateStarters.isPending}
        >
          {generateStarters.isPending ? (
            <Loader2 className="h-3 w-3 animate-spin mr-2" />
          ) : (
            <SparklesIcon className="h-3 w-3 mr-2" />
          )}
          Generate Icebreakers
        </Button>

        {generateStarters.data && (
          <div className="space-y-2 mt-2">
            {generateStarters.data.suggestions.map((suggestion) => (
              <Button
                key={suggestion.id}
                variant="outline"
                className="w-full justify-start text-left h-auto whitespace-normal p-2 text-sm"
                onClick={() => onSelectStarter(suggestion.content)}
              >
                <span className="line-clamp-2">{suggestion.content}</span>
              </Button>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}

function SparklesIcon({ className }: { className?: string }) {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
    >
      <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z" />
    </svg>
  );
}
