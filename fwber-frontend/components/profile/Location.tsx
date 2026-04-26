'use client';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import LocationMatcher from './LocationMatcher';

interface LocationProps {
  formData: any;
  handleLocationChange: (field: string, value: any) => void;
}

export default function Location({ formData, handleLocationChange }: LocationProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Location & Matching</CardTitle>
      </CardHeader>
      <CardContent>
        <LocationMatcher formData={formData} handleLocationChange={handleLocationChange} />
      </CardContent>
    </Card>
  );
}
