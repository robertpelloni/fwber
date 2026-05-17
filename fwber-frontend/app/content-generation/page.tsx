'use client';

import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Info, Sparkles } from 'lucide-react';
import { toast } from 'react-toastify';

export default function ContentGenerationPage() {
    const [prompt, setPrompt] = useState('');
    const [generating, setGenerating] = useState(false);
    const [generatedBio, setGeneratedBio] = useState<string | null>(null);

    const handleGenerate = async () => {
        if (!prompt) return;
        setGenerating(true);
        try {
            const res = await fetch('/api/content-generation/bio', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt })
            });
            if (!res.ok) throw new Error('Generation failed');
            const data = await res.json();
            setGeneratedBio(data.bio);
        } catch (e: any) {
            toast.error(e.message || 'Failed to generate content');
        } finally {
            setGenerating(false);
        }
    };

    return (
        <div className="container mx-auto p-4 md:p-8 max-w-4xl space-y-6">
            <h1 className="text-3xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <Sparkles className="w-8 h-8 text-primary" />
                AI Content Generation
            </h1>

            <Card className="bg-white shadow-sm border border-gray-100">
                <CardHeader>
                    <CardTitle className="text-xl flex items-center gap-2">
                        Profile Bio Generator
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Info className="w-4 h-4 text-gray-400 cursor-help" />
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Uses AI to generate a compelling dating profile bio based on your interests.</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </CardTitle>
                    <CardDescription>
                        Struggling to write about yourself? Tell our AI Wingman a few things you like, and we'll craft the perfect bio.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <textarea
                        className="w-full p-3 border rounded-lg h-32 focus:ring-2 focus:ring-primary/50"
                        placeholder="E.g., I love hiking, dogs, and trying new coffee shops. Looking for someone adventurous..."
                        value={prompt}
                        onChange={(e) => setPrompt(e.target.value)}
                    />
                    <Button
                        onClick={handleGenerate}
                        disabled={generating || !prompt}
                        className="w-full sm:w-auto flex items-center gap-2"
                    >
                        {generating ? 'Crafting Bio...' : 'Generate Bio'}
                        <Sparkles className="w-4 h-4" />
                    </Button>

                    {generatedBio && (
                        <div className="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <h3 className="font-semibold text-gray-700 mb-2">Suggested Bio:</h3>
                            <p className="text-gray-800 whitespace-pre-wrap">{generatedBio}</p>
                            <div className="mt-4 flex gap-2">
                                <Button variant="outline" size="sm" onClick={() => navigator.clipboard.writeText(generatedBio)}>
                                    Copy to Clipboard
                                </Button>
                            </div>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
