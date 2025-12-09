import React, { useState } from 'react';
import { useAIContent } from '@/lib/hooks/use-ai-content';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Loader2, Sparkles, RefreshCw, Check } from 'lucide-react';

interface BioGeneratorProps {
  onSelectBio: (bio: string) => void;
  currentBio?: string;
}

export function BioGenerator({ onSelectBio, currentBio }: BioGeneratorProps) {
  const { generateBio } = useAIContent();
  const [interests, setInterests] = useState('');
  const [personality, setPersonality] = useState('');
  const [style, setStyle] = useState<'casual' | 'formal' | 'humorous' | 'romantic'>('casual');

  const handleGenerate = () => {
    generateBio.mutate({
      interests: interests.split(',').map(i => i.trim()).filter(Boolean),
      personality,
      style
    });
  };

  return (
    <Card className="w-full max-w-md">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Sparkles className="h-5 w-5 text-purple-500" />
          AI Bio Generator
        </CardTitle>
        <CardDescription>
          Create a unique bio based on your interests and personality.
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="space-y-2">
          <Label htmlFor="interests">Interests (comma separated)</Label>
          <Input
            id="interests"
            placeholder="Hiking, Photography, Coding..."
            value={interests}
            onChange={(e) => setInterests(e.target.value)}
          />
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="personality">Personality Traits</Label>
          <Input
            id="personality"
            placeholder="Adventurous, Introverted, Curious..."
            value={personality}
            onChange={(e) => setPersonality(e.target.value)}
          />
        </div>

        <div className="space-y-2">
          <Label>Style</Label>
          <div className="flex flex-wrap gap-2">
            {(['casual', 'formal', 'humorous', 'romantic'] as const).map((s) => (
              <Button
                key={s}
                variant={style === s ? "default" : "outline"}
                size="sm"
                onClick={() => setStyle(s)}
                className="capitalize"
              >
                {s}
              </Button>
            ))}
          </div>
        </div>

        {generateBio.error && (
          <div className="text-sm text-red-500 bg-red-50 p-2 rounded">
            Error: {(generateBio.error as any).message || 'Something went wrong'}
          </div>
        )}

        {generateBio.data && (
          <div className="space-y-3 mt-4">
            <Label>Suggestions</Label>
            {generateBio.data.suggestions.map((suggestion) => (
              <div 
                key={suggestion.id} 
                className="p-3 border rounded-lg hover:bg-slate-50 transition-colors cursor-pointer group relative"
                onClick={() => onSelectBio(suggestion.content)}
              >
                <p className="text-sm text-slate-700 pr-8">{suggestion.content}</p>
                <Button
                  size="icon"
                  variant="ghost"
                  className="absolute right-2 top-2 opacity-0 group-hover:opacity-100 h-6 w-6"
                  onClick={(e) => {
                    e.stopPropagation();
                    onSelectBio(suggestion.content);
                  }}
                >
                  <Check className="h-4 w-4 text-green-600" />
                </Button>
              </div>
            ))}
          </div>
        )}
      </CardContent>
      <CardFooter>
        <Button 
          className="w-full" 
          onClick={handleGenerate} 
          disabled={generateBio.isPending}
        >
          {generateBio.isPending ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Generating...
            </>
          ) : (
            <>
              <RefreshCw className="mr-2 h-4 w-4" />
              Generate Bio
            </>
          )}
        </Button>
      </CardFooter>
    </Card>
  );
}
