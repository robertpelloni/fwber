'use client';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface LifestyleProps {
  formData: any;
  handlePreferenceChange: (field: string, value: any) => void;
  handleInputChange: (field: string, value: any) => void;
}

export default function Lifestyle({ formData, handlePreferenceChange, handleInputChange }: LifestyleProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Lifestyle Preferences</CardTitle>
      </CardHeader>
      <CardContent className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div>
          <label htmlFor="occupation" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Occupation
          </label>
          <input
            type="text"
            id="occupation"
            value={formData.preferences.occupation}
            onChange={(e) => handlePreferenceChange('occupation', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="Your job or profession"
          />
        </div>

        <div>
          <label htmlFor="smoking_status" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Smoking
          </label>
          <select
            id="smoking_status"
            value={formData.smoking_status}
            onChange={(e) => handleInputChange('smoking_status', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="">Select preference</option>
            <option value="non-smoker">Non-smoker</option>
            <option value="occasional">Occasional smoker</option>
            <option value="regular">Regular smoker</option>
            <option value="social">Social smoker</option>
            <option value="trying-to-quit">Trying to quit</option>
          </select>
        </div>

        <div>
          <label htmlFor="drinking_status" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Drinking
          </label>
          <select
            id="drinking_status"
            value={formData.drinking_status}
            onChange={(e) => handleInputChange('drinking_status', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="">Select preference</option>
            <option value="non-drinker">Non-drinker</option>
            <option value="occasional">Occasional drinker</option>
            <option value="regular">Regular drinker</option>
            <option value="social">Social drinker</option>
            <option value="sober">Sober</option>
          </select>
        </div>

        <div>
          <label htmlFor="cannabis_status" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Cannabis
          </label>
          <select
            id="cannabis_status"
            value={formData.cannabis_status}
            onChange={(e) => handleInputChange('cannabis_status', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="">Select preference</option>
            <option value="non-user">Non-user</option>
            <option value="occasional">Occasional user</option>
            <option value="regular">Regular user</option>
            <option value="medical">Medical user</option>
          </select>
        </div>

        <div>
          <label htmlFor="exercise_habits" className="block text-sm font-medium text-gray-700">
            Exercise
          </label>
          <select
            id="exercise_habits"
            value={formData.exercise_habits}
            onChange={(e) => handleInputChange('exercise_habits', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="">Select preference</option>
            <option value="daily">Daily</option>
            <option value="several-times-week">Several times a week</option>
            <option value="weekly">Weekly</option>
            <option value="occasional">Occasional</option>
            <option value="rarely">Rarely</option>
            <option value="never">Never</option>
          </select>
        </div>

        <div>
          <label htmlFor="dietary_preferences" className="block text-sm font-medium text-gray-700">
            Diet
          </label>
          <select
            id="dietary_preferences"
            value={formData.dietary_preferences}
            onChange={(e) => handleInputChange('dietary_preferences', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="">Select preference</option>
            <option value="omnivore">Omnivore</option>
            <option value="vegetarian">Vegetarian</option>
            <option value="vegan">Vegan</option>
            <option value="pescatarian">Pescatarian</option>
            <option value="keto">Keto</option>
            <option value="paleo">Paleo</option>
            <option value="gluten-free">Gluten-free</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div>
          <label htmlFor="sleep_habits" className="block text-sm font-medium text-gray-700">
            Sleep Habits
          </label>
          <select
            id="sleep_habits"
            value={formData.sleep_habits}
            onChange={(e) => handleInputChange('sleep_habits', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="">Select preference</option>
            <option value="early-riser">Early riser</option>
            <option value="night-owl">Night owl</option>
            <option value="irregular">Irregular schedule</option>
            <option value="average">Average (11pm-7am)</option>
          </select>
        </div>

        <div>
          <label htmlFor="pets" className="block text-sm font-medium text-gray-700">
            Pets
          </label>
          <select
            id="pets"
            value={formData.pets?.[0] || ''}
            onChange={(e) => handleInputChange('pets', e.target.value ? [e.target.value] : [])}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="">Select preference</option>
            <option value="love-pets">Love pets</option>
            <option value="have-pets">Have pets</option>
            <option value="allergic">Allergic to pets</option>
            <option value="prefer-no-pets">Prefer no pets</option>
            <option value="neutral">Neutral</option>
          </select>
        </div>

        <div>
          <label htmlFor="children" className="block text-sm font-medium text-gray-700">
            Children
          </label>
          <select
            id="children"
            value={formData.children}
            onChange={(e) => handleInputChange('children', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="">Select preference</option>
            <option value="have-children">Have children</option>
            <option value="want-children">Want children</option>
            <option value="dont-want-children">Don&apos;t want children</option>
            <option value="maybe-someday">Maybe someday</option>
            <option value="not-sure">Not sure</option>
          </select>
        </div>
      </CardContent>
    </Card>
  );
}
