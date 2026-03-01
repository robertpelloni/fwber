'use client';

import { useState, useRef, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Mic, Square, Trash2, Play, Pause, Volume2 } from 'lucide-react';

interface VoiceIntroRecorderProps {
    currentVoiceUrl?: string | null;
    onVoiceUpload: (file: File) => void;
    onVoiceDelete: () => void;
}

export default function VoiceIntroRecorder({
    currentVoiceUrl,
    onVoiceUpload,
    onVoiceDelete,
}: VoiceIntroRecorderProps) {
    const [isRecording, setIsRecording] = useState(false);
    const [recordingTime, setRecordingTime] = useState(0);
    const [audioUrl, setAudioUrl] = useState<string | null>(null);
    const [isPlaying, setIsPlaying] = useState(false);

    const mediaRecorderRef = useRef<MediaRecorder | null>(null);
    const audioChunksRef = useRef<Blob[]>([]);
    const timerRef = useRef<NodeJS.Timeout | null>(null);
    const audioRef = useRef<HTMLAudioElement | null>(null);

    const startRecording = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const mediaRecorder = new MediaRecorder(stream);
            mediaRecorderRef.current = mediaRecorder;
            audioChunksRef.current = [];

            mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    audioChunksRef.current.push(event.data);
                }
            };

            mediaRecorder.onstop = () => {
                const audioBlob = new Blob(audioChunksRef.current, { type: 'audio/mpeg' });
                const url = URL.createObjectURL(audioBlob);
                setAudioUrl(url);

                // Convert blob to file for upload
                const file = new File([audioBlob], 'voice_intro.mp3', { type: 'audio/mpeg' });
                onVoiceUpload(file);

                // Stop all tracks in stream
                stream.getTracks().forEach(track => track.stop());
            };

            mediaRecorder.start();
            setIsRecording(true);
            setRecordingTime(0);

            timerRef.current = setInterval(() => {
                setRecordingTime(prev => {
                    if (prev >= 30) {
                        stopRecording();
                        return 30;
                    }
                    return prev + 1;
                });
            }, 1000);

        } catch (err) {
            console.error('Error accessing microphone:', err);
            alert('Microphone access is required to record a voice introduction.');
        }
    };

    const stopRecording = () => {
        if (mediaRecorderRef.current && isRecording) {
            mediaRecorderRef.current.stop();
            setIsRecording(false);
            if (timerRef.current) clearInterval(timerRef.current);
        }
    };

    const togglePlayback = () => {
        if (!audioRef.current) return;

        if (isPlaying) {
            audioRef.current.pause();
        } else {
            audioRef.current.play();
        }
        setIsPlaying(!isPlaying);
    };

    const handleDelete = () => {
        setAudioUrl(null);
        onVoiceDelete();
    };

    useEffect(() => {
        return () => {
            if (timerRef.current) clearInterval(timerRef.current);
            if (audioUrl) URL.revokeObjectURL(audioUrl);
        };
    }, [audioUrl]);

    const displayUrl = audioUrl || currentVoiceUrl;

    return (
        <Card className="border-dashed border-2">
            <CardHeader>
                <CardTitle className="text-sm font-medium flex items-center space-x-2">
                    <Volume2 className="w-4 h-4" />
                    <span>Voice Introduction (Max 30s)</span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="flex flex-col items-center space-y-4">
                    {!displayUrl && !isRecording && (
                        <Button
                            type="button"
                            variant="outline"
                            className="w-full py-8 border-2"
                            onClick={startRecording}
                        >
                            <Mic className="w-6 h-6 mr-2" />
                            Start Recording
                        </Button>
                    )}

                    {isRecording && (
                        <div className="w-full flex flex-col items-center space-y-4 py-4 bg-red-50 rounded-lg border border-red-200 animate-pulse">
                            <div className="text-red-600 font-bold text-lg">
                                Recording... {recordingTime}s / 30s
                            </div>
                            <Button
                                type="button"
                                variant="destructive"
                                size="icon"
                                className="w-12 h-12 rounded-full"
                                onClick={stopRecording}
                            >
                                <Square className="w-6 h-6" />
                            </Button>
                        </div>
                    )}

                    {displayUrl && !isRecording && (
                        <div className="w-full space-y-3">
                            <div className="flex items-center justify-between p-3 bg-muted rounded-lg border">
                                <div className="flex items-center space-x-3">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={togglePlayback}
                                    >
                                        {isPlaying ? <Pause className="w-5 h-5" /> : <Play className="w-5 h-5" />}
                                    </Button>
                                    <span className="text-sm font-medium">Your Voice Intro</span>
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="text-destructive hover:text-destructive hover:bg-destructive/10"
                                    onClick={handleDelete}
                                >
                                    <Trash2 className="w-5 h-5" />
                                </Button>
                                <audio
                                    ref={audioRef}
                                    src={displayUrl}
                                    className="hidden"
                                    onEnded={() => setIsPlaying(false)}
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                className="w-full"
                                onClick={startRecording}
                            >
                                <Mic className="w-4 h-4 mr-2" />
                                Record Again
                            </Button>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
