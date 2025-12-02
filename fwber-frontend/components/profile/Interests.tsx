'use client';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface InterestsProps {
  formData: any;
  handleArrayPreferenceChange: (field: string, value: string, checked: boolean) => void;
}

export default function Interests({ formData, handleArrayPreferenceChange }: InterestsProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Interests & Hobbies</CardTitle>
      </CardHeader>
      <CardContent className="space-y-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Hobbies
          </label>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3" id="hobbies">
            {['reading', 'writing', 'photography', 'cooking', 'gardening', 'art', 'music', 'dancing', 'hiking', 'traveling', 'gaming', 'sports', 'fitness', 'yoga', 'meditation', 'volunteering'].map((hobby) => (
              <label key={hobby} className="flex items-center">
                <input
                  type="checkbox"
                  checked={formData.preferences.hobbies.includes(hobby)}
                  onChange={(e) => handleArrayPreferenceChange('hobbies', hobby, e.target.checked)}
                  className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                />
                <span className="ml-2 text-sm text-gray-700 capitalize">{hobby}</span>
              </label>
            ))}
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-3">
            Music Genres
          </label>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            {['pop', 'rock', 'hip-hop', 'country', 'jazz', 'classical', 'electronic', 'indie', 'r&b', 'reggae', 'blues', 'folk', 'metal', 'punk', 'alternative', 'world'].map((genre) => (
              <label key={genre} className="flex items-center">
                <input
                  type="checkbox"
                  checked={formData.preferences.music.includes(genre)}
                  onChange={(e) => handleArrayPreferenceChange('music', genre, e.target.checked)}
                  className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                />
                <span className="ml-2 text-sm text-gray-700 capitalize">{genre}</span>
              </label>
            ))}
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-3">
            Sports
          </label>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            {['football', 'basketball', 'soccer', 'tennis', 'golf', 'baseball', 'hockey', 'swimming', 'running', 'cycling', 'yoga', 'pilates', 'boxing', 'martial-arts', 'skiing', 'snowboarding'].map((sport) => (
              <label key={sport} className="flex items-center">
                <input
                  type="checkbox"
                  checked={formData.preferences.sports.includes(sport)}
                  onChange={(e) => handleArrayPreferenceChange('sports', sport, e.target.checked)}
                  className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                />
                <span className="ml-2 text-sm text-gray-700 capitalize">{sport.replace('-', ' ')}</span>
              </label>
            ))}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
