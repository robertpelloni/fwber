'use client'

import { useState, useEffect, useMemo, useCallback } from 'react'
import { useAuth } from '@/lib/auth-context'
import { useRouter } from 'next/navigation'
import dynamic from 'next/dynamic'
import { getUserProfile, updateUserProfile, getProfileCompleteness, type UserProfile, type ProfileUpdateData } from '@/lib/api/profile'
import { usePhotos } from '@/lib/api/photos'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

const PhotoUpload = dynamic(() => import('@/components/PhotoUpload'), {
  ssr: false,
  loading: () => <div className="h-64 bg-muted animate-pulse rounded-lg" />
})
import { Camera, ShieldCheck, Star } from 'lucide-react'
import { ProfileCompletenessBar, ProfileCompletenessChecklist, calculateProfileCompleteness, type ProfileField } from '@/lib/profileCompleteness'
import PhysicalProfileEditor from '@/components/PhysicalProfileEditor'
import { isFeatureEnabled } from '@/lib/featureFlags'
import ProfileTabs from '@/components/profile/ProfileTabs'
import VerificationCard from '@/components/VerificationCard'
// import { ProfileRoast } from '@/components/profile/ProfileRoast' // Deprecated inline roast
import { ProfileAnalysis } from '@/components/profile/ProfileAnalysis'
import { VibeCheck } from '@/components/profile/VibeCheck'
import { DatingFortune } from '@/components/profile/DatingFortune'
import { CosmicMatch } from '@/components/profile/CosmicMatch'
import { NemesisFinder } from '@/components/profile/NemesisFinder'
import { VouchLinkCard } from '@/components/profile/VouchLinkCard'

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
  const faceBlurEnabled = isFeatureEnabled('clientFaceBlur')

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
    
    // New Optional Attributes
    love_language?: string
    personality_type?: string
    political_views?: string
    religion?: string
    sleep_schedule?: string
    social_media?: Record<string, string>
    sti_status?: Record<string, any>
    fetishes?: string[]
    penis_length_cm?: number
    penis_girth_cm?: number
    breast_size?: string
    tattoos?: string
    piercings?: string
    smoking_status?: string
    drinking_status?: string
    cannabis_status?: string
    dietary_preferences?: string
    zodiac_sign?: string
    relationship_goals?: string
    has_children?: boolean
    wants_children?: boolean
    has_pets?: boolean
    languages?: string[]
    interests?: string[]
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
    // Initialize new fields
    love_language: '',
    personality_type: '',
    political_views: '',
    religion: '',
    sleep_schedule: '',
    social_media: {},
    sti_status: {},
    fetishes: [],
    penis_length_cm: undefined,
    penis_girth_cm: undefined,
    breast_size: '',
    tattoos: '',
    piercings: '',
    smoking_status: '',
    drinking_status: '',
    cannabis_status: '',
    dietary_preferences: '',
    zodiac_sign: '',
    relationship_goals: '',
    has_children: false,
    wants_children: false,
    has_pets: false,
    languages: [],
    interests: [],
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
      religion: formData.religion || formData.preferences.religion,
      politics: formData.political_views || formData.preferences.politics,
      drinking: formData.drinking_status || formData.preferences.drinking,
      smoking: formData.smoking_status || formData.preferences.smoking
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
    formData.religion,
    formData.political_views,
    formData.drinking_status,
    formData.smoking_status,
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
          // New Optional Attributes
          love_language: profileData.profile.love_language || '',
          personality_type: profileData.profile.personality_type || '',
          chronotype: profileData.profile.chronotype || '',
          social_media: profileData.profile.social_media || {},
          communication_style: profileData.profile.communication_style || '',
          blood_type: profileData.profile.blood_type || '',
          sti_status: profileData.profile.sti_status || {},
          family_plans: profileData.profile.family_plans || '',
          relationship_goals: profileData.profile.relationship_goals || '',
          languages: profileData.profile.languages || [],
          zodiac_sign: profileData.profile.zodiac_sign || '',
          drinking_status: profileData.profile.drinking_status || '',
          smoking_status: profileData.profile.smoking_status || '',
          cannabis_status: profileData.profile.cannabis_status || '',
          dietary_preferences: profileData.profile.dietary_preferences || '',
          exercise_habits: profileData.profile.exercise_habits || '',
          sleep_habits: profileData.profile.sleep_habits || '',
          pets: profileData.profile.pets || [],
          children: profileData.profile.children || '',
          religion: profileData.profile.religion || '',
          political_views: profileData.profile.political_views || '',
          
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
  const handlePhotoUpload = async (
    items: Array<{ file: File; isPrivate?: boolean; unlockPrice?: number }> | File[],
    onProgress?: (fileIndex: number, progress: number, fileName: string) => void
  ) => {
    try {
      await uploadPhotos(items, onProgress)
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

  const handlePreferenceChange = (field: string, value: any) => {
    setFormData(prev => ({
      ...prev,
      preferences: {
        ...prev.preferences,
        [field as PreferenceKey]: value
      }
    }))
  }

  const handleArrayPreferenceChange = (field: string, value: string, checked: boolean) => {
    const key = field as PreferenceArrayKey;
    setFormData(prev => ({
      ...prev,
      preferences: {
        ...prev.preferences,
        [key]: checked 
          ? ([...(prev.preferences[key] as string[] | undefined ?? []), value])
          : ((prev.preferences[key] as string[] | undefined ?? []).filter((item: string) => item !== value))
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


        {/* AI Wingman Features */}
        <div className="mb-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
          <ProfileAnalysis />
          {/* <ProfileRoast /> Replaced by the new Viral Roast Page */}
          <Card className="bg-gradient-to-br from-orange-900 to-red-900 text-white border-orange-500/30 overflow-hidden relative">
             <div className="absolute top-0 right-0 w-32 h-32 bg-orange-500/20 blur-3xl rounded-full -mr-16 -mt-16 pointer-events-none"></div>
             <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <span className="text-2xl">🔥</span> Roast My Profile
                </CardTitle>
             </CardHeader>
             <CardContent>
                <p className="text-orange-100/80 mb-4">
                  Ready to get humbled? Let our AI roast your profile choices. Or switch to "Hype Mode" for an ego boost.
                </p>
                <a href="/roast" className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-white text-orange-900 hover:bg-orange-100 h-10 px-4 py-2 w-full">
                  Go to Roast Page
                </a>
             </CardContent>
          </Card>
          <VibeCheck />
          <DatingFortune />
          <CosmicMatch userId={user?.id || 'me'} />
          <NemesisFinder />
          <VouchLinkCard />
        </div>


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
            {/* Photo Upload Section */}
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
                {faceBlurEnabled && (
                  <div className="mb-4 flex items-start gap-3 rounded-lg border border-primary/30 bg-primary/5 p-4 text-sm text-primary-900">
                    <ShieldCheck className="h-5 w-5 text-primary" />
                    <div>
                      <p className="font-semibold">Client-side face blur is active</p>
                      <p className="text-xs text-primary-800">
                        New uploads blur detected faces locally before they leave your device so only redacted images reach FWBer servers.
                      </p>
                    </div>
                  </div>
                )}
                <PhotoUpload
                  onUpload={handlePhotoUpload}
                  onRemove={handlePhotoRemove}
                  photos={photos}
                  maxPhotos={12}
                  maxSize={5}
                />
              </CardContent>
            </Card>

            {/* Verification Section */}
            <VerificationCard />

            {/* Basic Information */}
            <div>
              <h2 className="text-xl font-semibold text-gray-900 mb-4">Basic Information</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label htmlFor="display_name" className="block text-sm font-medium text-gray-700">
                    Display Name
                  </label>
                  <input
                    type="text"
                    id="display_name"
                    value={formData.display_name}
                    onChange={(e) => handleInputChange('display_name', e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="How would you like to be called?"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    Date of Birth
                  </label>
                  <div className="grid grid-cols-3 gap-2 mt-1">
                    <select
                      className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      value={formData.date_of_birth ? formData.date_of_birth.split('-')[1] : ''}
                      onChange={(e) => {
                         const [y, m, d] = formData.date_of_birth ? formData.date_of_birth.split('-') : [`${new Date().getFullYear() - 18}`, '01', '01'];
                         handleInputChange('date_of_birth', `${y}-${e.target.value}-${d}`);
                      }}
                    >
                      <option value="">Month</option>
                      {["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"].map((month, i) => (
                        <option key={i} value={(i + 1).toString().padStart(2, '0')}>{month}</option>
                      ))}
                    </select>
                    <select
                      className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      value={formData.date_of_birth ? formData.date_of_birth.split('-')[2] : ''}
                      onChange={(e) => {
                        const [y, m, d] = formData.date_of_birth ? formData.date_of_birth.split('-') : [`${new Date().getFullYear() - 18}`, '01', '01'];
                        handleInputChange('date_of_birth', `${y}-${m}-${e.target.value}`);
                      }}
                    >
                      <option value="">Day</option>
                      {Array.from({length: 31}, (_, i) => {
                        const d = (i + 1).toString().padStart(2, '0');
                        return <option key={d} value={d}>{i + 1}</option>
                      })}
                    </select>
                    <select
                      className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      value={formData.date_of_birth ? formData.date_of_birth.split('-')[0] : ''}
                      onChange={(e) => {
                        const [y, m, d] = formData.date_of_birth ? formData.date_of_birth.split('-') : [`${new Date().getFullYear() - 18}`, '01', '01'];
                        handleInputChange('date_of_birth', `${e.target.value}-${m}-${d}`);
                      }}
                    >
                      <option value="">Year</option>
                      {Array.from({length: 100}, (_, i) => {
                        const year = new Date().getFullYear() - 18 - i;
                        return <option key={year} value={year}>{year}</option>
                      })}
                    </select>
                  </div>
                </div>

                <div>
                  <label htmlFor="gender" className="block text-sm font-medium text-gray-700">
                    Gender
                  </label>
                  <select
                    id="gender"
                    value={formData.gender}
                    onChange={(e) => handleInputChange('gender', e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="">Select gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="non-binary">Non-binary</option>
                    <option value="mtf">Trans Woman</option>
                    <option value="ftm">Trans Man</option>
                    <option value="other">Other</option>
                    <option value="prefer-not-to-say">Prefer not to say</option>
                  </select>
                </div>

                <div>
                  <label htmlFor="pronouns" className="block text-sm font-medium text-gray-700">
                    Pronouns
                  </label>
                  <select
                    id="pronouns"
                    value={formData.pronouns}
                    onChange={(e) => handleInputChange('pronouns', e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="">Select pronouns</option>
                    <option value="he/him">He/Him</option>
                    <option value="she/her">She/Her</option>
                    <option value="they/them">They/Them</option>
                    <option value="he/they">He/They</option>
                    <option value="she/they">She/They</option>
                    <option value="other">Other</option>
                    <option value="prefer-not-to-say">Prefer not to say</option>
                  </select>
                </div>

                <div>
                  <label htmlFor="sexual_orientation" className="block text-sm font-medium text-gray-700">
                    Sexual Orientation
                  </label>
                  <select
                    id="sexual_orientation"
                    value={formData.sexual_orientation}
                    onChange={(e) => handleInputChange('sexual_orientation', e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="">Select orientation</option>
                    <option value="straight">Straight</option>
                    <option value="gay">Gay</option>
                    <option value="lesbian">Lesbian</option>
                    <option value="bisexual">Bisexual</option>
                    <option value="pansexual">Pansexual</option>
                    <option value="asexual">Asexual</option>
                    <option value="demisexual">Demisexual</option>
                    <option value="queer">Queer</option>
                    <option value="questioning">Questioning</option>
                    <option value="other">Other</option>
                    <option value="prefer-not-to-say">Prefer not to say</option>
                  </select>
                </div>

                <div>
                  <label htmlFor="relationship_style" className="block text-sm font-medium text-gray-700">
                    Relationship Style
                  </label>
                  <select
                    id="relationship_style"
                    value={formData.relationship_style}
                    onChange={(e) => handleInputChange('relationship_style', e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="">Select relationship style</option>
                    <option value="monogamous">Monogamous</option>
                    <option value="non-monogamous">Non-monogamous</option>
                    <option value="polyamorous">Polyamorous</option>
                    <option value="open">Open Relationship</option>
                    <option value="swinger">Swinger</option>
                    <option value="other">Other</option>
                    <option value="prefer-not-to-say">Prefer not to say</option>
                  </select>
                </div>

                <div>
                  <label htmlFor="max_distance" className="block text-sm font-medium text-gray-700">
                    Maximum Distance (miles)
                  </label>
                  <input
                    type="number"
                    id="max_distance"
                    min="1"
                    max="100"
                    value={formData.location.max_distance}
                    onChange={(e) => handleLocationChange('max_distance', parseInt(e.target.value))}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  />
                </div>
              </div>
            </div>

            {/* Bio */}
            <div>
              <label htmlFor="bio" className="block text-sm font-medium text-gray-700">
                About You
              </label>
              <textarea
                id="bio"
                rows={4}
                value={formData.bio}
                onChange={(e) => handleInputChange('bio', e.target.value)}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                placeholder="Tell others about yourself, your interests, and what you're looking for..."
              />
            </div>

            {/* Looking For */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-3">
                Looking For
              </label>
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
            </div>

            {/* Location */}
            <div>
              <h2 className="text-xl font-semibold text-gray-900 mb-4">Location</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
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
              </div>
            </div>

            {/* Lifestyle Preferences */}
            <div>
              <h2 className="text-xl font-semibold text-gray-900 mb-4">Lifestyle Preferences</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
                  <label htmlFor="smoking" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Smoking
                  </label>
                  <select
                    id="smoking"
                    value={formData.preferences.smoking}
                    onChange={(e) => handlePreferenceChange('smoking', e.target.value)}
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
                  <label htmlFor="drinking" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Drinking
                  </label>
                  <select
                    id="drinking"
                    value={formData.preferences.drinking}
                    onChange={(e) => handlePreferenceChange('drinking', e.target.value)}
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
                  <label htmlFor="exercise" className="block text-sm font-medium text-gray-700">
                    Exercise
                  </label>
                  <select
                    id="exercise"
                    value={formData.preferences.exercise}
                    onChange={(e) => handlePreferenceChange('exercise', e.target.value)}
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
                  <label htmlFor="diet" className="block text-sm font-medium text-gray-700">
                    Diet
                  </label>
                  <select
                    id="diet"
                    value={formData.preferences.diet}
                    onChange={(e) => handlePreferenceChange('diet', e.target.value)}
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
                  <label htmlFor="pets" className="block text-sm font-medium text-gray-700">
                    Pets
                  </label>
                  <select
                    id="pets"
                    value={formData.preferences.pets}
                    onChange={(e) => handlePreferenceChange('pets', e.target.value)}
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
                    value={formData.preferences.children}
                    onChange={(e) => handlePreferenceChange('children', e.target.value)}
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
              </div>
            </div>

            {/* Dating Preferences */}
            <div>
              <h2 className="text-xl font-semibold text-gray-900 mb-4">Dating Preferences</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                  <label htmlFor="height_min" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Your Height
                  </label>
                  <input
                    type="text"
                    id="height_min"
                    value={formData.preferences.height_min}
                    onChange={(e) => handlePreferenceChange('height_min', e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="e.g., 5'10&quot; or 178cm"
                  />
                </div>

                <div>
                  <label htmlFor="age_range_min" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Minimum Age
                  </label>
                  <input
                    type="number"
                    id="age_range_min"
                    min="18"
                    max="99"
                    value={formData.preferences.age_range_min}
                    onChange={(e) => handlePreferenceChange('age_range_min', parseInt(e.target.value))}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  />
                </div>

                <div>
                  <label htmlFor="age_range_max" className="block text-sm font-medium text-gray-700">
                    Maximum Age
                  </label>
                  <input
                    type="number"
                    id="age_range_max"
                    min="18"
                    max="99"
                    value={formData.preferences.age_range_max}
                    onChange={(e) => handlePreferenceChange('age_range_max', parseInt(e.target.value))}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  />
                </div>

                <div>
                  <label htmlFor="pref_body_type" className="block text-sm font-medium text-gray-700">
                    Body Type Preference
                  </label>
                  <select
                    id="pref_body_type"
                    value={formData.preferences.body_type}
                    onChange={(e) => handlePreferenceChange('body_type', e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="">No preference</option>
                    <option value="slim">Slim</option>
                    <option value="athletic">Athletic</option>
                    <option value="average">Average</option>
                    <option value="curvy">Curvy</option>
                    <option value="plus-size">Plus size</option>
                    <option value="muscular">Muscular</option>
                  </select>
                </div>

                <div>
                  <label htmlFor="religion" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Religion
                  </label>
                  <select
                    id="religion"
                    value={formData.preferences.religion}
                    onChange={(e) => handlePreferenceChange('religion', e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="">No preference</option>
                    <option value="christian">Christian</option>
                    <option value="catholic">Catholic</option>
                    <option value="jewish">Jewish</option>
                    <option value="muslim">Muslim</option>
                    <option value="hindu">Hindu</option>
                    <option value="buddhist">Buddhist</option>
                    <option value="agnostic">Agnostic</option>
                    <option value="atheist">Atheist</option>
                    <option value="spiritual">Spiritual</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div>
                  <label htmlFor="politics" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Politics
                  </label>
                  <select
                    id="politics"
                    value={formData.preferences.politics}
                    onChange={(e) => handlePreferenceChange('politics', e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="">No preference</option>
                    <option value="liberal">Liberal</option>
                    <option value="moderate">Moderate</option>
                    <option value="conservative">Conservative</option>
                    <option value="apolitical">Apolitical</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div>
                  <label htmlFor="education" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Education Level
                  </label>
                  <select
                    id="education"
                    value={formData.preferences.education}
                    onChange={(e) => handlePreferenceChange('education', e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="">Select education</option>
                    <option value="high-school">High School</option>
                    <option value="some-college">Some College</option>
                    <option value="associates">Associate&apos;s Degree</option>
                    <option value="bachelors">Bachelor&apos;s Degree</option>
                    <option value="masters">Master&apos;s Degree</option>
                    <option value="phd">PhD</option>
                    <option value="other">Other</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Interests */}
            <div>
              <h2 className="text-xl font-semibold text-gray-900 mb-4">Interests & Hobbies</h2>
              <div className="space-y-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Hobbies
                  </label>
                  <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3" id="hobbies">
                    {['reading', 'writing', 'photography', 'cooking', 'gardening', 'art', 'music', 'dancing', 'hiking', 'traveling', 'gaming', 'sports', 'fitness', 'yoga', 'meditation', 'volunteering'].map((hobby) => (
                      <label key={hobby} className="flex items-center">
                        <input
                          type="checkbox"
                          checked={formData.preferences.hobbies.includes(hobby)}
                          onChange={(e) => handleArrayPreferenceChange('hobbies', hobby, e.target.checked)}
                          className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        />
                        <span className="ml-2 text-sm text-gray-700 capitalize">{hobby}</span>
                      </label>
                    ))}
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-3">
                    Music Genres
                  </label>
                  <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    {['pop', 'rock', 'hip-hop', 'country', 'jazz', 'classical', 'electronic', 'indie', 'r&b', 'reggae', 'blues', 'folk', 'metal', 'punk', 'alternative', 'world'].map((genre) => (
                      <label key={genre} className="flex items-center">
                        <input
                          type="checkbox"
                          checked={formData.preferences.music.includes(genre)}
                          onChange={(e) => handleArrayPreferenceChange('music', genre, e.target.checked)}
                          className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        />
                        <span className="ml-2 text-sm text-gray-700 capitalize">{genre}</span>
                      </label>
                    ))}
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-3">
                    Sports
                  </label>
                  <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    {['football', 'basketball', 'soccer', 'tennis', 'golf', 'baseball', 'hockey', 'swimming', 'running', 'cycling', 'yoga', 'pilates', 'boxing', 'martial-arts', 'skiing', 'snowboarding'].map((sport) => (
                      <label key={sport} className="flex items-center">
                        <input
                          type="checkbox"
                          checked={formData.preferences.sports.includes(sport)}
                          onChange={(e) => handleArrayPreferenceChange('sports', sport, e.target.checked)}
                          className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        />
                        <span className="ml-2 text-sm text-gray-700 capitalize">{sport.replace('-', ' ')}</span>
                      </label>
                    ))}
                  </div>
                </div>
              </div>
            </div>

            {/* Communication Preferences */}
            <div>
              <h2 className="text-xl font-semibold text-gray-900 mb-4">Communication Preferences</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                  <label htmlFor="communication_style" className="block text-sm font-medium text-gray-700">
                    Communication Style
                  </label>
                  <select
                    id="communication_style"
                    value={formData.preferences.communication_style}
                    onChange={(e) => handlePreferenceChange('communication_style', e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="">Select style</option>
                    <option value="direct">Direct</option>
                    <option value="diplomatic">Diplomatic</option>
                    <option value="humorous">Humorous</option>
                    <option value="serious">Serious</option>
                    <option value="casual">Casual</option>
                    <option value="formal">Formal</option>
                  </select>
                </div>

                <div>
                  <label htmlFor="response_time" className="block text-sm font-medium text-gray-700">
                    Response Time
                  </label>
                  <select
                    id="response_time"
                    value={formData.preferences.response_time}
                    onChange={(e) => handlePreferenceChange('response_time', e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="">Select preference</option>
                    <option value="immediate">Immediate</option>
                    <option value="within-hour">Within an hour</option>
                    <option value="within-day">Within a day</option>
                    <option value="when-convenient">When convenient</option>
                    <option value="no-rush">No rush</option>
                  </select>
                </div>

                <div>
                  <label htmlFor="meeting_preference" className="block text-sm font-medium text-gray-700">
                    Meeting Preference
                  </label>
                  <select
                    id="meeting_preference"
                    value={formData.preferences.meeting_preference}
                    onChange={(e) => handlePreferenceChange('meeting_preference', e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="">Select preference</option>
                    <option value="public-places">Public places</option>
                    <option value="coffee-dates">Coffee dates</option>
                    <option value="dinner-dates">Dinner dates</option>
                    <option value="outdoor-activities">Outdoor activities</option>
                    <option value="virtual-first">Virtual first</option>
                    <option value="flexible">Flexible</option>
                  </select>
                </div>
              </div>
            </div>

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

        {/* Physical Profile Section */}
        <div className="mt-8">
          <PhysicalProfileEditor />
        </div>
      </div>
    </div>
  );
}