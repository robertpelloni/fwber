'use client';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface BioProps {
  formData: any;
  handleInputChange: (field: string, value: any) => void;
}

export default function Bio({ formData, handleInputChange }: BioProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>About You</CardTitle>
      </CardHeader>
      <CardContent>
        <textarea
          id="bio"
          rows={4}
          value={formData.bio}
          onChange={(e) => handleInputChange('bio', e.target.value)}
          className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          placeholder="Tell others about yourself, your interests, and what you're looking for..."
        />
      </CardContent>
    </Card>
  );
}
