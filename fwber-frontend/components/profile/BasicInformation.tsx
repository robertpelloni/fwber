'use client';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface BasicInformationProps {
  formData: any;
  handleInputChange: (field: string, value: any) => void;
  handleLocationChange: (field: string, value: any) => void;
}

export default function BasicInformation({ formData, handleInputChange, handleLocationChange }: BasicInformationProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Basic Information</CardTitle>
      </CardHeader>
      <CardContent className="space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label htmlFor="display_name" className="block text-sm font-medium text-gray-700">
              Display Name
            </label>
            <input
              type="text"
              id="display_name"
              value={formData.display_name}
              onChange={(e) => handleInputChange('display_name', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="How would you like to be called?"
            />
          </div>

          <div>
            <label htmlFor="date_of_birth" className="block text-sm font-medium text-gray-700">
              Date of Birth
            </label>
            <input
              type="date"
              id="date_of_birth"
              value={formData.date_of_birth || ''}
              onChange={(e) => handleInputChange('date_of_birth', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            />
          </div>

          <div>
            <label htmlFor="gender" className="block text-sm font-medium text-gray-700">
              Gender
            </label>
            <select
              id="gender"
              value={formData.gender}
              onChange={(e) => handleInputChange('gender', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option value="">Select gender</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="non-binary">Non-binary</option>
              <option value="mtf">Trans Woman</option>
              <option value="ftm">Trans Man</option>
              <option value="other">Other</option>
              <option value="prefer-not-to-say">Prefer not to say</option>
            </select>
          </div>

          <div>
            <label htmlFor="pronouns" className="block text-sm font-medium text-gray-700">
              Pronouns
            </label>
            <select
              id="pronouns"
              value={formData.pronouns}
              onChange={(e) => handleInputChange('pronouns', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option value="">Select pronouns</option>
              <option value="he/him">He/Him</option>
              <option value="she/her">She/Her</option>
              <option value="they/them">They/Them</option>
              <option value="he/they">He/They</option>
              <option value="she/they">She/They</option>
              <option value="other">Other</option>
              <option value="prefer-not-to-say">Prefer not to say</option>
            </select>
          </div>

          <div>
            <label htmlFor="sexual_orientation" className="block text-sm font-medium text-gray-700">
              Sexual Orientation
            </label>
            <select
              id="sexual_orientation"
              value={formData.sexual_orientation}
              onChange={(e) => handleInputChange('sexual_orientation', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option value="">Select orientation</option>
              <option value="straight">Straight</option>
              <option value="gay">Gay</option>
              <option value="lesbian">Lesbian</option>
              <option value="bisexual">Bisexual</option>
              <option value="pansexual">Pansexual</option>
              <option value="asexual">Asexual</option>
              <option value="demisexual">Demisexual</option>
              <option value="queer">Queer</option>
              <option value="questioning">Questioning</option>
              <option value="other">Other</option>
              <option value="prefer-not-to-say">Prefer not to say</option>
            </select>
          </div>

          <div>
            <label htmlFor="relationship_style" className="block text-sm font-medium text-gray-700">
              Relationship Style
            </label>
            <select
              id="relationship_style"
              value={formData.relationship_style}
              onChange={(e) => handleInputChange('relationship_style', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option value="">Select relationship style</option>
              <option value="monogamous">Monogamous</option>
              <option value="non-monogamous">Non-monogamous</option>
              <option value="polyamorous">Polyamorous</option>
              <option value="open">Open Relationship</option>
              <option value="swinger">Swinger</option>
              <option value="other">Other</option>
              <option value="prefer-not-to-say">Prefer not to say</option>
            </select>
          </div>

          <div>
            <label htmlFor="max_distance" className="block text-sm font-medium text-gray-700">
              Maximum Distance (miles)
            </label>
            <input
              type="number"
              id="max_distance"
              min="1"
              max="100"
              value={formData.location.max_distance}
              onChange={(e) => handleLocationChange('max_distance', parseInt(e.target.value))}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            />
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
