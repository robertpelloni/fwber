'use client'

import { useState, useEffect, useMemo, useCallback } from 'react'
import { useAuth } from '@/lib/auth-context'
import { useRouter } from 'next/navigation'
import dynamic from 'next/dynamic'
import { getUserProfile, updateUserProfile, getProfileCompleteness, type UserProfile, type ProfileUpdateData } from '@/lib/api/profile'
import { getTopics, type Topic } from '@/lib/api/topics'
import { usePhotos } from '@/lib/api/photos'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

const PhotoUpload = dynamic(() => import('@/components/PhotoUpload'), {
  ssr: false,
  loading: () => <div className="h-64 bg-muted animate-pulse rounded-lg" />
})
import { Camera, ShieldCheck, Star, Share2 } from 'lucide-react'
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
  const [interestTopics, setInterestTopics] = useState<Topic[]>([])
  const [topicsLoading, setTopicsLoading] = useState(true)

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
    tattoos?: boolean
    piercings?: boolean
    smoking_status?: string
    drinking_status?: string
    cannabis_status?: string
    dietary_preferences?: string
    zodiac_sign?: string
    relationship_goals?: string
    has_children?: boolean | string
    wants_children?: boolean | string
    has_pets?: boolean | string
    languages?: string[]
    interests?: string[]
    voice_intro?: File | null
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
    tattoos: false,
    piercings: false,
    smoking_status: '',
    drinking_status: '',
    cannabis_status: '',
    dietary_preferences: '',
    zodiac_sign: '',
    relationship_goals: '',
    has_children: '',
    wants_children: '',
    has_pets: '',
    languages: [],
    interests: [],
    voice_intro: null,
  })

  const getCombinedInterestValues = useCallback((data: ProfileFormData) => {
    return Array.from(new Set([
      ...(data.interests ?? []),
      ...(data.preferences.hobbies ?? []),
      ...(data.preferences.music ?? []),
      ...(data.preferences.movies ?? []),
      ...(data.preferences.books ?? []),
      ...(data.preferences.sports ?? []),
    ]
      .map(value => value.trim().toLowerCase())
      .filter(Boolean)))
  }, [])

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
      interests: getCombinedInterestValues(formData),
      occupation: formData.preferences.occupation,
      education: formData.preferences.education,
      height: formData.preferences.height_min,
      religion: formData.religion || formData.preferences.religion,
      politics: formData.political_views || formData.preferences.politics,
      drinking: formData.drinking_status || formData.preferences.drinking,
      smoking: formData.smoking_status || formData.preferences.smoking
    });
  }, [formData, photos, getCombinedInterestValues]);

  useEffect(() => {
    let isMounted = true

    const loadInterestTopics = async () => {
      try {
        setTopicsLoading(true)
        const featuredTopics = await getTopics({ featured: true })
        const topics = featuredTopics.length > 0 ? featuredTopics : await getTopics()

        if (isMounted) {
          setInterestTopics(topics.slice(0, 18))
        }
      } catch {
        if (isMounted) {
          setInterestTopics([])
        }
      } finally {
        if (isMounted) {
          setTopicsLoading(false)
        }
      }
    }

    void loadInterestTopics()

    return () => {
      isMounted = false
    }
  }, [])

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
          interests: profileData.profile.interests || [],
          voice_intro: null,

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

      await updateUserProfile(effectiveToken, {
        ...formData,
        interests: getCombinedInterestValues(formData),
      })
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

  const handleArrayChange = (field: string, value: string, checked: boolean) => {
    setFormData(prev => {
      const currentArray = (prev as Record<string, unknown>)[field] as string[] | undefined ?? [];
      const updatedArray = checked
        ? [...currentArray, value]
        : currentArray.filter((item: string) => item !== value);
      return { ...prev, [field]: updatedArray };
    });
  }

  const handleTopicInterestToggle = (slug: string) => {
    setFormData(prev => ({
      ...prev,
      interests: prev.interests?.includes(slug)
        ? prev.interests.filter(item => item !== slug)
        : [...(prev.interests ?? []), slug]
    }))
  }

  const handleVoiceUpload = (file: File) => {
    setFormData(prev => ({
      ...prev,
      voice_intro: file
    }))
  }

  const handleVoiceDelete = () => {
    setFormData(prev => ({
      ...prev,
      voice_intro: null,
      voice_intro_url: null
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
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
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
            <div className="mt-4 rounded-lg bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 p-4 border border-green-200 dark:border-green-800 flex items-center justify-between gap-4">
              <div className="flex items-center space-x-2">
                <Star className="w-5 h-5 text-yellow-500 fill-current" />
                <span className="text-green-800 dark:text-green-200 font-semibold">
                  🎉 Amazing! Your profile is 100% complete!
                </span>
              </div>
              <button
                onClick={async () => {
                  const shareText = `💎 My dating profile is officially 100% complete on fwber! Come find me in Detroit's private-first adult network. #fwber #dating #detroit`
                  if (navigator.share) {
                    try {
                      await navigator.share({
                        title: 'Profile 100% Complete!',
                        text: shareText,
                        url: window.location.origin
                      })
                    } catch (err) { console.error(err) }
                  } else {
                    await navigator.clipboard.writeText(shareText)
                    // Clipboard write succeeded
                  }
                }}
                className="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-black rounded-full transition shadow-md whitespace-nowrap"
              >
                <Share2 className="w-3.5 h-3.5" />
                Share Status
              </button>
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
                Ready to get humbled? Let our AI roast your profile choices. Or switch to &quot;Hype Mode&quot; for an ego boost.
              </p>
              <a href="/roast" className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-white dark:bg-gray-800 text-orange-900 hover:bg-orange-100 h-10 px-4 py-2 w-full">
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
        <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
          <form onSubmit={handleSubmit} className="p-6 space-y-6">
            <ProfileTabs
              formData={formData}
              handleInputChange={handleInputChange}
              handleLocationChange={handleLocationChange}
              handleLookingForChange={handleLookingForChange}
              handlePreferenceChange={handlePreferenceChange}
              handleArrayPreferenceChange={handleArrayPreferenceChange}
              handleArrayChange={handleArrayChange}
              photos={photos}
              uploadPhotos={handlePhotoUpload}
              deletePhoto={handlePhotoRemove}
              handleVoiceUpload={handleVoiceUpload}
              handleVoiceDelete={handleVoiceDelete}
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
                        New uploads blur detected faces locally before they leave your device so only redacted images reach fwber servers.
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
