'use client';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface LocationProps {
  formData: any;
  handleLocationChange: (field: string, value: any) => void;
}

export default function Location({ formData, handleLocationChange }: LocationProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Location</CardTitle>
      </CardHeader>
      <CardContent className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label htmlFor="city" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
            City
          </label>
          <input
            type="text"
            id="city"
            value={formData.location.city}
            onChange={(e) => handleLocationChange('city', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="Enter your city"
          />
        </div>

        <div>
          <label htmlFor="state" className="block text-sm font-medium text-gray-700">
            State
          </label>
          <input
            type="text"
            id="state"
            value={formData.location.state}
            onChange={(e) => handleLocationChange('state', e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="Enter your state"
          />
        </div>
      </CardContent>
    </Card>
  );
}
