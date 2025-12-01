'use client';

import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { 
  User, Mail, MapPin, Calendar, Heart, MessageSquare, 
  Save, Loader2, CheckCircle2, AlertCircle, Info, Eye, EyeOff 
} from 'lucide-react';
import { api } from '@/lib/api/client';
import { BioGenerator } from '@/components/ai/BioGenerator';

interface UserProfile {
  name: string;
  email: string;
  bio?: string;
  age?: number;
  gender?: string;
  interested_in?: string[];
  location_city?: string;
  location_state?: string;
  interests?: string[];
  relationship_status?: string;
  looking_for?: string[];
  height_cm?: number;
  body_type?: string;
  ethnicity?: string;
  occupation?: string;
  education?: string;
  smoking?: string;
  drinking?: string;
  has_kids?: boolean;
  wants_kids?: boolean;
}

interface ValidationErrors {
  [key: string]: string;
}

export default function EnhancedProfileEditor() {
  const [profile, setProfile] = useState<UserProfile>({
    name: '',
    email: '',
    interested_in: [],
    interests: [],
    looking_for: [],
  });
  const [errors, setErrors] = useState<ValidationErrors>({});
  const [touched, setTouched] = useState<Set<string>>(new Set());
  const [showSuccess, setShowSuccess] = useState(false);
  const queryClient = useQueryClient();

  // Fetch current profile
  const { data: currentProfile, isLoading } = useQuery({
    queryKey: ['user-profile'],
    queryFn: async () => {
      return api.get<UserProfile>('/user');
    },
  });

  useEffect(() => {
    if (currentProfile) {
      setProfile(currentProfile);
    }
  }, [currentProfile]);

  // Real-time validation
  useEffect(() => {
    const newErrors: ValidationErrors = {};

    if (touched.has('name') && !profile.name) {
      newErrors.name = 'Name is required';
    } else if (touched.has('name') && profile.name.length < 2) {
      newErrors.name = 'Name must be at least 2 characters';
    } else if (touched.has('name') && profile.name.length > 50) {
      newErrors.name = 'Name must be less than 50 characters';
    }

    if (touched.has('bio') && profile.bio && profile.bio.length > 500) {
      newErrors.bio = 'Bio must be less than 500 characters';
    }

    if (touched.has('age') && profile.age) {
      if (profile.age < 18) {
        newErrors.age = 'You must be at least 18 years old';
      } else if (profile.age > 120) {
        newErrors.age = 'Please enter a valid age';
      }
    }

    if (touched.has('email') && profile.email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(profile.email)) {
        newErrors.email = 'Please enter a valid email address';
      }
    }

    if (touched.has('height_cm') && profile.height_cm) {
      if (profile.height_cm < 140 || profile.height_cm > 220) {
        newErrors.height_cm = 'Height must be between 140-220 cm';
      }
    }

    setErrors(newErrors);
  }, [profile, touched]);

  const updateMutation = useMutation({
    mutationFn: async (updatedProfile: UserProfile) => {
      return api.put<UserProfile>('/user', updatedProfile);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user-profile'] });
      setShowSuccess(true);
      setTimeout(() => setShowSuccess(false), 3000);
    },
  });

  const handleChange = (field: keyof UserProfile, value: any) => {
    setProfile({ ...profile, [field]: value });
    setTouched(prev => new Set(prev).add(field));
  };

  const handleArrayToggle = (field: 'interested_in' | 'interests' | 'looking_for', value: string) => {
    const current = profile[field] || [];
    const updated = current.includes(value)
      ? current.filter(item => item !== value)
      : [...current, value];
    handleChange(field, updated);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Mark all fields as touched
    const allFields = Object.keys(profile);
    setTouched(new Set(allFields));

    // Check for errors
    if (Object.keys(errors).length === 0) {
      updateMutation.mutate(profile);
    }
  };

  const calculateCompleteness = () => {
    const requiredFields = ['name', 'email', 'age', 'gender', 'bio'];
    const optionalFields = ['location_city', 'interested_in', 'interests', 'looking_for', 'relationship_status'];
    
    let completed = 0;
    let total = requiredFields.length + optionalFields.length;

    requiredFields.forEach(field => {
      if (profile[field as keyof UserProfile]) completed++;
    });

    optionalFields.forEach(field => {
      const value = profile[field as keyof UserProfile];
      if (value && (Array.isArray(value) ? value.length > 0 : true)) completed++;
    });

    return Math.round((completed / total) * 100);
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <Loader2 className="w-8 h-8 animate-spin text-purple-600" />
      </div>
    );
  }

  const completeness = calculateCompleteness();

  return (
    <div className="max-w-4xl mx-auto p-6">
      {/* Header with Completeness */}
      <div className="mb-8">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Edit Profile</h1>
            <p className="text-gray-600 mt-1">Update your information and preferences</p>
          </div>
          <div className="text-right">
            <div className="text-sm text-gray-600 mb-1">Profile Completeness</div>
            <div className="flex items-center gap-2">
              <div className="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div 
                  className="h-full bg-gradient-to-r from-purple-600 to-pink-600 transition-all duration-500"
                  style={{ width: `${completeness}%` }}
                />
              </div>
              <span className="text-lg font-bold text-purple-600">{completeness}%</span>
            </div>
          </div>
        </div>

        {/* Success Message */}
        {showSuccess && (
          <div className="p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3 mb-4">
            <CheckCircle2 className="w-5 h-5 text-green-600" />
            <span className="text-green-800 font-medium">Profile updated successfully!</span>
          </div>
        )}

        {/* Error Summary */}
        {Object.keys(errors).length > 0 && touched.size > 0 && (
          <div className="p-4 bg-red-50 border border-red-200 rounded-lg mb-4">
            <div className="flex items-start gap-3">
              <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
              <div>
                <h4 className="font-semibold text-red-900 mb-1">Please fix the following errors:</h4>
                <ul className="text-sm text-red-700 list-disc list-inside space-y-1">
                  {Object.entries(errors).map(([field, error]) => (
                    <li key={field}>{error}</li>
                  ))}
                </ul>
              </div>
            </div>
          </div>
        )}
      </div>

      <form onSubmit={handleSubmit} className="space-y-8">
        {/* Basic Information */}
        <Section title="Basic Information" icon={<User />}>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              label="Name"
              required
              error={errors.name}
              touched={touched.has('name')}
            >
              <input
                type="text"
                value={profile.name}
                onChange={(e) => handleChange('name', e.target.value)}
                onBlur={() => setTouched(prev => new Set(prev).add('name'))}
                className={inputClassName(errors.name, touched.has('name'))}
                placeholder="Your display name"
              />
            </FormField>

            <FormField
              label="Email"
              required
              error={errors.email}
              touched={touched.has('email')}
            >
              <input
                type="email"
                value={profile.email}
                onChange={(e) => handleChange('email', e.target.value)}
                onBlur={() => setTouched(prev => new Set(prev).add('email'))}
                className={inputClassName(errors.email, touched.has('email'))}
                placeholder="your@email.com"
              />
            </FormField>

            <FormField
              label="Age"
              required
              error={errors.age}
              touched={touched.has('age')}
            >
              <input
                type="number"
                value={profile.age || ''}
                onChange={(e) => handleChange('age', parseInt(e.target.value))}
                onBlur={() => setTouched(prev => new Set(prev).add('age'))}
                className={inputClassName(errors.age, touched.has('age'))}
                placeholder="25"
                min="18"
                max="120"
              />
            </FormField>

            <FormField label="Gender" required>
              <select
                value={profile.gender || ''}
                onChange={(e) => handleChange('gender', e.target.value)}
                className={inputClassName()}
              >
                <option value="">Select gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="non-binary">Non-binary</option>
                <option value="other">Other</option>
              </select>
            </FormField>
          </div>

          <FormField
            label="Bio"
            required
            error={errors.bio}
            touched={touched.has('bio')}
            hint={profile.bio ? `${profile.bio.length}/500 characters` : '0/500 characters'}
          >
            <div className="space-y-3">
              <textarea
                value={profile.bio || ''}
                onChange={(e) => handleChange('bio', e.target.value)}
                onBlur={() => setTouched(prev => new Set(prev).add('bio'))}
                className={`${inputClassName(errors.bio, touched.has('bio'))} min-h-[100px]`}
                placeholder="Tell us about yourself..."
                maxLength={500}
              />
              <BioGenerator 
                currentBio={profile.bio || ''}
                onSelectBio={(bio) => handleChange('bio', bio)}
              />
            </div>
          </FormField>
        </Section>

        {/* Location */}
        <Section title="Location" icon={<MapPin />}>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField label="City">
              <input
                type="text"
                value={profile.location_city || ''}
                onChange={(e) => handleChange('location_city', e.target.value)}
                className={inputClassName()}
                placeholder="San Francisco"
              />
            </FormField>

            <FormField label="State/Province">
              <input
                type="text"
                value={profile.location_state || ''}
                onChange={(e) => handleChange('location_state', e.target.value)}
                className={inputClassName()}
                placeholder="California"
              />
            </FormField>
          </div>
        </Section>

        {/* Dating Preferences */}
        <Section title="Dating Preferences" icon={<Heart />}>
          <FormField label="Interested In">
            <div className="flex flex-wrap gap-2">
              {['men', 'women', 'non-binary', 'everyone'].map(option => (
                <ToggleChip
                  key={option}
                  label={option.charAt(0).toUpperCase() + option.slice(1)}
                  selected={(profile.interested_in || []).includes(option)}
                  onClick={() => handleArrayToggle('interested_in', option)}
                />
              ))}
            </div>
          </FormField>

          <FormField label="Looking For">
            <div className="flex flex-wrap gap-2">
              {['casual', 'friends', 'dating', 'relationship', 'not-sure'].map(option => (
                <ToggleChip
                  key={option}
                  label={option.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')}
                  selected={(profile.looking_for || []).includes(option)}
                  onClick={() => handleArrayToggle('looking_for', option)}
                />
              ))}
            </div>
          </FormField>

          <FormField label="Relationship Status">
            <select
              value={profile.relationship_status || ''}
              onChange={(e) => handleChange('relationship_status', e.target.value)}
              className={inputClassName()}
            >
              <option value="">Select status</option>
              <option value="single">Single</option>
              <option value="divorced">Divorced</option>
              <option value="separated">Separated</option>
              <option value="widowed">Widowed</option>
              <option value="open-relationship">Open Relationship</option>
            </select>
          </FormField>
        </Section>

        {/* Interests */}
        <Section title="Interests & Hobbies" icon={<MessageSquare />}>
          <FormField label="Select your interests" hint="Choose all that apply">
            <div className="flex flex-wrap gap-2">
              {['music', 'movies', 'sports', 'gaming', 'travel', 'cooking', 'reading', 'art', 'fitness', 'outdoors', 'tech', 'nightlife'].map(interest => (
                <ToggleChip
                  key={interest}
                  label={interest.charAt(0).toUpperCase() + interest.slice(1)}
                  selected={(profile.interests || []).includes(interest)}
                  onClick={() => handleArrayToggle('interests', interest)}
                />
              ))}
            </div>
          </FormField>
        </Section>

        {/* Physical Attributes */}
        <Section title="Physical Attributes" icon={<User />}>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <FormField label="Height (cm)" error={errors.height_cm} touched={touched.has('height_cm')}>
              <input
                type="number"
                value={profile.height_cm || ''}
                onChange={(e) => handleChange('height_cm', parseInt(e.target.value))}
                onBlur={() => setTouched(prev => new Set(prev).add('height_cm'))}
                className={inputClassName(errors.height_cm, touched.has('height_cm'))}
                placeholder="170"
                min="140"
                max="220"
              />
            </FormField>

            <FormField label="Body Type">
              <select
                value={profile.body_type || ''}
                onChange={(e) => handleChange('body_type', e.target.value)}
                className={inputClassName()}
              >
                <option value="">Select</option>
                <option value="slim">Slim</option>
                <option value="athletic">Athletic</option>
                <option value="average">Average</option>
                <option value="curvy">Curvy</option>
                <option value="heavyset">Heavyset</option>
              </select>
            </FormField>

            <FormField label="Ethnicity">
              <select
                value={profile.ethnicity || ''}
                onChange={(e) => handleChange('ethnicity', e.target.value)}
                className={inputClassName()}
              >
                <option value="">Select</option>
                <option value="asian">Asian</option>
                <option value="black">Black</option>
                <option value="hispanic">Hispanic/Latino</option>
                <option value="white">White</option>
                <option value="middle_eastern">Middle Eastern</option>
                <option value="mixed">Mixed</option>
                <option value="other">Other</option>
              </select>
            </FormField>
          </div>
        </Section>

        {/* Lifestyle */}
        <Section title="Lifestyle" icon={<Calendar />}>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField label="Occupation">
              <input
                type="text"
                value={profile.occupation || ''}
                onChange={(e) => handleChange('occupation', e.target.value)}
                className={inputClassName()}
                placeholder="Software Engineer"
              />
            </FormField>

            <FormField label="Education">
              <select
                value={profile.education || ''}
                onChange={(e) => handleChange('education', e.target.value)}
                className={inputClassName()}
              >
                <option value="">Select</option>
                <option value="high-school">High School</option>
                <option value="some-college">Some College</option>
                <option value="bachelors">Bachelor&apos;s Degree</option>
                <option value="masters">Master&apos;s Degree</option>
                <option value="phd">PhD</option>
              </select>
            </FormField>

            <FormField label="Smoking">
              <select
                value={profile.smoking || ''}
                onChange={(e) => handleChange('smoking', e.target.value)}
                className={inputClassName()}
              >
                <option value="">Select</option>
                <option value="never">Never</option>
                <option value="socially">Socially</option>
                <option value="regularly">Regularly</option>
                <option value="trying-to-quit">Trying to Quit</option>
              </select>
            </FormField>

            <FormField label="Drinking">
              <select
                value={profile.drinking || ''}
                onChange={(e) => handleChange('drinking', e.target.value)}
                className={inputClassName()}
              >
                <option value="">Select</option>
                <option value="never">Never</option>
                <option value="socially">Socially</option>
                <option value="regularly">Regularly</option>
              </select>
            </FormField>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField label="Has Kids">
              <select
                value={profile.has_kids === undefined ? '' : profile.has_kids.toString()}
                onChange={(e) => handleChange('has_kids', e.target.value === 'true')}
                className={inputClassName()}
              >
                <option value="">Prefer not to say</option>
                <option value="false">No</option>
                <option value="true">Yes</option>
              </select>
            </FormField>

            <FormField label="Wants Kids">
              <select
                value={profile.wants_kids === undefined ? '' : profile.wants_kids.toString()}
                onChange={(e) => handleChange('wants_kids', e.target.value === 'true')}
                className={inputClassName()}
              >
                <option value="">Prefer not to say</option>
                <option value="false">No</option>
                <option value="true">Yes</option>
              </select>
            </FormField>
          </div>
        </Section>

        {/* Submit Button */}
        <div className="flex items-center justify-end gap-4 pt-6 border-t">
          <button
            type="button"
            onClick={() => window.history.back()}
            className="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={updateMutation.isPending || Object.keys(errors).length > 0}
            className="flex items-center gap-2 px-8 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors font-medium"
          >
            {updateMutation.isPending ? (
              <>
                <Loader2 className="w-5 h-5 animate-spin" />
                Saving...
              </>
            ) : (
              <>
                <Save className="w-5 h-5" />
                Save Changes
              </>
            )}
          </button>
        </div>
      </form>
    </div>
  );
}

