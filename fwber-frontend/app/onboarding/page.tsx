'use client'

import { useState, useEffect } from 'react'
import { useAuth } from '@/lib/auth-context'
import { useRouter } from 'next/navigation'
import { updateUserProfile, completeOnboarding, getUserProfile, type UserProfile } from '@/lib/api/profile'
import { usePhotos } from '@/lib/api/photos'
import { getCurrentGeolocation } from '@/lib/api/location'
import dynamic from 'next/dynamic'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card'

const PhotoUpload = dynamic(() => import('@/components/PhotoUpload'), {
  ssr: false,
  loading: () => <div className="h-64 bg-muted animate-pulse rounded-lg" />
})
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Loader2, CheckCircle2, Camera, MapPin, User, Heart } from 'lucide-react'

const STEPS = [
  { id: 'welcome', title: 'Welcome' },
  { id: 'basics', title: 'Basic Info' },
  { id: 'photos', title: 'Photos' },
  { id: 'preferences', title: 'Preferences' },
  { id: 'complete', title: 'All Set!' },
]

export default function OnboardingPage() {
  const { isAuthenticated, token, isLoading: authLoading, updateUser, user } = useAuth()
  const router = useRouter()
  const [currentStep, setCurrentStep] = useState(0)
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  
  // Photo management
  const { photos, uploadPhotos, deletePhoto } = usePhotos()

  // Form State
  const [formData, setFormData] = useState({
    display_name: '',
    date_of_birth: '',
    gender: '',
    location: {
      city: '',
      state: '',
      latitude: 0,
      longitude: 0, // In a real app, we'd get this from browser geolocation
    },
    looking_for: [] as string[],
    preferences: {
      age_range_min: 18,
      age_range_max: 50,
    }
  })

  // Load initial data
  useEffect(() => {
    if (isAuthenticated && token) {
      getUserProfile(token).then(data => {
        if (data.profile) {
          setFormData(prev => ({
            ...prev,
            display_name: data.profile.display_name || '',
            date_of_birth: (data.profile as any).date_of_birth || '',
            gender: data.profile.gender || '',
            location: {
              city: data.profile.location.city || '',
              state: data.profile.location.state || '',
              latitude: data.profile.location.latitude || 0,
              longitude: data.profile.location.longitude || 0,
            },
            looking_for: data.profile.looking_for || [],
            preferences: {
              age_range_min: data.profile.preferences?.age_range_min || 18,
              age_range_max: data.profile.preferences?.age_range_max || 50,
            }
          }))
        }
      }).catch(console.error)
    }
  }, [isAuthenticated, token])

  const handleUseCurrentLocation = async () => {
    setIsLoading(true)
    try {
      const position = await getCurrentGeolocation()
      setFormData(prev => ({
        ...prev,
        location: {
          ...prev.location,
          latitude: position.coords.latitude,
          longitude: position.coords.longitude
        }
      }))
    } catch (err) {
      console.error(err)
      setError('Could not get location. Please enter manually.')
    } finally {
      setIsLoading(false)
    }
  }

  const handleNext = async () => {
    setError(null)
    setIsLoading(true)

    try {
      if (STEPS[currentStep].id === 'basics') {
        if (!formData.display_name || !formData.date_of_birth || !formData.gender) {
          throw new Error('Please fill in all required fields.')
        }

        // Sanitize location data
        const locationUpdate: any = { ...formData.location };
        
        // Remove 0 coordinates if they haven't been set by geolocation
        if (locationUpdate.latitude === 0 && locationUpdate.longitude === 0) {
            delete locationUpdate.latitude;
            delete locationUpdate.longitude;
        }
        
        // Remove empty strings
        if (!locationUpdate.city) delete locationUpdate.city;
        if (!locationUpdate.state) delete locationUpdate.state;

        await updateUserProfile(token!, {
          display_name: formData.display_name,
          date_of_birth: formData.date_of_birth,
          gender: formData.gender,
          location: locationUpdate,
        })
      } else if (STEPS[currentStep].id === 'photos') {
        if (photos.length === 0) {
          throw new Error('Please upload at least one photo.')
        }
      } else if (STEPS[currentStep].id === 'preferences') {
        if (formData.looking_for.length === 0) {
          throw new Error('Please select what you are looking for.')
        }
        await updateUserProfile(token!, {
          looking_for: formData.looking_for,
          preferences: formData.preferences,
        })
      } else if (STEPS[currentStep].id === 'complete') {
        await completeOnboarding(token!)
        // Update local user context to prevent redirect loop
        if (user) {
            updateUser({
                ...user,
                onboarding_completed_at: new Date().toISOString()
            })
        }
        router.push('/dashboard')
        return
      }

      setCurrentStep(prev => prev + 1)
    } catch (err) {
      console.error('Onboarding error:', err)
      const msg = err instanceof Error ? err.message : 'An error occurred'
      setError(`${msg} (Please check console for details)`)
    } finally {
      setIsLoading(false)
    }
  }

  const handleBack = () => {
    setCurrentStep(prev => Math.max(0, prev - 1))
  }

  if (authLoading) return <div className="flex h-screen items-center justify-center"><Loader2 className="h-8 w-8 animate-spin" /></div>
  if (!isAuthenticated) {
    router.push('/login')
    return null
  }

  const renderStep = () => {
    switch (STEPS[currentStep].id) {
      case 'welcome':
        return (
          <div className="text-center space-y-4">
            <div className="flex justify-center mb-6">
              <div className="h-24 w-24 bg-blue-100 rounded-full flex items-center justify-center">
                <User className="h-12 w-12 text-blue-600" />
              </div>
            </div>
            <h2 className="text-2xl font-bold">Welcome to FWBer!</h2>
            <p className="text-gray-600">
              We&apos;re excited to have you here. Let&apos;s take a moment to set up your profile so you can start meeting people.
            </p>
            <p className="text-sm text-gray-500">
              This will only take a minute.
            </p>
          </div>
        )

      case 'basics':
        return (
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="display_name">Display Name</Label>
              <Input 
                id="display_name" 
                value={formData.display_name} 
                onChange={e => setFormData({...formData, display_name: e.target.value})}
                placeholder="What should we call you?"
              />
            </div>
            <div className="space-y-2">
              <Label>Date of Birth</Label>
              <div className="grid grid-cols-3 gap-2">
                <select
                  className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                  value={formData.date_of_birth ? formData.date_of_birth.split('-')[1] : ''}
                  onChange={e => {
                    const [y, m, d] = formData.date_of_birth ? formData.date_of_birth.split('-') : [`${new Date().getFullYear() - 18}`, '01', '01'];
                    setFormData({...formData, date_of_birth: `${y}-${e.target.value}-${d}`});
                  }}
                >
                  <option value="">Month</option>
                  {["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"].map((month, i) => (
                    <option key={i} value={(i + 1).toString().padStart(2, '0')}>{month}</option>
                  ))}
                </select>
                <select
                  className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                  value={formData.date_of_birth ? formData.date_of_birth.split('-')[2] : ''}
                  onChange={e => {
                    const [y, m, d] = formData.date_of_birth ? formData.date_of_birth.split('-') : [`${new Date().getFullYear() - 18}`, '01', '01'];
                    setFormData({...formData, date_of_birth: `${y}-${m}-${e.target.value}`});
                  }}
                >
                  <option value="">Day</option>
                  {Array.from({length: 31}, (_, i) => {
                    const d = (i + 1).toString().padStart(2, '0');
                    return <option key={d} value={d}>{i + 1}</option>
                  })}
                </select>
                <select
                  className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                  value={formData.date_of_birth ? formData.date_of_birth.split('-')[0] : ''}
                  onChange={e => {
                    const [y, m, d] = formData.date_of_birth ? formData.date_of_birth.split('-') : [`${new Date().getFullYear() - 18}`, '01', '01'];
                    setFormData({...formData, date_of_birth: `${e.target.value}-${m}-${d}`});
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
            <div className="space-y-2">
              <Label htmlFor="gender">Gender</Label>
              <select
                id="gender"
                className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                value={formData.gender}
                onChange={e => setFormData({...formData, gender: e.target.value})}
              >
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="non-binary">Non-binary</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="city">City</Label>
                <Input 
                  id="city" 
                  value={formData.location.city} 
                  onChange={e => setFormData({...formData, location: {...formData.location, city: e.target.value}})}
                  placeholder="City"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="state">State</Label>
                <Input 
                  id="state" 
                  value={formData.location.state} 
                  onChange={e => setFormData({...formData, location: {...formData.location, state: e.target.value}})}
                  placeholder="State"
                />
              </div>
            </div>
            <div className="flex flex-col items-end gap-1">
              <Button type="button" variant="outline" size="sm" onClick={handleUseCurrentLocation} disabled={isLoading}>
                <MapPin className="mr-2 h-4 w-4" />
                Use Current Location
              </Button>
              {formData.location.latitude !== 0 && (
                <p className="text-xs text-green-600">
                  Coordinates set: {formData.location.latitude.toFixed(4)}, {formData.location.longitude.toFixed(4)}
                </p>
              )}
            </div>
          </div>
        )

      case 'photos':
        return (
          <div className="space-y-4">
            <div className="text-center mb-4">
              <h3 className="text-lg font-medium">Add your best photos</h3>
              <p className="text-sm text-gray-500">Upload at least one photo to continue.</p>
            </div>
            <PhotoUpload 
              photos={photos} 
              onUpload={async (items, onProgress) => {
                await uploadPhotos(items, onProgress)
              }}
              onRemove={(index) => deletePhoto(photos[index].id)}
              maxPhotos={6}
            />
          </div>
        )

      case 'preferences':
        return (
          <div className="space-y-6">
            <div className="space-y-3">
              <Label>I&apos;m looking for...</Label>
              <div className="grid grid-cols-2 gap-2">
                {['Dating', 'Friends', 'Networking', 'Casual'].map(opt => {
                  const mapping: Record<string, string> = {
                    'Dating': 'dating',
                    'Friends': 'friendship',
                    'Networking': 'networking',
                    'Casual': 'casual'
                  }
                  const val = mapping[opt] || opt.toLowerCase()
                  
                  return (
                  <div key={opt} className="flex items-center space-x-2">
                    <input
                      type="checkbox"
                      id={`looking_${opt}`}
                      className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      checked={formData.looking_for.includes(val)}
                      onChange={e => {
                        setFormData(prev => ({
                          ...prev,
                          looking_for: e.target.checked 
                            ? [...prev.looking_for, val]
                            : prev.looking_for.filter(i => i !== val)
                        }))
                      }}
                    />
                    <Label htmlFor={`looking_${opt}`}>{opt}</Label>
                  </div>
                )})}
              </div>
            </div>

            <div className="space-y-4">
              <Label>Age Range ({formData.preferences.age_range_min} - {formData.preferences.age_range_max})</Label>
              <div className="flex items-center space-x-4">
                <Input 
                  type="number" 
                  min={18} 
                  max={99}
                  value={formData.preferences.age_range_min}
                  onChange={e => setFormData({
                    ...formData, 
                    preferences: { ...formData.preferences, age_range_min: parseInt(e.target.value) }
                  })}
                  className="w-20"
                />
                <span>to</span>
                <Input 
                  type="number" 
                  min={18} 
                  max={99}
                  value={formData.preferences.age_range_max}
                  onChange={e => setFormData({
                    ...formData, 
                    preferences: { ...formData.preferences, age_range_max: parseInt(e.target.value) }
                  })}
                  className="w-20"
                />
              </div>
            </div>
          </div>
        )

      case 'complete':
        return (
          <div className="text-center space-y-4">
            <div className="flex justify-center mb-6">
              <CheckCircle2 className="h-24 w-24 text-green-500" />
            </div>
            <h2 className="text-2xl font-bold">You&apos;re All Set!</h2>
            <p className="text-gray-600">
              Your profile is ready. Start exploring and meeting new people.
            </p>
          </div>
        )
    }
  }

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
      <Card className="w-full max-w-lg">
        <CardHeader>
          <div className="flex justify-between items-center mb-4">
            {STEPS.map((step, idx) => (
              <div key={step.id} className="flex flex-col items-center">
                <div className={`h-2 w-full rounded-full mb-2 ${idx <= currentStep ? 'bg-blue-600' : 'bg-gray-200'}`} style={{width: '60px'}}></div>
              </div>
            ))}
          </div>
          <CardTitle>{STEPS[currentStep].title}</CardTitle>
        </CardHeader>
        <CardContent>
          {error && (
            <div className="bg-red-50 text-red-600 p-3 rounded-md mb-4 text-sm">
              {error}
            </div>
          )}
          {renderStep()}
        </CardContent>
        <CardFooter className="flex justify-between">
          <Button 
            variant="outline" 
            onClick={handleBack}
            disabled={currentStep === 0 || isLoading}
            className={currentStep === 0 ? 'invisible' : ''}
          >
            Back
          </Button>
          <Button onClick={handleNext} disabled={isLoading}>
            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            {currentStep === STEPS.length - 1 ? 'Finish' : 'Next'}
          </Button>
        </CardFooter>
      </Card>
    </div>
  )
}
