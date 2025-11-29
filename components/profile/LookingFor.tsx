'use client';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface LookingForProps {
  formData: any;
  handleLookingForChange: (value: string, checked: boolean) => void;
}

export default function LookingFor({ formData, handleLookingForChange }: LookingForProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Looking For</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
          {['friendship', 'dating', 'relationship', 'casual', 'marriage', 'networking'].map((option) => (
            <label key={option} className="flex items-center">
              <input
                type="checkbox"
                checked={formData.looking_for.includes(option)}
                onChange={(e) => handleLookingForChange(option, e.target.checked)}
                className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
              />
              <span className="ml-2 text-sm text-gray-700 capitalize">{option}</span>
            </label>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}
