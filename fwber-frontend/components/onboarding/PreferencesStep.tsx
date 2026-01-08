import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Dispatch, SetStateAction } from 'react'

export interface OnboardingFormData {
  looking_for: string[]
  preferences: {
    age_range_min: number
    age_range_max: number
  }
  [key: string]: any
}

interface PreferencesStepProps {
  formData: OnboardingFormData
  setFormData: Dispatch<SetStateAction<OnboardingFormData>>
}

export function PreferencesStep({ formData, setFormData }: PreferencesStepProps) {
  const handleLookingForChange = (val: string, checked: boolean) => {
    setFormData((prev) => ({
      ...prev,
      looking_for: checked
        ? [...prev.looking_for, val]
        : prev.looking_for.filter((i) => i !== val)
    }))
  }

  const handleAgeChange = (field: 'age_range_min' | 'age_range_max', value: string) => {
    const intVal = parseInt(value) || 18
    setFormData((prev) => ({
      ...prev,
      preferences: {
        ...prev.preferences,
        [field]: intVal
      }
    }))
  }

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
                  onChange={e => handleLookingForChange(val, e.target.checked)}
                />
                <Label htmlFor={`looking_${opt}`}>{opt}</Label>
              </div>
            )
          })}
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
            onChange={e => handleAgeChange('age_range_min', e.target.value)}
            className="w-20"
          />
          <span>to</span>
          <Input 
            type="number" 
            min={18} 
            max={99}
            value={formData.preferences.age_range_max}
            onChange={e => handleAgeChange('age_range_max', e.target.value)}
            className="w-20"
          />
        </div>
        {formData.preferences.age_range_min > formData.preferences.age_range_max && (
          <p className="text-sm text-red-500">Min age cannot be greater than max age.</p>
        )}
      </div>
    </div>
  )
}
