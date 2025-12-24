'use client';

import { useState } from 'react';
import { useAuth } from '@/lib/auth-context';
import { Lock } from 'lucide-react';
import Link from 'next/link';

interface MatchFilterProps {
  onFilterChange: (filters: any) => void;
}

export default function MatchFilter({ onFilterChange }: MatchFilterProps) {
  const { user } = useAuth();
  // Threshold for Premium Filters: 100 FWB tokens
  const PREMIUM_THRESHOLD = 100;
  const isPremium = (user?.token_balance || 0) >= PREMIUM_THRESHOLD;

  const [filters, setFilters] = useState({
    // Standard
    smoking: '',
    drinking: '',
    body_type: '',
    height_min: '',
    // Premium
    cannabis: '',
    diet: '',
    has_pets: '',
    has_children: '',
    wants_children: '',
    politics: '',
    religion: '',
    zodiac: '',
  });

  const handleInputChange = (field: string, value: any) => {
    setFilters((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const handleApplyFilters = () => {
    onFilterChange(filters);
  };

  const PremiumOverlay = () => (
    <div className="absolute inset-0 bg-gray-100 bg-opacity-60 backdrop-blur-[1px] flex flex-col items-center justify-center z-10 rounded-lg border-2 border-dashed border-gray-300">
      <Lock className="h-8 w-8 text-gray-500 mb-2" />
      <p className="text-sm font-semibold text-gray-700">Premium Filters</p>
      <p className="text-xs text-gray-500 text-center px-4 mb-3">
        Hold {PREMIUM_THRESHOLD}+ FWB Tokens to unlock advanced lifestyle filters.
      </p>
      <Link
        href="/wallet"
        className="text-xs bg-indigo-600 hover:bg-indigo-700 text-white py-1.5 px-3 rounded-full transition-colors"
      >
        Get Tokens
      </Link>
    </div>
  );

  return (
    <div className="space-y-6">
      {/* Standard Filters Section */}
      <div className="p-4 bg-white rounded-lg shadow-sm border border-gray-200">
        <h3 className="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Basic Preferences</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label htmlFor="smoking" className="block text-sm font-medium text-gray-700">Smoking</label>
            <select
              id="smoking"
              value={filters.smoking}
              onChange={(e) => handleInputChange('smoking', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
              <option value="">Any</option>
              <option value="non-smoker">Non-smoker</option>
              <option value="occasional">Occasional</option>
              <option value="regular">Regular</option>
              <option value="social">Social</option>
              <option value="trying-to-quit">Trying to quit</option>
            </select>
          </div>

          <div>
            <label htmlFor="drinking" className="block text-sm font-medium text-gray-700">Drinking</label>
            <select
              id="drinking"
              value={filters.drinking}
              onChange={(e) => handleInputChange('drinking', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
              <option value="">Any</option>
              <option value="non-drinker">Non-drinker</option>
              <option value="occasional">Occasional</option>
              <option value="regular">Regular</option>
              <option value="social">Social</option>
              <option value="sober">Sober</option>
            </select>
          </div>

          <div>
            <label htmlFor="body_type" className="block text-sm font-medium text-gray-700">Body Type</label>
            <select
              id="body_type"
              value={filters.body_type}
              onChange={(e) => handleInputChange('body_type', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
              <option value="">Any</option>
              <option value="slim">Slim</option>
              <option value="athletic">Athletic</option>
              <option value="average">Average</option>
              <option value="curvy">Curvy</option>
              <option value="plus-size">Plus size</option>
              <option value="muscular">Muscular</option>
            </select>
          </div>

          <div>
            <label htmlFor="height_min" className="block text-sm font-medium text-gray-700">Min Height (cm)</label>
            <input
              type="number"
              id="height_min"
              value={filters.height_min}
              onChange={(e) => handleInputChange('height_min', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            />
          </div>
        </div>
      </div>

      {/* Premium Filters Section */}
      <div className="relative p-4 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg shadow-sm border border-indigo-100">
        {!isPremium && <PremiumOverlay />}

        <h3 className="text-lg font-medium text-indigo-900 mb-4 border-b border-indigo-200 pb-2 flex items-center justify-between">
          <span>Premium Lifestyle Filters</span>
          {isPremium && <span className="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full border border-indigo-200">Unlocked</span>}
        </h3>

        <div className={`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 ${!isPremium ? 'opacity-40 pointer-events-none filter blur-[1px]' : ''}`}>
          {/* Cannabis */}
          <div>
            <label htmlFor="cannabis" className="block text-sm font-medium text-gray-700">Cannabis</label>
            <select
              id="cannabis"
              value={filters.cannabis}
              onChange={(e) => handleInputChange('cannabis', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              disabled={!isPremium}
            >
              <option value="">Any</option>
              <option value="non-smoker">No</option>
              <option value="occasional">Occasional</option>
              <option value="regular">Regular</option>
            </select>
          </div>

          {/* Diet */}
          <div>
            <label htmlFor="diet" className="block text-sm font-medium text-gray-700">Diet</label>
            <select
              id="diet"
              value={filters.diet}
              onChange={(e) => handleInputChange('diet', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              disabled={!isPremium}
            >
              <option value="">Any</option>
              <option value="omnivore">Omnivore</option>
              <option value="vegetarian">Vegetarian</option>
              <option value="vegan">Vegan</option>
              <option value="keto">Keto</option>
              <option value="paleo">Paleo</option>
              <option value="halal">Halal</option>
              <option value="kosher">Kosher</option>
            </select>
          </div>

          {/* Politics */}
          <div>
            <label htmlFor="politics" className="block text-sm font-medium text-gray-700">Politics</label>
            <select
              id="politics"
              value={filters.politics}
              onChange={(e) => handleInputChange('politics', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              disabled={!isPremium}
            >
              <option value="">Any</option>
              <option value="liberal">Liberal</option>
              <option value="moderate">Moderate</option>
              <option value="conservative">Conservative</option>
              <option value="apolitical">Apolitical</option>
              <option value="other">Other</option>
            </select>
          </div>

          {/* Zodiac */}
          <div>
            <label htmlFor="zodiac" className="block text-sm font-medium text-gray-700">Zodiac</label>
            <select
              id="zodiac"
              value={filters.zodiac}
              onChange={(e) => handleInputChange('zodiac', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              disabled={!isPremium}
            >
              <option value="">Any</option>
              <option value="aries">Aries</option>
              <option value="taurus">Taurus</option>
              <option value="gemini">Gemini</option>
              <option value="cancer">Cancer</option>
              <option value="leo">Leo</option>
              <option value="virgo">Virgo</option>
              <option value="libra">Libra</option>
              <option value="scorpio">Scorpio</option>
              <option value="sagittarius">Sagittarius</option>
              <option value="capricorn">Capricorn</option>
              <option value="aquarius">Aquarius</option>
              <option value="pisces">Pisces</option>
            </select>
          </div>

          {/* Pets */}
           <div>
            <label htmlFor="has_pets" className="block text-sm font-medium text-gray-700">Has Pets</label>
            <select
              id="has_pets"
              value={filters.has_pets}
              onChange={(e) => handleInputChange('has_pets', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              disabled={!isPremium}
            >
              <option value="">Any</option>
              <option value="yes">Yes</option>
              <option value="no">No</option>
            </select>
          </div>

           {/* Children */}
           <div>
            <label htmlFor="has_children" className="block text-sm font-medium text-gray-700">Has Children</label>
            <select
              id="has_children"
              value={filters.has_children}
              onChange={(e) => handleInputChange('has_children', e.target.value)}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              disabled={!isPremium}
            >
              <option value="">Any</option>
              <option value="yes">Yes</option>
              <option value="no">No</option>
            </select>
          </div>

        </div>
      </div>

      <div className="flex justify-end pt-2">
        <button
          onClick={handleApplyFilters}
          className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition duration-200 transform hover:-translate-y-0.5"
        >
          Apply Filters
        </button>
      </div>
    </div>
  );
}
