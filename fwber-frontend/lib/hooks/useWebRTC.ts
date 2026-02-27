import { useEffect, useRef, useState, useCallback } from 'react';
import { apiClient } from '../api/client';

interface UseWebRTCProps {
    roomId: string;
    userId: number; // The target peer to connect to
}

export function useWebRTC({ roomId, userId }: UseWebRTCProps) {
    const [localStream, setLocalStream] = useState<MediaStream | null>(null);
    const [remoteStream, setRemoteStream] = useState<MediaStream | null>(null);
    const peerConnectionRef = useRef<RTCPeerConnection | null>(null);

    const initializePeerConnection = useCallback(() => {
        const pc = new RTCPeerConnection({
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                // STUN/TURN servers would go here in production
            ]
        });

        pc.onicecandidate = async (event) => {
            if (event.candidate) {
                // Send ICE candidate to peer via Laravel Echo signaling route
                await apiClient.post(`/audio-rooms/${roomId}/signal`, {
                    target_user_id: userId,
                    type: 'ice-candidate',
                    payload: event.candidate
                });
            }
        };

        pc.ontrack = (event) => {
            if (event.streams && event.streams[0]) {
                setRemoteStream(event.streams[0]);
            }
        };

        peerConnectionRef.current = pc;
        return pc;
    }, [roomId, userId]);

    const startLocalAudio = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
            setLocalStream(stream);

            if (peerConnectionRef.current) {
                stream.getTracks().forEach((track) => {
                    peerConnectionRef.current?.addTrack(track, stream);
                });
            }
            return stream;
        } catch (err) {
            console.error('Error accessing microphone', err);
            throw err;
        }
    };

    const stopLocalAudio = () => {
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
            setLocalStream(null);
        }
    };

    const toggleMute = () => {
        if (localStream) {
            localStream.getAudioTracks().forEach(track => {
                track.enabled = !track.enabled;
            });
        }
    };

    const createOffer = async () => {
        if (!peerConnectionRef.current) return;
        try {
            const offer = await peerConnectionRef.current.createOffer();
            await peerConnectionRef.current.setLocalDescription(offer);

            await apiClient.post(`/audio-rooms/${roomId}/signal`, {
                target_user_id: userId,
                type: 'offer',
                payload: offer
            });
        } catch (err) {
            console.error('Error creating offer', err);
        }
    };

    const handleReceiveSignal = async (signalData: { type: string, payload: any }) => {
        if (!peerConnectionRef.current) return;

        try {
            if (signalData.type === 'offer') {
                await peerConnectionRef.current.setRemoteDescription(new RTCSessionDescription(signalData.payload));
                const answer = await peerConnectionRef.current.createAnswer();
                await peerConnectionRef.current.setLocalDescription(answer);

                await apiClient.post(`/audio-rooms/${roomId}/signal`, {
                    target_user_id: userId,
                    type: 'answer',
                    payload: answer
                });
            } else if (signalData.type === 'answer') {
                await peerConnectionRef.current.setRemoteDescription(new RTCSessionDescription(signalData.payload));
            } else if (signalData.type === 'ice-candidate') {
                await peerConnectionRef.current.addIceCandidate(new RTCIceCandidate(signalData.payload));
            }
        } catch (err) {
            console.error('Error handling signal', err);
        }
    };

    useEffect(() => {
        initializePeerConnection();
        return () => {
            peerConnectionRef.current?.close();
            stopLocalAudio();
        };
    }, [initializePeerConnection]);

    return {
        localStream,
        remoteStream,
        startLocalAudio,
        stopLocalAudio,
        toggleMute,
        createOffer,
        handleReceiveSignal
    };
}