// Helper Components
function Section({ title, icon, children }: { title: string; icon: React.ReactNode; children: React.ReactNode }) {
  return (
    <div className="bg-white rounded-xl border border-gray-200 p-6">
      <div className="flex items-center gap-2 mb-4">
        <div className="text-purple-600">{icon}</div>
        <h2 className="text-xl font-semibold text-gray-900">{title}</h2>
      </div>
      <div className="space-y-4">
        {children}
      </div>
    </div>
  );
}

function FormField({ 
  label, 
  children, 
  required, 
  error, 
  touched, 
  hint 
}: { 
  label: string; 
  children: React.ReactNode; 
  required?: boolean;
  error?: string;
  touched?: boolean;
  hint?: string;
}) {
  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-2">
        {label}
        {required && <span className="text-red-500 ml-1">*</span>}
      </label>
      {children}
      {hint && !error && (
        <p className="mt-1 text-xs text-gray-500">{hint}</p>
      )}
      {error && touched && (
        <p className="mt-1 text-xs text-red-600 flex items-center gap-1">
          <AlertCircle className="w-3 h-3" />
          {error}
        </p>
      )}
    </div>
  );
}

function ToggleChip({ 
  label, 
  selected, 
  onClick 
}: { 
  label: string; 
  selected: boolean; 
  onClick: () => void;
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`px-4 py-2 rounded-full border-2 transition-all font-medium text-sm ${
        selected
          ? 'bg-purple-600 border-purple-600 text-white'
          : 'bg-white border-gray-300 text-gray-700 hover:border-purple-600 hover:text-purple-600'
      }`}
    >
      {label}
    </button>
  );
}

function inputClassName(error?: string, touched?: boolean) {
  const baseClass = 'w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors';
  if (error && touched) {
    return `${baseClass} border-red-300 focus:ring-red-500`;
  }
  return `${baseClass} border-gray-300`;
}
