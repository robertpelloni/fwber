import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useAiWingman, DateIdea } from '@/lib/hooks/use-ai-wingman';
import { Calendar, Sparkles, Copy, Check } from 'lucide-react';
import { useToast } from '@/components/ToastProvider';

interface DatePlannerProps {
  matchId: string;
  matchName: string;
}

export function DatePlanner({ matchId, matchName }: DatePlannerProps) {
  const [isOpen, setIsOpen] = useState(false);
  const { getDateIdeas } = useAiWingman();
  const [ideas, setIdeas] = useState<DateIdea[]>([]);
  const [copiedIndex, setCopiedIndex] = useState<number | null>(null);
  const toast = useToast();

  const handleGenerate = () => {
    getDateIdeas.mutate(
      { matchId },
      {
        onSuccess: (data) => {
          setIdeas(data.ideas);
        },
      }
    );
  };

  const copyToClipboard = (text: string, index: number) => {
    navigator.clipboard.writeText(text);
    setCopiedIndex(index);
    setTimeout(() => setCopiedIndex(null), 2000);
    toast.showSuccess('Date idea copied!');
  };

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm" className="gap-2">
          <Calendar className="w-4 h-4" />
          Plan Date
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Sparkles className="w-5 h-5 text-pink-500" />
            Plan a Date with {matchName}
          </DialogTitle>
        </DialogHeader>
        
        <div className="space-y-4 py-4">
          {ideas.length === 0 && !getDateIdeas.isPending && (
            <div className="text-center py-8">
              <p className="text-gray-500 mb-4">
                Let AI suggest personalized date ideas based on your shared interests.
              </p>
              <Button onClick={handleGenerate} className="w-full">
                Generate Ideas
              </Button>
            </div>
          )}

          {getDateIdeas.isPending && (
            <div className="flex flex-col items-center justify-center py-8 space-y-4">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-pink-500" />
              <p className="text-sm text-gray-500">Consulting Cupid...</p>
            </div>
          )}

          {ideas.length > 0 && (
            <div className="space-y-4">
              {ideas.map((idea, index) => (
                <div key={index} className="bg-gray-50 p-4 rounded-lg border border-gray-100 hover:border-pink-200 transition-colors">
                  <div className="flex justify-between items-start mb-2">
                    <h3 className="font-semibold text-gray-900">{idea.title}</h3>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-6 w-6"
                      onClick={() => copyToClipboard(`${idea.title}: ${idea.description}`, index)}
                    >
                      {copiedIndex === index ? (
                        <Check className="w-3 h-3 text-green-500" />
                      ) : (
                        <Copy className="w-3 h-3 text-gray-400" />
                      )}
                    </Button>
                  </div>
                  <p className="text-sm text-gray-600 mb-2">{idea.description}</p>
                  <div className="flex items-center gap-1.5 text-xs text-pink-600 bg-pink-50 px-2 py-1 rounded-full w-fit">
                    <Sparkles className="w-3 h-3" />
                    {idea.reason}
                  </div>
                </div>
              ))}
              
              <Button 
                variant="outline" 
                onClick={handleGenerate} 
                className="w-full mt-4"
                disabled={getDateIdeas.isPending}
              >
                Regenerate
              </Button>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
