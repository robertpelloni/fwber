"use client";

import { useState, useEffect, useCallback } from 'react';
import { useAuth } from '@/lib/auth-context';
import { physicalProfileApi, type PhysicalProfile } from '@/lib/api/physical-profile';
import { Ruler, User, Palette, Shirt, Activity, Wand2 } from 'lucide-react';

export default function PhysicalProfileEditor() {
  const { token } = useAuth();
  const [profile, setProfile] = useState<PhysicalProfile>({});
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
  const [avatarStyle, setAvatarStyle] = useState('realistic');
  const [isGeneratingAvatar, setIsGeneratingAvatar] = useState(false);

  const loadProfile = useCallback(async () => {
    if (!token) return;
    try {
      setIsLoading(true);
      const res = await physicalProfileApi.get(token);
      setProfile(res.data || {});
    } catch (err) {
      console.error(err);
      setMessage({ type: 'error', text: 'Failed to load physical profile' });
    } finally {
      setIsLoading(false);
    }
  }, [token]);

  useEffect(() => {
    if (token) {
      loadProfile();
    }
  }, [token, loadProfile]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) return;

    try {
      setIsSaving(true);
      setMessage(null);
      await physicalProfileApi.upsert(token, profile);
      setMessage({ type: 'success', text: 'Physical profile updated successfully' });
    } catch (err) {
      console.error(err);
      setMessage({ type: 'error', text: 'Failed to update physical profile' });
    } finally {
      setIsSaving(false);
    }
  };

  const handleAvatarRequest = async () => {
    if (!token) return;
    try {
      setIsGeneratingAvatar(true);
      setMessage(null);
      await physicalProfileApi.requestAvatar(token, avatarStyle);
      setMessage({ type: 'success', text: 'Avatar generation requested!' });
      loadProfile(); // Reload to get status
    } catch (err) {
      console.error(err);
      setMessage({ type: 'error', text: 'Failed to request avatar generation' });
    } finally {
      setIsGeneratingAvatar(false);
    }
  };

  const handleChange = (field: keyof PhysicalProfile, value: any) => {
    setProfile(prev => ({ ...prev, [field]: value }));
  };

  if (isLoading) return <div className="p-4 text-center">Loading physical profile...</div>;

  return (
    <div className="bg-white shadow rounded-lg p-6">
      <h2 className="text-2xl font-bold mb-6 flex items-center gap-2">
        <User className="h-6 w-6 text-blue-600" /> Physical Attributes
      </h2>

      {message && (
        <div className={`p-4 mb-6 rounded-md ${message.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>
          {message.text}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Basic Stats */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label htmlFor="height_cm" className="block text-sm font-medium text-gray-700 mb-1">Height (cm)</label>
            <div className="relative rounded-md shadow-sm">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Ruler className="h-4 w-4 text-gray-400" />
              </div>
              <input
                type="number"
                id="height_cm"
                min="80"
                max="250"
                value={profile.height_cm || ''}
                onChange={(e) => handleChange('height_cm', parseInt(e.target.value) || undefined)}
                className="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                placeholder="175"
              />
            </div>
          </div>

          <div>
            <label htmlFor="body_type" className="block text-sm font-medium text-gray-700 mb-1">Body Type</label>
            <select
              id="body_type"
              value={profile.body_type || ''}
              onChange={(e) => handleChange('body_type', e.target.value)}
              className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
            >
              <option value="">Select...</option>
              <option value="slim">Slim</option>
              <option value="athletic">Athletic</option>
              <option value="average">Average</option>
              <option value="curvy">Curvy</option>
              <option value="muscular">Muscular</option>
              <option value="plus-size">Plus Size</option>
            </select>
          </div>
        </div>

        {/* Appearance Details */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label htmlFor="hair_color" className="block text-sm font-medium text-gray-700 mb-1">Hair Color</label>
            <input
              type="text"
              id="hair_color"
              value={profile.hair_color || ''}
              onChange={(e) => handleChange('hair_color', e.target.value)}
              className="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
            />
          </div>
          <div>
            <label htmlFor="eye_color" className="block text-sm font-medium text-gray-700 mb-1">Eye Color</label>
            <input
              type="text"
              id="eye_color"
              value={profile.eye_color || ''}
              onChange={(e) => handleChange('eye_color', e.target.value)}
              className="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
            />
          </div>
          <div>
            <label htmlFor="skin_tone" className="block text-sm font-medium text-gray-700 mb-1">Skin Tone</label>
            <input
              type="text"
              id="skin_tone"
              value={profile.skin_tone || ''}
              onChange={(e) => handleChange('skin_tone', e.target.value)}
              className="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
            />
          </div>
        </div>

        {/* Features */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label htmlFor="facial_hair" className="block text-sm font-medium text-gray-700 mb-1">Facial Hair</label>
            <input
              type="text"
              id="facial_hair"
              value={profile.facial_hair || ''}
              onChange={(e) => handleChange('facial_hair', e.target.value)}
              className="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
              placeholder="e.g. Beard, Mustache, None"
            />
          </div>
          <div>
            <label htmlFor="ethnicity" className="block text-sm font-medium text-gray-700 mb-1">Ethnicity</label>
            <input
              type="text"
              id="ethnicity"
              value={profile.ethnicity || ''}
              onChange={(e) => handleChange('ethnicity', e.target.value)}
              className="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
            />
          </div>
        </div>

        {/* Toggles */}
        <div className="flex gap-6">
          <div className="flex items-center">
            <input
              id="tattoos"
              type="checkbox"
              checked={profile.tattoos || false}
              onChange={(e) => handleChange('tattoos', e.target.checked)}
              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label htmlFor="tattoos" className="ml-2 block text-sm text-gray-900">Has Tattoos</label>
          </div>
          <div className="flex items-center">
            <input
              id="piercings"
              type="checkbox"
              checked={profile.piercings || false}
              onChange={(e) => handleChange('piercings', e.target.checked)}
              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label htmlFor="piercings" className="ml-2 block text-sm text-gray-900">Has Piercings</label>
          </div>
        </div>

        {/* Lifestyle & Style */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label htmlFor="fitness_level" className="block text-sm font-medium text-gray-700 mb-1">
              <div className="flex items-center gap-1"><Activity className="h-4 w-4" /> Fitness Level</div>
            </label>
            <select
              id="fitness_level"
              value={profile.fitness_level || ''}
              onChange={(e) => handleChange('fitness_level', e.target.value)}
              className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
            >
              <option value="">Select...</option>
              <option value="low">Low</option>
              <option value="average">Average</option>
              <option value="fit">Fit</option>
              <option value="athletic">Athletic</option>
            </select>
          </div>
          <div>
            <label htmlFor="dominant_hand" className="block text-sm font-medium text-gray-700 mb-1">Dominant Hand</label>
            <select
              id="dominant_hand"
              value={profile.dominant_hand || ''}
              onChange={(e) => handleChange('dominant_hand', e.target.value)}
              className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
            >
              <option value="">Select...</option>
              <option value="right">Right</option>
              <option value="left">Left</option>
              <option value="ambi">Ambidextrous</option>
            </select>
          </div>
          <div>
            <label htmlFor="clothing_style" className="block text-sm font-medium text-gray-700 mb-1">
              <div className="flex items-center gap-1"><Shirt className="h-4 w-4" /> Clothing Style</div>
            </label>
            <input
              type="text"
              id="clothing_style"
              value={profile.clothing_style || ''}
              onChange={(e) => handleChange('clothing_style', e.target.value)}
              className="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
              placeholder="e.g. Casual, Formal, Streetwear"
            />
          </div>
        </div>

        {/* Avatar Generation Section */}
        <div className="border-t pt-6 mt-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
            <Wand2 className="h-5 w-5 text-purple-600" /> AI Avatar Generation
          </h3>
          
          <div className="bg-purple-50 p-4 rounded-md mb-4">
            <label htmlFor="avatar_prompt" className="block text-sm font-medium text-purple-900 mb-1">Avatar Prompt</label>
            <p className="text-xs text-purple-700 mb-2">Describe yourself for the AI generator. This will be used to create your avatar.</p>
            <textarea
              id="avatar_prompt"
              rows={3}
              value={profile.avatar_prompt || ''}
              onChange={(e) => handleChange('avatar_prompt', e.target.value)}
              className="shadow-sm focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm border-purple-200 rounded-md"
              placeholder="A young man with short brown hair, blue eyes, wearing a leather jacket..."
            />
          </div>

          <div className="flex items-end gap-4">
            <div className="flex-grow">
              <label htmlFor="avatar_style" className="block text-sm font-medium text-gray-700 mb-1">Art Style</label>
              <select
                id="avatar_style"
                value={avatarStyle}
                onChange={(e) => setAvatarStyle(e.target.value)}
                className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm rounded-md"
              >
                <option value="realistic">Realistic</option>
                <option value="anime">Anime</option>
                <option value="fantasy">Fantasy</option>
                <option value="sci-fi">Sci-Fi</option>
                <option value="cartoon">Cartoon</option>
                <option value="pixel-art">Pixel Art</option>
              </select>
            </div>
            <button
              type="button"
              onClick={handleAvatarRequest}
              disabled={isGeneratingAvatar || !profile.avatar_prompt}
              className="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-md transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              {isGeneratingAvatar ? 'Generating...' : 'Generate Avatar'}
            </button>
          </div>
          {profile.avatar_status && (
            <p className="mt-2 text-sm text-gray-500">Status: <span className="font-medium capitalize">{profile.avatar_status}</span></p>
          )}
        </div>

        <div className="flex justify-end pt-4 border-t">
          <button
            type="submit"
            disabled={isSaving}
            className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isSaving ? 'Saving...' : 'Save Physical Profile'}
          </button>
        </div>
      </form>
    </div>
  );
}
