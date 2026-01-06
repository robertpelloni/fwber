'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import * as z from 'zod'
import { useAuth } from '@/lib/auth-context'
import { registerMerchant } from '@/lib/api/merchant'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { useToast } from '@/components/ui/use-toast'
import { Loader2, Store } from 'lucide-react'

const formSchema = z.object({
  business_name: z.string().min(2, 'Business name must be at least 2 characters'),
  category: z.string().min(1, 'Please select a category'),
  description: z.string().optional(),
  address: z.string().optional(),
})

type FormData = z.infer<typeof formSchema>

const CATEGORIES = [
  'Restaurant',
  'Cafe',
  'Bar',
  'Retail',
  'Services',
  'Entertainment',
  'Other',
]

export default function MerchantRegisterPage() {
  const { user, token, updateUser } = useAuth()
  const router = useRouter()
  const { toast } = useToast()
  const [isLoading, setIsLoading] = useState(false)

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      business_name: '',
      category: '',
      description: '',
      address: '',
    },
  })

  async function onSubmit(values: FormData) {
    if (!token) return

    setIsLoading(true)
    try {
      await registerMerchant(token, values)
      
      // Update local user context to include new role
      if (user) {
        updateUser({ ...user, role: 'merchant' })
      }

      toast({
        title: "Welcome to fwber Merchant!",
        description: "Your merchant profile has been created successfully.",
      })

      router.push('/merchant/dashboard')
    } catch (error) {
      toast({
        variant: "destructive",
        title: "Registration failed",
        description: error instanceof Error ? error.message : "Something went wrong",
      })
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="max-w-2xl mx-auto px-4 py-8">
      <div className="text-center mb-8">
        <div className="bg-amber-100 dark:bg-amber-900/30 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
          <Store className="w-8 h-8 text-amber-600 dark:text-amber-400" />
        </div>
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Become a Merchant</h1>
        <p className="mt-2 text-gray-600 dark:text-gray-400">
          Create your business profile to start offering promotions to fwber users.
        </p>
      </div>

      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          <div className="space-y-2">
            <Label htmlFor="business_name">Business Name</Label>
            <Input 
              id="business_name" 
              placeholder="e.g. Joe's Pizza" 
              {...register('business_name')} 
            />
            {errors.business_name && (
              <p className="text-sm text-red-500">{errors.business_name.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="category">Category</Label>
            <select
              id="category"
              className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              {...register('category')}
            >
              <option value="" disabled>Select a category</option>
              {CATEGORIES.map((cat) => (
                <option key={cat} value={cat}>
                  {cat}
                </option>
              ))}
            </select>
            {errors.category && (
              <p className="text-sm text-red-500">{errors.category.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="address">Address (Optional)</Label>
            <Input 
              id="address" 
              placeholder="e.g. 123 Main St, New York, NY" 
              {...register('address')} 
            />
            <p className="text-xs text-gray-500 dark:text-gray-400">
              Helping customers find your physical location.
            </p>
            {errors.address && (
              <p className="text-sm text-red-500">{errors.address.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Description (Optional)</Label>
            <Textarea 
              id="description"
              placeholder="Tell us about your business..." 
              className="resize-none" 
              {...register('description')} 
            />
            {errors.description && (
              <p className="text-sm text-red-500">{errors.description.message}</p>
            )}
          </div>

          <Button type="submit" className="w-full bg-amber-500 hover:bg-amber-600 text-white" disabled={isLoading}>
            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Create Merchant Profile
          </Button>
        </form>
      </div>
    </div>
  )
}
