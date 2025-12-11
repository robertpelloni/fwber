'use client';

import React, { useEffect, useRef, useState, useCallback } from 'react';
import { useMercure } from '@/lib/contexts/MercureContext';
import { useAuth } from '@/lib/auth-context';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Phone, PhoneOff, Video, Mic, MicOff, VideoOff } from 'lucide-react';

interface VideoCallModalProps {
  recipientId: string;
  isOpen: boolean;
  onClose: () => void;
  isIncoming?: boolean;
}

const ICE_SERVERS = {
  iceServers: [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' },
  ],
};

export function VideoCallModal({ recipientId, isOpen, onClose, isIncoming = false }: VideoCallModalProps) {
  const { user } = useAuth();
  const { sendVideoSignal, videoSignals } = useMercure();
  
  const [localStream, setLocalStream] = useState<MediaStream | null>(null);
  const [remoteStream, setRemoteStream] = useState<MediaStream | null>(null);
  const [callStatus, setCallStatus] = useState<'idle' | 'calling' | 'ringing' | 'connected' | 'ended'>('idle');
  const [isMuted, setIsMuted] = useState(false);
  const [isVideoOff, setIsVideoOff] = useState(false);
  const [incomingOffer, setIncomingOffer] = useState<RTCSessionDescriptionInit | null>(null);
  
  const localVideoRef = useRef<HTMLVideoElement>(null);
  const remoteVideoRef = useRef<HTMLVideoElement>(null);
  const peerConnectionRef = useRef<RTCPeerConnection | null>(null);
  const pendingCandidates = useRef<RTCIceCandidate[]>([]);

  const endCall = useCallback(() => {
    if (localStream) {
      localStream.getTracks().forEach(track => track.stop());
    }
    if (peerConnectionRef.current) {
      peerConnectionRef.current.close();
    }
    sendVideoSignal(recipientId, { type: 'bye' });
    setCallStatus('ended');
    onClose();
  }, [localStream, recipientId, sendVideoSignal, onClose]);

  const createPeerConnection = useCallback((stream: MediaStream) => {
    const pc = new RTCPeerConnection(ICE_SERVERS);
    
    stream.getTracks().forEach(track => pc.addTrack(track, stream));

    pc.onicecandidate = (event) => {
      if (event.candidate) {
        sendVideoSignal(recipientId, { type: 'candidate', candidate: event.candidate });
      }
    };

    pc.ontrack = (event) => {
      setRemoteStream(event.streams[0]);
      if (remoteVideoRef.current) {
        remoteVideoRef.current.srcObject = event.streams[0];
      }
    };

    pc.onconnectionstatechange = () => {
      if (pc.connectionState === 'connected') {
        setCallStatus('connected');
      } else if (pc.connectionState === 'disconnected' || pc.connectionState === 'failed') {
        endCall();
      }
    };

    peerConnectionRef.current = pc;
    return pc;
  }, [recipientId, sendVideoSignal, endCall]);

  const startCall = useCallback(async (stream: MediaStream) => {
    setCallStatus('calling');
    const pc = createPeerConnection(stream);
    
    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);
    
    sendVideoSignal(recipientId, { type: 'offer', sdp: offer });
  }, [createPeerConnection, recipientId, sendVideoSignal]);

  const handleSignal = useCallback(async (signal: any) => {
    if (signal.type === 'offer') {
      setIncomingOffer(signal.sdp);
      setCallStatus('ringing');
    } else if (signal.type === 'answer') {
      if (peerConnectionRef.current) {
        await peerConnectionRef.current.setRemoteDescription(new RTCSessionDescription(signal.sdp));
      }
    } else if (signal.type === 'candidate') {
      const candidate = new RTCIceCandidate(signal.candidate);
      if (peerConnectionRef.current && peerConnectionRef.current.remoteDescription) {
        await peerConnectionRef.current.addIceCandidate(candidate);
      } else {
        pendingCandidates.current.push(candidate);
      }
    } else if (signal.type === 'bye') {
      endCall();
    }
  }, [endCall]);

  // Initialize local stream
  useEffect(() => {
    if (isOpen && !localStream) {
      navigator.mediaDevices.getUserMedia({ video: true, audio: true })
        .then(stream => {
          setLocalStream(stream);
          if (localVideoRef.current) {
            localVideoRef.current.srcObject = stream;
          }
          if (isIncoming) {
            setCallStatus('ringing');
          } else {
            startCall(stream);
          }
        })
        .catch(err => {
          console.error('Error accessing media devices:', err);
          // onClose(); // Don't close immediately, let user see error or retry
        });
    }
    
    return () => {
      // Cleanup handled in separate effect or onClose
    };
  }, [isOpen, isIncoming, localStream, startCall]);

  // Handle incoming signals
  useEffect(() => {
    const lastSignal = videoSignals[videoSignals.length - 1];
    if (!lastSignal || lastSignal.from_user_id !== recipientId) return;

    handleSignal(lastSignal.signal);
  }, [videoSignals, recipientId, handleSignal]);

  const acceptCall = async () => {
    if (!localStream || !incomingOffer) return;
    
    const pc = createPeerConnection(localStream);
    await pc.setRemoteDescription(new RTCSessionDescription(incomingOffer));
    
    // Process pending candidates now that remote description is set
    while (pendingCandidates.current.length > 0) {
        const candidate = pendingCandidates.current.shift();
        if (candidate) await pc.addIceCandidate(candidate);
    }

    const answer = await pc.createAnswer();
    await pc.setLocalDescription(answer);
    
    sendVideoSignal(recipientId, { type: 'answer', sdp: answer });
    setCallStatus('connected');
  };

  const toggleMute = () => {
    if (localStream) {
      localStream.getAudioTracks().forEach(track => track.enabled = !track.enabled);
      setIsMuted(!isMuted);
    }
  };

  const toggleVideo = () => {
    if (localStream) {
      localStream.getVideoTracks().forEach(track => track.enabled = !track.enabled);
      setIsVideoOff(!isVideoOff);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={() => endCall()}>
      <DialogContent className="sm:max-w-[900px] h-[80vh] bg-gray-950 border-gray-800 p-0 overflow-hidden flex flex-col">
        <div className="relative flex-1 bg-black">
          {/* Remote Video */}
          {remoteStream ? (
            <video 
              ref={remoteVideoRef} 
              autoPlay 
              playsInline 
              className="w-full h-full object-cover"
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-gray-500">
              {callStatus === 'calling' ? 'Calling...' : 'Waiting for video...'}
            </div>
          )}

          {/* Local Video (PIP) */}
          <div className="absolute bottom-4 right-4 w-48 h-36 bg-gray-900 rounded-lg overflow-hidden border border-gray-700 shadow-xl">
            <video 
              ref={localVideoRef} 
              autoPlay 
              playsInline 
              muted 
              className="w-full h-full object-cover"
            />
          </div>

          {/* Incoming Call Overlay */}
          {callStatus === 'ringing' && isIncoming && (
            <div className="absolute inset-0 bg-black/80 flex flex-col items-center justify-center z-50">
              <div className="text-2xl text-white mb-8">Incoming Video Call</div>
              <div className="flex gap-8">
                <Button 
                  size="lg" 
                  className="bg-green-600 hover:bg-green-700 rounded-full w-16 h-16 p-0"
                  onClick={acceptCall}
                >
                  <Phone className="w-8 h-8" />
                </Button>
                <Button 
                  size="lg" 
                  className="bg-red-600 hover:bg-red-700 rounded-full w-16 h-16 p-0"
                  onClick={endCall}
                >
                  <PhoneOff className="w-8 h-8" />
                </Button>
              </div>
            </div>
          )}
        </div>

        {/* Controls Bar */}
        <div className="h-20 bg-gray-900 flex items-center justify-center gap-6 border-t border-gray-800">
          <Button 
            variant="ghost" 
            size="icon"
            className={`rounded-full w-12 h-12 ${isMuted ? 'bg-red-500/20 text-red-500' : 'bg-gray-800 text-white'}`}
            onClick={toggleMute}
          >
            {isMuted ? <MicOff /> : <Mic />}
          </Button>
          
          <Button 
            variant="destructive" 
            size="icon"
            className="rounded-full w-16 h-16 bg-red-600 hover:bg-red-700"
            onClick={endCall}
          >
            <PhoneOff className="w-8 h-8" />
          </Button>

          <Button 
            variant="ghost" 
            size="icon"
            className={`rounded-full w-12 h-12 ${isVideoOff ? 'bg-red-500/20 text-red-500' : 'bg-gray-800 text-white'}`}
            onClick={toggleVideo}
          >
            {isVideoOff ? <VideoOff /> : <Video />}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
