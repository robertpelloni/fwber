'use client';

import { useState } from 'react';
import { useMatchingQuestions, useSubmitMatchingAnswer, MatchingQuestion } from '@/lib/hooks/use-matching';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Sparkles, ArrowLeft, ArrowRight, CheckCircle2 } from 'lucide-react';

export default function MatchingQuestionsPage() {
  const { data: questions, isLoading } = useMatchingQuestions();
  const submitAnswer = useSubmitMatchingAnswer();
  const [currentIndex, setCurrentIndex] = useState(0);

  if (isLoading) return <div className="flex items-center justify-center min-h-screen">Loading matching engine...</div>;
  if (!questions || questions.length === 0) return <div>No questions found.</div>;

  const currentQuestion = questions[currentIndex];

  return (
    <div className="max-w-2xl mx-auto py-10 px-4">
      <div className="flex items-center gap-2 mb-6">
        <Sparkles className="w-6 h-6 text-purple-500" />
        <h1 className="text-3xl font-bold">Matching Algorithm</h1>
      </div>

      <p className="text-muted-foreground mb-8">
        Answer these questions to improve your match accuracy. Our AI uses these to calculate
        your compatibility with others.
      </p>

      <div className="space-y-6">
        <div className="flex justify-between items-center text-sm font-medium">
          <span>Question {currentIndex + 1} of {questions.length}</span>
          <span>{Math.round(((currentIndex + 1) / questions.length) * 100)}% Complete</span>
        </div>

        <QuestionCard
          question={currentQuestion}
          onSave={(data) => submitAnswer.mutate({ ...data, question_id: currentQuestion.id })}
          isPending={submitAnswer.isPending}
        />

        <div className="flex justify-between">
          <Button
            variant="outline"
            onClick={() => setCurrentIndex(prev => Math.max(0, prev - 1))}
            disabled={currentIndex === 0}
          >
            <ArrowLeft className="w-4 h-4 mr-2" /> Previous
          </Button>
          <Button
            onClick={() => setCurrentIndex(prev => Math.min(questions.length - 1, prev + 1))}
            disabled={currentIndex === questions.length - 1}
          >
            Next <ArrowRight className="w-4 h-4 ml-2" />
          </Button>
        </div>
      </div>
    </div>
  );
}

function QuestionCard({
  question,
  onSave,
  isPending
}: {
  question: MatchingQuestion;
  onSave: (data: any) => void;
  isPending: boolean;
}) {
  const [chosenOption, setChosenOption] = useState(question.answer?.chosen_option_id || '');
  const [acceptedOptions, setAcceptedOptions] = useState<string[]>(question.answer?.accepted_option_ids || []);
  const [importance, setImportance] = useState(question.answer?.importance?.toString() || '1');
  const [explanation, setExplanation] = useState(question.answer?.explanation || '');

  const handleSave = () => {
    onSave({
      chosen_option_id: chosenOption,
      accepted_option_ids: acceptedOptions,
      importance: parseInt(importance),
      explanation
    });
  };

  const toggleAccepted = (id: string) => {
    setAcceptedOptions(prev =>
      prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
    );
  };

  return (
    <Card className="border-purple-500/20 bg-background/50 backdrop-blur-sm">
      <CardHeader>
        <CardTitle className="text-xl">{question.text}</CardTitle>
        <CardDescription>Category: {question.category}</CardDescription>
      </CardHeader>
      <CardContent className="space-y-8">
        <div className="space-y-4">
          <Label className="text-lg font-semibold">Your Answer</Label>
          <RadioGroup value={chosenOption} onValueChange={setChosenOption}>
            {question.options.map(opt => (
              <div key={opt.id} className="flex items-center space-x-2">
                <RadioGroupItem value={opt.id} id={`opt-${opt.id}`} />
                <Label htmlFor={`opt-${opt.id}`}>{opt.text}</Label>
              </div>
            ))}
          </RadioGroup>
        </div>

        <div className="space-y-4">
          <Label className="text-lg font-semibold">Answers you&apos;ll accept from them</Label>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {question.options.map(opt => (
              <div key={opt.id} className="flex items-center space-x-2">
                <Checkbox
                  id={`acc-${opt.id}`}
                  checked={acceptedOptions.includes(opt.id)}
                  onCheckedChange={() => toggleAccepted(opt.id)}
                />
                <Label htmlFor={`acc-${opt.id}`}>{opt.text}</Label>
              </div>
            ))}
          </div>
        </div>

        <div className="space-y-4">
          <Label className="text-lg font-semibold">Importance</Label>
          <Select value={importance} onValueChange={setImportance}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="0">Irrelevant</SelectItem>
              <SelectItem value="1">A little important</SelectItem>
              <SelectItem value="2">Somewhat important</SelectItem>
              <SelectItem value="3">Very important</SelectItem>
              <SelectItem value="4">Mandatory</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-4">
          <Label className="text-lg font-semibold">Explanation (optional)</Label>
          <Textarea
            placeholder="Why is this important to you?"
            value={explanation}
            onChange={(e) => setExplanation(e.target.value)}
          />
        </div>

        <Button
          className="w-full bg-purple-600 hover:bg-purple-700"
          onClick={handleSave}
          disabled={!chosenOption || acceptedOptions.length === 0 || isPending}
        >
          {isPending ? 'Saving...' : question.answer ? 'Update Answer' : 'Save Answer'}
        </Button>
      </CardContent>
    </Card>
  );
}
