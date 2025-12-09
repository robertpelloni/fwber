'use client';

import { useState } from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import BasicInformation from './BasicInformation';
import Bio from './Bio';
import LookingFor from './LookingFor';
import Location from './Location';
import Lifestyle from './Lifestyle';
import Dating from './Dating';
import Interests from './Interests';
import Communication from './Communication';
import PhotoUpload from '../PhotoUpload';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Camera } from 'lucide-react';

interface ProfileTabsProps {
  formData: any;
  handleInputChange: (field: string, value: any) => void;
  handleLocationChange: (field: string, value: any) => void;
  handleLookingForChange: (value: string, checked: boolean) => void;
  handlePreferenceChange: (field: string, value: any) => void;
  handleArrayPreferenceChange: (field: string, value: string, checked: boolean) => void;
  photos: any[];
  uploadPhotos: (files: File[], onProgress?: (fileIndex: number, progress: number, fileName: string) => void) => Promise<void>;
  deletePhoto: (index: number) => void;
}

export default function ProfileTabs({
  formData,
  handleInputChange,
  handleLocationChange,
  handleLookingForChange,
  handlePreferenceChange,
  handleArrayPreferenceChange,
  photos,
  uploadPhotos,
  deletePhoto,
}: ProfileTabsProps) {
  const [isEditMode, setIsEditMode] = useState(false);

  return (
    <div>
      <div className="flex justify-end mb-4">
        <button
          onClick={() => setIsEditMode(!isEditMode)}
          className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200"
        >
          {isEditMode ? 'View Profile' : 'Edit Profile'}
        </button>
      </div>

      {isEditMode ? (
        <Tabs defaultValue="basic">
          <TabsList>
            <TabsTrigger value="basic">Basic Info</TabsTrigger>
            <TabsTrigger value="bio">About You</TabsTrigger>
            <TabsTrigger value="lookingFor">Looking For</TabsTrigger>
            <TabsTrigger value="location">Location</TabsTrigger>
            <TabsTrigger value="lifestyle">Lifestyle</TabsTrigger>
            <TabsTrigger value="dating">Dating</TabsTrigger>
            <TabsTrigger value="interests">Interests</TabsTrigger>
            <TabsTrigger value="communication">Communication</TabsTrigger>
            <TabsTrigger value="photos">Photos</TabsTrigger>
          </TabsList>
          <TabsContent value="basic">
            <BasicInformation formData={formData} handleInputChange={handleInputChange} handleLocationChange={handleLocationChange} />
          </TabsContent>
          <TabsContent value="bio">
            <Bio formData={formData} handleInputChange={handleInputChange} />
          </TabsContent>
          <TabsContent value="lookingFor">
            <LookingFor formData={formData} handleLookingForChange={handleLookingForChange} />
          </TabsContent>
          <TabsContent value="location">
            <Location formData={formData} handleLocationChange={handleLocationChange} />
          </TabsContent>
          <TabsContent value="lifestyle">
            <Lifestyle formData={formData} handlePreferenceChange={handlePreferenceChange} />
          </TabsContent>
          <TabsContent value="dating">
            <Dating formData={formData} handlePreferenceChange={handlePreferenceChange} />
          </TabsContent>
          <TabsContent value="interests">
            <Interests formData={formData} handleArrayPreferenceChange={handleArrayPreferenceChange} />
          </TabsContent>
          <TabsContent value="communication">
            <Communication formData={formData} handlePreferenceChange={handlePreferenceChange} />
          </TabsContent>
          <TabsContent value="photos">
            <Card id="photos">
              <CardHeader>
                <CardTitle className="flex items-center space-x-2">
                  <Camera className="w-5 h-5" />
                  <span>Profile Photos</span>
                  {photos.length > 0 && (
                    <span className="text-sm text-muted-foreground">
                      ({photos.length} photos)
                    </span>
                  )}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <PhotoUpload
                  onUpload={uploadPhotos}
                  onRemove={deletePhoto}
                  photos={photos.map((photo) => photo.url)}
                  maxPhotos={12}
                  maxSize={5}
                />
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      ) : (
        <div className="space-y-6">
          <BasicInformation formData={formData} handleInputChange={() => {}} handleLocationChange={() => {}} />
          <Bio formData={formData} handleInputChange={() => {}} />
          <LookingFor formData={formData} handleLookingForChange={() => {}} />
          <Location formData={formData} handleLocationChange={() => {}} />
          <Lifestyle formData={formData} handlePreferenceChange={() => {}} />
          <Dating formData={formData} handlePreferenceChange={() => {}} />
          <Interests formData={formData} handleArrayPreferenceChange={() => {}} />
          <Communication formData={formData} handlePreferenceChange={() => {}} />
        </div>
      )}
    </div>
  );
}
