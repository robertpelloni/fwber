'use client';

import { useState, useEffect } from 'react';
import { ChevronDown, ChevronUp, Filter } from 'lucide-react';

interface MatchFilterProps {
  onFilterChange: (filters: any) => void;
}

export default function MatchFilter({ onFilterChange }: MatchFilterProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [filters, setFilters] = useState({
    smoking: '',
    drinking: '',
    body_type: '',
    height_min: '',
    has_bio: false,
    verified_only: false,
    age_min: '',
    age_max: '',
    distance_max: '',
  });

  useEffect(() => {
    const savedFilters = localStorage.getItem('match_filters');
    if (savedFilters) {
      try {
        const parsed = JSON.parse(savedFilters);
        setFilters(parsed);
        // Optionally apply them immediately on mount
        onFilterChange(parsed);
      } catch (e) {
        console.error('Failed to parse saved filters', e);
      }
    }
  }, []);

  const handleInputChange = (field: string, value: any) => {
    setFilters((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const handleApplyFilters = () => {
    localStorage.setItem('match_filters', JSON.stringify(filters));
    onFilterChange(filters);
  };

  const handleReset = () => {
    const resetFilters = {
      smoking: '',
      drinking: '',
      body_type: '',
      height_min: '',
      has_bio: false,
      verified_only: false,
      age_min: '',
      age_max: '',
      distance_max: '',
    };
    setFilters(resetFilters);
    localStorage.removeItem('match_filters');
    onFilterChange(resetFilters);
  };

  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="w-full px-4 py-3 flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors"
      >
        <div className="flex items-center gap-2 text-gray-700 dark:text-gray-200 font-medium">
          <Filter className="w-4 h-4" />
          <span>Advanced Filters</span>
        </div>
        {isOpen ? (
          <ChevronUp className="w-4 h-4 text-gray-500" />
        ) : (
          <ChevronDown className="w-4 h-4 text-gray-500" />
        )}
      </button>

      {isOpen && (
        <div className="p-4 border-t border-gray-200 dark:border-gray-700">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {/* Lifestyle */}
            <div className="space-y-4">
              <h4 className="font-medium text-sm text-gray-900 dark:text-white border-b pb-2 dark:border-gray-700">Lifestyle</h4>
              <div>
                <label htmlFor="smoking" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Smoking
                </label>
                <select
                  id="smoking"
                  value={filters.smoking}
                  onChange={(e) => handleInputChange('smoking', e.target.value)}
                  className="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                >
                  <option value="">Any</option>
                  <option value="non-smoker">Non-smoker</option>
                  <option value="occasional">Occasional smoker</option>
                  <option value="regular">Regular smoker</option>
                  <option value="social">Social smoker</option>
                  <option value="trying-to-quit">Trying to quit</option>
                </select>
              </div>

              <div>
                <label htmlFor="drinking" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Drinking
                </label>
                <select
                  id="drinking"
                  value={filters.drinking}
                  onChange={(e) => handleInputChange('drinking', e.target.value)}
                  className="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                >
                  <option value="">Any</option>
                  <option value="non-drinker">Non-drinker</option>
                  <option value="occasional">Occasional drinker</option>
                  <option value="regular">Regular drinker</option>
                  <option value="social">Social drinker</option>
                  <option value="sober">Sober</option>
                </select>
              </div>
            </div>

            {/* Appearance */}
            <div className="space-y-4">
              <h4 className="font-medium text-sm text-gray-900 dark:text-white border-b pb-2 dark:border-gray-700">Appearance</h4>
              <div>
                <label htmlFor="body_type" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Body Type
                </label>
                <select
                  id="body_type"
                  value={filters.body_type}
                  onChange={(e) => handleInputChange('body_type', e.target.value)}
                  className="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
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
                <label htmlFor="height_min" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Min Height (cm)
                </label>
                <input
                  type="number"
                  id="height_min"
                  value={filters.height_min}
                  onChange={(e) => handleInputChange('height_min', e.target.value)}
                  className="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  placeholder="e.g. 170"
                />
              </div>
            </div>

            {/* Demographics */}
            <div className="space-y-4">
              <h4 className="font-medium text-sm text-gray-900 dark:text-white border-b pb-2 dark:border-gray-700">Demographics</h4>
              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label htmlFor="age_min" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Min Age
                  </label>
                  <input
                    type="number"
                    id="age_min"
                    value={filters.age_min}
                    onChange={(e) => handleInputChange('age_min', e.target.value)}
                    className="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  />
                </div>
                <div>
                  <label htmlFor="age_max" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Max Age
                  </label>
                  <input
                    type="number"
                    id="age_max"
                    value={filters.age_max}
                    onChange={(e) => handleInputChange('age_max', e.target.value)}
                    className="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  />
                </div>
              </div>
              <div>
                <label htmlFor="distance_max" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Max Distance (miles)
                </label>
                <input
                  type="number"
                  id="distance_max"
                  value={filters.distance_max}
                  onChange={(e) => handleInputChange('distance_max', e.target.value)}
                  className="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                />
              </div>
            </div>

            {/* Preferences */}
            <div className="space-y-4">
              <h4 className="font-medium text-sm text-gray-900 dark:text-white border-b pb-2 dark:border-gray-700">Preferences</h4>
              <div className="space-y-2">
                <label className="flex items-center space-x-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={filters.has_bio}
                    onChange={(e) => handleInputChange('has_bio', e.target.checked)}
                    className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                  <span className="text-sm text-gray-700 dark:text-gray-300">Has Bio</span>
                </label>
                <label className="flex items-center space-x-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={filters.verified_only}
                    onChange={(e) => handleInputChange('verified_only', e.target.checked)}
                    className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                  <span className="text-sm text-gray-700 dark:text-gray-300">Verified Only</span>
                </label>
              </div>
            </div>
          </div>

          <div className="mt-6 flex justify-end gap-3">
            <button
              onClick={handleReset}
              className="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              Reset
            </button>
            <button
              onClick={handleApplyFilters}
              className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Apply Filters
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
