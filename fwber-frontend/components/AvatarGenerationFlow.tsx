'use client';

import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Loader2, Sparkles, RefreshCw, Check, X } from 'lucide-react';
import axios from 'axios';

interface AvatarGenerationProps {
  userId: number;
  currentAvatarUrl?: string;
  onComplete?: (avatarUrl: string) => void;
}

interface PhysicalProfile {
  age?: number;
  gender?: string;
  ethnicity?: string;
  body_type?: string;
  hair_color?: string;
  eye_color?: string;
  height_cm?: number;
}

export default function AvatarGenerationFlow({ 
  userId, 
  currentAvatarUrl,
  onComplete 
}: AvatarGenerationProps) {
  const [step, setStep] = useState<'profile' | 'generating' | 'preview' | 'complete'>('profile');
  const [profile, setProfile] = useState<PhysicalProfile>({});
  const [generatedAvatar, setGeneratedAvatar] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const queryClient = useQueryClient();

  const generateMutation = useMutation({
    mutationFn: async (profileData: PhysicalProfile) => {
      const token = localStorage.getItem('auth_token');
      const response = await axios.post(
        `${process.env.NEXT_PUBLIC_API_URL}/physical-profile/avatar/request`,
        profileData,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      return response.data;
    },
    onSuccess: (data) => {
      setGeneratedAvatar(data.avatar_url);
      setStep('preview');
      setError(null);
    },
    onError: (err: any) => {
      setError(err.response?.data?.message || 'Failed to generate avatar');
      setStep('profile');
    },
  });

  const acceptMutation = useMutation({
    mutationFn: async () => {
      const token = localStorage.getItem('auth_token');
      await axios.put(
        `${process.env.NEXT_PUBLIC_API_URL}/physical-profile`,
        { ...profile, avatar_url: generatedAvatar },
        { headers: { Authorization: `Bearer ${token}` } }
      );
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user'] });
      setStep('complete');
      if (onComplete && generatedAvatar) {
        onComplete(generatedAvatar);
      }
    },
  });

  const handleGenerate = () => {
    setStep('generating');
    setError(null);
    generateMutation.mutate(profile);
  };

  const handleRegenerate = () => {
    setStep('generating');
    generateMutation.mutate(profile);
  };

  const handleAccept = () => {
    acceptMutation.mutate();
  };

  const handleReject = () => {
    setStep('profile');
    setGeneratedAvatar(null);
  };

  const isProfileComplete = () => {
    return profile.age && profile.gender && profile.ethnicity && 
           profile.body_type && profile.hair_color && profile.eye_color;
  };

  return (
    <div className="max-w-2xl mx-auto p-6">
      {/* Progress Indicator */}
      <div className="mb-8">
        <div className="flex items-center justify-between mb-2">
          <StepIndicator active={step === 'profile'} completed={step !== 'profile'} label="Profile" />
          <div className="flex-1 h-0.5 bg-gray-200 mx-2">
            <div className={`h-full bg-purple-600 transition-all duration-300 ${
              step !== 'profile' ? 'w-full' : 'w-0'
            }`} />
          </div>
          <StepIndicator active={step === 'generating'} completed={step === 'preview' || step === 'complete'} label="Generate" />
          <div className="flex-1 h-0.5 bg-gray-200 mx-2">
            <div className={`h-full bg-purple-600 transition-all duration-300 ${
              step === 'preview' || step === 'complete' ? 'w-full' : 'w-0'
            }`} />
          </div>
          <StepIndicator active={step === 'preview'} completed={step === 'complete'} label="Preview" />
        </div>
      </div>

      {/* Error Display */}
      {error && (
        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
          <X className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
          <div>
            <h4 className="font-semibold text-red-900">Generation Failed</h4>
            <p className="text-sm text-red-700 mt-1">{error}</p>
          </div>
        </div>
      )}

      {/* Step 1: Physical Profile Input */}
      {step === 'profile' && (
        <div className="space-y-6">
          <div>
            <h2 className="text-2xl font-bold text-gray-900 mb-2">Create Your Avatar</h2>
            <p className="text-gray-600">
              Describe your physical characteristics to generate a personalized AI avatar.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Age</label>
              <input
                type="number"
                min="18"
                max="100"
                value={profile.age || ''}
                onChange={(e) => setProfile({ ...profile, age: parseInt(e.target.value) })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                placeholder="25"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Gender</label>
              <select
                value={profile.gender || ''}
                onChange={(e) => setProfile({ ...profile, gender: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              >
                <option value="">Select gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="non-binary">Non-binary</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Ethnicity</label>
              <select
                value={profile.ethnicity || ''}
                onChange={(e) => setProfile({ ...profile, ethnicity: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              >
                <option value="">Select ethnicity</option>
                <option value="asian">Asian</option>
                <option value="black">Black</option>
                <option value="hispanic">Hispanic/Latino</option>
                <option value="white">White</option>
                <option value="middle_eastern">Middle Eastern</option>
                <option value="mixed">Mixed</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Body Type</label>
              <select
                value={profile.body_type || ''}
                onChange={(e) => setProfile({ ...profile, body_type: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              >
                <option value="">Select body type</option>
                <option value="slim">Slim</option>
                <option value="athletic">Athletic</option>
                <option value="average">Average</option>
                <option value="curvy">Curvy</option>
                <option value="heavyset">Heavyset</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Hair Color</label>
              <select
                value={profile.hair_color || ''}
                onChange={(e) => setProfile({ ...profile, hair_color: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              >
                <option value="">Select hair color</option>
                <option value="black">Black</option>
                <option value="brown">Brown</option>
                <option value="blonde">Blonde</option>
                <option value="red">Red</option>
                <option value="gray">Gray</option>
                <option value="bald">Bald</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Eye Color</label>
              <select
                value={profile.eye_color || ''}
                onChange={(e) => setProfile({ ...profile, eye_color: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              >
                <option value="">Select eye color</option>
                <option value="brown">Brown</option>
                <option value="blue">Blue</option>
                <option value="green">Green</option>
                <option value="hazel">Hazel</option>
                <option value="gray">Gray</option>
                <option value="amber">Amber</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Height (cm)</label>
              <input
                type="number"
                min="140"
                max="220"
                value={profile.height_cm || ''}
                onChange={(e) => setProfile({ ...profile, height_cm: parseInt(e.target.value) })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                placeholder="170"
              />
            </div>
          </div>

          <button
            onClick={handleGenerate}
            disabled={!isProfileComplete()}
            className="w-full flex items-center justify-center gap-2 px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors font-medium"
          >
            <Sparkles className="w-5 h-5" />
            Generate Avatar
          </button>

          <p className="text-xs text-gray-500 text-center">
            Your avatar will be AI-generated based on your description. No photos required.
          </p>
        </div>
      )}

      {/* Step 2: Generating */}
      {step === 'generating' && (
        <div className="text-center py-12">
          <Loader2 className="w-16 h-16 text-purple-600 animate-spin mx-auto mb-4" />
          <h3 className="text-xl font-semibold text-gray-900 mb-2">Generating Your Avatar</h3>
          <p className="text-gray-600">
            Our AI is creating a unique avatar based on your profile...
          </p>
          <div className="mt-8 flex justify-center gap-2">
            <div className="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style={{ animationDelay: '0ms' }} />
            <div className="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style={{ animationDelay: '150ms' }} />
            <div className="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style={{ animationDelay: '300ms' }} />
          </div>
        </div>
      )}

      {/* Step 3: Preview & Accept/Reject */}
      {step === 'preview' && generatedAvatar && (
        <div className="space-y-6">
          <div className="text-center">
            <h2 className="text-2xl font-bold text-gray-900 mb-2">Your New Avatar</h2>
            <p className="text-gray-600">
              Review your AI-generated avatar. You can regenerate if you'd like a different result.
            </p>
          </div>

          <div className="flex justify-center">
            <div className="relative">
              <img
                src={generatedAvatar}
                alt="Generated avatar"
                className="w-64 h-64 rounded-full object-cover border-4 border-purple-200 shadow-lg"
              />
              <div className="absolute -bottom-2 -right-2 bg-purple-600 text-white p-2 rounded-full">
                <Sparkles className="w-5 h-5" />
              </div>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <button
              onClick={handleRegenerate}
              disabled={generateMutation.isPending}
              className="flex items-center justify-center gap-2 px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:border-purple-600 hover:text-purple-600 disabled:opacity-50 transition-colors font-medium"
            >
              <RefreshCw className="w-5 h-5" />
              Regenerate
            </button>

            <button
              onClick={handleAccept}
              disabled={acceptMutation.isPending}
              className="flex items-center justify-center gap-2 px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 transition-colors font-medium"
            >
              <Check className="w-5 h-5" />
              {acceptMutation.isPending ? 'Saving...' : 'Accept Avatar'}
            </button>
          </div>

          <button
            onClick={handleReject}
            className="w-full text-sm text-gray-500 hover:text-gray-700 transition-colors"
          >
            Start over with different profile
          </button>
        </div>
      )}

      {/* Step 4: Complete */}
      {step === 'complete' && (
        <div className="text-center py-12">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <Check className="w-10 h-10 text-green-600" />
          </div>
          <h3 className="text-2xl font-bold text-gray-900 mb-2">Avatar Created!</h3>
          <p className="text-gray-600 mb-6">
            Your AI-generated avatar is now active on your profile.
          </p>
          {generatedAvatar && (
            <img
              src={generatedAvatar}
              alt="Your avatar"
              className="w-32 h-32 rounded-full object-cover mx-auto border-4 border-green-200"
            />
          )}
        </div>
      )}
    </div>
  );
}

function StepIndicator({ active, completed, label }: { active: boolean; completed: boolean; label: string }) {
  return (
    <div className="flex flex-col items-center">
      <div className={`w-10 h-10 rounded-full flex items-center justify-center border-2 transition-all ${
        completed ? 'bg-purple-600 border-purple-600' : 
        active ? 'border-purple-600 bg-white' : 
        'border-gray-300 bg-white'
      }`}>
        {completed ? (
          <Check className="w-5 h-5 text-white" />
        ) : (
          <span className={`text-sm font-semibold ${active ? 'text-purple-600' : 'text-gray-400'}`}>
            {label === 'Profile' ? '1' : label === 'Generate' ? '2' : '3'}
          </span>
        )}
      </div>
      <span className={`text-xs mt-1 font-medium ${
        active || completed ? 'text-purple-600' : 'text-gray-400'
      }`}>
        {label}
      </span>
    </div>
  );
}
