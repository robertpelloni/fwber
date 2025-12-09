'use client';

import { useState, useRef } from 'react';
import { Mic, Square, X, Send, Trash2 } from 'lucide-react';

interface AudioRecorderProps {
  onRecordingComplete: (audioFile: File, duration: number) => void;
  isSending?: boolean;
}

export default function AudioRecorder({ onRecordingComplete, isSending }: AudioRecorderProps) {
  const [isRecording, setIsRecording] = useState(false);
  const [duration, setDuration] = useState(0);
  const [audioBlob, setAudioBlob] = useState<Blob | null>(null);
  const mediaRecorderRef = useRef<MediaRecorder | null>(null);
  const timerRef = useRef<NodeJS.Timeout | null>(null);
  const chunksRef = useRef<Blob[]>([]);

  const startRecording = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const mediaRecorder = new MediaRecorder(stream);
      mediaRecorderRef.current = mediaRecorder;
      chunksRef.current = [];

      mediaRecorder.ondataavailable = (e) => {
        if (e.data.size > 0) {
          chunksRef.current.push(e.data);
        }
      };

      mediaRecorder.onstop = () => {
        const blob = new Blob(chunksRef.current, { type: 'audio/webm' });
        setAudioBlob(blob);
        stream.getTracks().forEach(track => track.stop());
      };

      mediaRecorder.start();
      setIsRecording(true);
      setDuration(0);

      timerRef.current = setInterval(() => {
        setDuration(prev => prev + 1);
      }, 1000);

    } catch (err) {
      console.error('Error accessing microphone:', err);
      alert('Could not access microphone. Please check permissions.');
    }
  };

  const stopRecording = () => {
    if (mediaRecorderRef.current && isRecording) {
      mediaRecorderRef.current.stop();
      setIsRecording(false);
      if (timerRef.current) {
        clearInterval(timerRef.current);
      }
    }
  };

  const cancelRecording = () => {
    if (mediaRecorderRef.current && isRecording) {
      mediaRecorderRef.current.stop();
      // Also stop tracks
      mediaRecorderRef.current.stream.getTracks().forEach(track => track.stop());
    }
    setIsRecording(false);
    if (timerRef.current) {
      clearInterval(timerRef.current);
    }
    setAudioBlob(null);
    setDuration(0);
  };

  const sendRecording = () => {
    if (audioBlob) {
      const file = new File([audioBlob], 'voice-message.webm', { type: 'audio/webm' });
      onRecordingComplete(file, duration);
      setAudioBlob(null);
      setDuration(0);
    }
  };

  const formatDuration = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  if (audioBlob) {
    return (
      <div className="flex items-center gap-2 bg-gray-100 p-2 rounded-lg w-full">
        <div className="flex-1 flex items-center gap-2">
          <span className="text-sm font-medium text-gray-600">{formatDuration(duration)}</span>
          <audio src={URL.createObjectURL(audioBlob)} controls className="h-8 w-48" />
        </div>
        <button
          onClick={() => {
            setAudioBlob(null);
            setDuration(0);
          }}
          className="p-2 text-red-500 hover:bg-red-100 rounded-full"
          title="Delete"
          type="button"
        >
          <Trash2 className="h-5 w-5" />
        </button>
        <button
          onClick={sendRecording}
          disabled={isSending}
          className="p-2 text-blue-500 hover:bg-blue-100 rounded-full"
          title="Send"
          type="button"
        >
          <Send className="h-5 w-5" />
        </button>
      </div>
    );
  }

  if (isRecording) {
    return (
      <div className="flex items-center gap-2 bg-red-50 p-2 rounded-lg w-full">
        <div className="flex-1 flex items-center gap-2">
          <div className="w-3 h-3 bg-red-600 rounded-full animate-pulse" />
          <span className="text-red-600 font-medium">Recording {formatDuration(duration)}...</span>
        </div>
        <button
          onClick={cancelRecording}
          className="p-2 text-gray-500 hover:bg-gray-200 rounded-full"
          title="Cancel"
          type="button"
        >
          <X className="h-5 w-5" />
        </button>
        <button
          onClick={stopRecording}
          className="p-2 text-red-600 hover:bg-red-100 rounded-full"
          title="Stop"
          type="button"
        >
          <Square className="h-5 w-5 fill-current" />
        </button>
      </div>
    );
  }

  return (
    <button
      onClick={startRecording}
      className="p-2 text-gray-500 hover:bg-gray-100 rounded-full transition-colors"
      title="Record Voice Message"
      type="button"
    >
      <Mic className="h-6 w-6" />
    </button>
  );
}
