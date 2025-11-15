'use client'

import { useState, useEffect, useMemo, useCallback } from 'react'
import { useAuth } from '@/lib/auth-context'
import { useRouter } from 'next/navigation'
import { getUserProfile, updateUserProfile, getProfileCompleteness, type UserProfile, type ProfileUpdateData } from '@/lib/api/profile'
import { usePhotos } from '@/lib/api/photos'
import PhotoUpload from '@/components/PhotoUpload'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Camera, Star } from 'lucide-react'
import { ProfileCompletenessBar, ProfileCompletenessChecklist, calculateProfileCompleteness, type ProfileField } from '@/lib/profileCompleteness'

export default function ProfilePage() {
  const { isAuthenticated, user, token, isLoading: authLoading } = useAuth()
  const router = useRouter()
  // Fallback dev token support
  const localToken = typeof window !== 'undefined' ? (localStorage.getItem('fwber_token') || '') : ''
  const effectiveToken = token || localToken
  const [profile, setProfile] = useState<UserProfile | null>(null)
  const [completeness, setCompleteness] = useState<number>(0)
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState<string | null>(null)
  
  // Photo management
  const { photos, uploadPhotos, deletePhoto, setPrimaryPhoto } = usePhotos()

  // Local form type: ensure location, looking_for, and key array prefs are present for UI binding
  type BasePrefs = NonNullable<ProfileUpdateData['preferences']>
  type ProfileFormData = Omit<ProfileUpdateData, 'preferences' | 'location' | 'looking_for'> & {
    preferences: BasePrefs & {
      hobbies: string[]
      music: string[]
      movies: string[]
      books: string[]
      sports: string[]
      ethnicity?: string[]
    }
    location: NonNullable<ProfileUpdateData['location']>
    looking_for: string[]
  }

  // Form state
  const [formData, setFormData] = useState<ProfileFormData>({
    display_name: '',
    bio: '',
    date_of_birth: '',
    gender: '',
    pronouns: '',
    sexual_orientation: '',
    relationship_style: '',
    looking_for: [],
    location: {
      latitude: undefined,
      longitude: undefined,
      max_distance: 25,
      city: '',
      state: '',
    },
    preferences: {
      // Lifestyle preferences
      smoking: '',
      drinking: '',
      exercise: '',
      diet: '',
      pets: '',
      children: '',
      education: '',
      occupation: '',
      income: '',
      // Dating preferences
      age_range_min: 18,
      age_range_max: 99,
      height_min: '',
      height_max: '',
      body_type: '',
      ethnicity: [],
      religion: '',
      politics: '',
      // Interests
      hobbies: [],
      music: [],
      movies: [],
      books: [],
      sports: [],
      travel: '',
      // Communication
      communication_style: '',
      response_time: '',
      meeting_preference: '',
    },
  })

  // Calculate completeness from current form data
  const currentCompleteness = useMemo(() => {
    return calculateProfileCompleteness({
      displayName: formData.display_name,
      age: formData.date_of_birth ? Math.floor((Date.now() - new Date(formData.date_of_birth).getTime()) / 31536000000) : undefined,
      location: formData.location.city && formData.location.state 
        ? `${formData.location.city}, ${formData.location.state}` 
        : undefined,
      bio: formData.bio,
      photos: photos.map(p => p.url),
      interests: [...formData.preferences.hobbies, ...formData.preferences.music, ...formData.preferences.sports],
      occupation: formData.preferences.occupation,
      education: formData.preferences.education,
      height: formData.preferences.height_min,
      religion: formData.preferences.religion,
      politics: formData.preferences.politics,
      drinking: formData.preferences.drinking,
      smoking: formData.preferences.smoking
    });
  }, [
    formData.display_name,
    formData.date_of_birth,
    formData.location.city,
    formData.location.state,
    formData.bio,
    formData.preferences.hobbies,
    formData.preferences.music,
    formData.preferences.sports,
    formData.preferences.occupation,
    formData.preferences.education,
    formData.preferences.height_min,
    formData.preferences.religion,
    formData.preferences.politics,
    formData.preferences.drinking,
    formData.preferences.smoking,
    photos
  ]);

  const loadProfile = useCallback(async () => {
    if (!effectiveToken) return

    try {
      setIsLoading(true)
      setError(null)

      const [profileData, completenessData] = await Promise.all([
        getUserProfile(effectiveToken),
        getProfileCompleteness(effectiveToken)
      ])

      setProfile(profileData)
      setCompleteness(completenessData.percentage)

      // Populate form with existing data
      if (profileData.profile) {
        setFormData({
          display_name: profileData.profile.display_name || '',
          bio: profileData.profile.bio || '',
          date_of_birth: (profileData.profile as any).date_of_birth || '',
          gender: profileData.profile.gender || '',
          pronouns: profileData.profile.pronouns || '',
          sexual_orientation: profileData.profile.sexual_orientation || '',
          relationship_style: profileData.profile.relationship_style || '',
          looking_for: profileData.profile.looking_for || [],
          location: {
            latitude: profileData.profile.location.latitude ?? undefined,
            longitude: profileData.profile.location.longitude ?? undefined,
            max_distance: profileData.profile.location.max_distance || 25,
            city: profileData.profile.location.city || '',
            state: profileData.profile.location.state || '',
          },
          preferences: {
            // Lifestyle preferences
            smoking: profileData.profile.preferences?.smoking || '',
            drinking: profileData.profile.preferences?.drinking || '',
            exercise: profileData.profile.preferences?.exercise || '',
            diet: profileData.profile.preferences?.diet || '',
            pets: profileData.profile.preferences?.pets || '',
            children: profileData.profile.preferences?.children || '',
            education: profileData.profile.preferences?.education || '',
            occupation: profileData.profile.preferences?.occupation || '',
            income: profileData.profile.preferences?.income || '',
            // Dating preferences
            age_range_min: profileData.profile.preferences?.age_range_min || 18,
            age_range_max: profileData.profile.preferences?.age_range_max || 99,
            height_min: profileData.profile.preferences?.height_min || '',
            height_max: profileData.profile.preferences?.height_max || '',
            body_type: profileData.profile.preferences?.body_type || '',
            ethnicity: profileData.profile.preferences?.ethnicity || [],
            religion: profileData.profile.preferences?.religion || '',
            politics: profileData.profile.preferences?.politics || '',
            // Interests
            hobbies: profileData.profile.preferences?.hobbies || [],
            music: profileData.profile.preferences?.music || [],
            movies: profileData.profile.preferences?.movies || [],
            books: profileData.profile.preferences?.books || [],
            sports: profileData.profile.preferences?.sports || [],
            travel: profileData.profile.preferences?.travel || '',
            // Communication
            communication_style: profileData.profile.preferences?.communication_style || '',
            response_time: profileData.profile.preferences?.response_time || '',
            meeting_preference: profileData.profile.preferences?.meeting_preference || '',
          },
        })
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load profile')
    } finally {
      setIsLoading(false)
    }
  }, [effectiveToken])

  useEffect(() => {
    // Check for dev token as fallback
    const hasDevToken = typeof window !== 'undefined' && localStorage.getItem('fwber_token') === 'dev'
    
    if (!authLoading && !isAuthenticated && !hasDevToken) {
      router.push('/login')
      return
    }

    if ((isAuthenticated || hasDevToken) && effectiveToken) {
      loadProfile()
    }
  }, [isAuthenticated, authLoading, token, router, effectiveToken, loadProfile])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!effectiveToken) return

    try {
      setIsSaving(true)
      setError(null)
      setSuccess(null)

      await updateUserProfile(effectiveToken, formData)
      setSuccess('Profile updated successfully!')
      
      // Reload profile data
      await loadProfile()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update profile')
    } finally {
      setIsSaving(false)
    }
  }

  // Photo upload handlers
  const handlePhotoUpload = async (files: File[], onProgress?: (fileIndex: number, progress: number, fileName: string) => void) => {
    try {
      await uploadPhotos(files, onProgress)
      setSuccess('Photos uploaded successfully!')
    } catch (error) {
      setError(error instanceof Error ? error.message : 'Failed to upload photos')
    }
  }

  const handlePhotoRemove = async (index: number) => {
    try {
      await deletePhoto(photos[index].id)
      setSuccess('Photo removed successfully!')
    } catch (error) {
      setError(error instanceof Error ? error.message : 'Failed to remove photo')
    }
  }

  const handleInputChange = (field: string, value: any) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }))
  }

  const handleLocationChange = (field: string, value: any) => {
    setFormData(prev => ({
      ...prev,
      location: {
        ...prev.location,
        [field]: value
      }
    }))
  }

  const handleLookingForChange = (value: string, checked: boolean) => {
    setFormData(prev => ({
      ...prev,
      looking_for: checked 
        ? [...prev.looking_for, value]
        : prev.looking_for.filter(item => item !== value)
    }))
  }

  type PreferenceKey = keyof ProfileFormData['preferences']
  type PreferenceArrayKey = Extract<PreferenceKey, 'hobbies' | 'music' | 'movies' | 'books' | 'sports' | 'ethnicity'>

  const handlePreferenceChange = (field: PreferenceKey, value: any) => {
    setFormData(prev => ({
      ...prev,
      preferences: {
        ...prev.preferences,
        [field]: value
      }
    }))
  }

  const handleArrayPreferenceChange = (field: PreferenceArrayKey, value: string, checked: boolean) => {
    setFormData(prev => ({
      ...prev,
      preferences: {
        ...prev.preferences,
        [field]: checked 
          ? ([...(prev.preferences[field] as string[] | undefined ?? []), value])
          : ((prev.preferences[field] as string[] | undefined ?? []).filter((item: string) => item !== value))
      }
    }))
  }

  if (authLoading || isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-500"></div>
      </div>
    )
  }

  if (!isAuthenticated) {
    return null // Will redirect to login
  }

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Complete Your Profile</h1>
          <p className="mt-2 text-gray-600 dark:text-gray-300">
            Help others get to know you better. Complete your profile to find better matches.
          </p>
          
          {/* Profile Completeness Bar */}
          <div className="mt-4">
            <ProfileCompletenessBar 
              percentage={currentCompleteness.percentage}
            />
          </div>

          {/* Celebration Message at 100% */}
          {currentCompleteness.percentage === 100 && (
            <div className="mt-4 rounded-lg bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 p-4 border border-green-200 dark:border-green-800">
              <div className="flex items-center space-x-2">
                <Star className="w-5 h-5 text-yellow-500 fill-current" />
                <span className="text-green-800 dark:text-green-200 font-semibold">
                  🎉 Amazing! Your profile is 100% complete!
                </span>
              </div>
            </div>
          )}
        </div>

        {/* Profile Completeness Checklist */}
        <Card className="mb-6">
          <CardHeader>
            <CardTitle>Profile Checklist</CardTitle>
          </CardHeader>
          <CardContent>
            <ProfileCompletenessChecklist 
              completeness={currentCompleteness}
              onFieldClick={(field: ProfileField) => {
                // Scroll to the corresponding field
                const fieldMap: Record<string, string> = {
                  name: 'display_name',
                  age: 'date_of_birth',
                  location: 'city',
                  bio: 'bio',
                  photos: 'photos',
                  interests: 'hobbies',
                  occupation: 'occupation',
                  education: 'education',
                  height: 'height_min',
                  religion: 'religion',
                  politics: 'politics',
                  drinking: 'drinking',
                  smoking: 'smoking'
                }
                const elementId = fieldMap[field.key]
                if (elementId) {
                  const element = document.getElementById(elementId)
                  element?.scrollIntoView({ behavior: 'smooth', block: 'center' })
                  element?.focus()
                  // Add highlight animation
                  element?.classList.add('ring-4', 'ring-blue-500', 'ring-opacity-50')
                  setTimeout(() => {
                    element?.classList.remove('ring-4', 'ring-blue-500', 'ring-opacity-50')
                  }, 2000)
                }
              }}
            />
          </CardContent>
        </Card>

        {/* Profile Form */}
        <div className="bg-white shadow rounded-lg">
          <form onSubmit={handleSubmit} className="p-6 space-y-6">
            <ProfileTabs
              formData={formData}
              handleInputChange={handleInputChange}
              handleLocationChange={handleLocationChange}
              handleLookingForChange={handleLookingForChange}
              handlePreferenceChange={handlePreferenceChange}
              handleArrayPreferenceChange={handleArrayPreferenceChange}
              photos={photos}
              uploadPhotos={handlePhotoUpload}
              deletePhoto={handlePhotoRemove}
            />

            {/* Error/Success Messages */}
            {error && (
              <div className="rounded-md bg-red-50 p-4">
                <div className="text-sm text-red-700">{error}</div>
              </div>
            )}

            {success && (
              <div className="rounded-md bg-green-50 p-4">
                <div className="text-sm text-green-700">{success}</div>
              </div>
            )}

            {/* Submit Button */}
            <div className="flex justify-end">
              <button
                type="submit"
                disabled={isSaving}
                className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isSaving ? 'Saving...' : 'Save Profile'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}