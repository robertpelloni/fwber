'use client';

import { useState } from 'react';

interface MatchFilterProps {
  onFilterChange: (filters: any) => void;
}

export default function MatchFilter({ onFilterChange }: MatchFilterProps) {
  const [filters, setFilters] = useState({
    smoking: '',
    drinking: '',
    body_type: '',
    height_min: '',
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

  return (
    <div className="p-4 bg-gray-100 rounded-lg">
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label htmlFor="smoking" className="block text-sm font-medium text-gray-700">
            Smoking
          </label>
          <select
            id="smoking"
            value={filters.smoking}
            onChange={(e) => handleInputChange('smoking', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
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
          <label htmlFor="drinking" className="block text-sm font-medium text-gray-700">
            Drinking
          </label>
          <select
            id="drinking"
            value={filters.drinking}
            onChange={(e) => handleInputChange('drinking', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="">Any</option>
            <option value="non-drinker">Non-drinker</option>
            <option value="occasional">Occasional drinker</option>
            <option value="regular">Regular drinker</option>
            <option value="social">Social drinker</option>
            <option value="sober">Sober</option>
          </select>
        </div>

        <div>
          <label htmlFor="body_type" className="block text-sm font-medium text-gray-700">
            Body Type
          </label>
          <select
            id="body_type"
            value={filters.body_type}
            onChange={(e) => handleInputChange('body_type', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
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
          <label htmlFor="height_min" className="block text-sm font-medium text-gray-700">
            Minimum Height (cm)
          </label>
          <input
            type="number"
            id="height_min"
            value={filters.height_min}
            onChange={(e) => handleInputChange('height_min', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          />
        </div>
      </div>

      <div className="mt-4 flex justify-end">
        <button
          onClick={handleApplyFilters}
          className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200"
        >
          Apply Filters
        </button>
      </div>
    </div>
  );
}
