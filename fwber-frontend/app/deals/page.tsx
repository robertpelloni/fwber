'use client'

import { useState, useEffect, useMemo } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { apiClient } from '@/lib/api/client'
import { useGeolocation } from '@/hooks/useGeolocation'
import { 
  Tag, MapPin, Clock, Percent, Store, Filter, ChevronDown,
  ArrowLeft, Sparkles, Gift, Coffee, Utensils, ShoppingBag,
  Ticket, Heart, Star, Navigation, RefreshCw
} from 'lucide-react'
import Link from 'next/link'

interface MerchantInfo {
  id: number
  business_name: string
  category: string | null
  address: string | null
}

interface Deal {
  id: number
  title: string
  description: string
  promo_code: string | null
  discount_value: number
  lat: number
  lng: number
  radius: number
  token_cost: number
  starts_at: string
  expires_at: string
  is_active: boolean
  merchant: MerchantInfo
}

interface DealsResponse {
  data: Deal[]
  current_page: number
  last_page: number
  total: number
}

const categoryIcons: Record<string, React.ComponentType<{ className?: string }>> = {
  'food': Utensils,
  'restaurant': Utensils,
  'cafe': Coffee,
  'coffee': Coffee,
  'retail': ShoppingBag,
  'shopping': ShoppingBag,
  'entertainment': Ticket,
  'dating': Heart,
  'default': Store,
}

const sortOptions = [
  { value: 'distance', label: 'Nearest First' },
  { value: 'discount', label: 'Best Discount' },
  { value: 'expiring', label: 'Expiring Soon' },
  { value: 'newest', label: 'Newest' },
]

const radiusOptions = [
  { value: 1000, label: '1 km' },
  { value: 2000, label: '2 km' },
  { value: 5000, label: '5 km' },
  { value: 10000, label: '10 km' },
  { value: 25000, label: '25 km' },
]

function getTimeRemaining(expiresAt: string): string {
  const now = new Date()
  const expires = new Date(expiresAt)
  const diff = expires.getTime() - now.getTime()
  
  if (diff <= 0) return 'Expired'
  
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const days = Math.floor(hours / 24)
  
  if (days > 0) return `${days}d left`
  if (hours > 0) return `${hours}h left`
  return 'Expiring soon!'
}

function getCategoryIcon(category: string | null): React.ComponentType<{ className?: string }> {
  if (!category) return categoryIcons.default
  const lower = category.toLowerCase()
  for (const [key, icon] of Object.entries(categoryIcons)) {
    if (lower.includes(key)) return icon
  }
  return categoryIcons.default
}

export default function DealsPage() {
  const [deals, setDeals] = useState<Deal[]>([])
  const [categories, setCategories] = useState<string[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [selectedCategory, setSelectedCategory] = useState<string>('all')
  const [sortBy, setSortBy] = useState('distance')
  const [radius, setRadius] = useState(5000)
  const [showFilters, setShowFilters] = useState(false)
  const [page, setPage] = useState(1)
  const [hasMore, setHasMore] = useState(false)

  const { latitude, longitude, error: geoError, loading: geoLoading } = useGeolocation()

  useEffect(() => {
    async function fetchCategories() {
      try {
        const response = await apiClient.get<{ categories: string[] }>('/deals/categories')
        setCategories(response.categories || [])
      } catch (err) {
        console.error('Failed to fetch categories:', err)
      }
    }
    fetchCategories()
  }, [])

  useEffect(() => {
    async function fetchDeals() {
      if (!latitude || !longitude) return

      setLoading(true)
      setError(null)

      try {
        const params = new URLSearchParams({
          lat: latitude.toString(),
          lng: longitude.toString(),
          radius: radius.toString(),
          sort: sortBy,
          page: page.toString(),
          per_page: '20',
        })

        if (selectedCategory !== 'all') {
          params.append('category', selectedCategory)
        }

        const response = await apiClient.get<DealsResponse>(`/deals?${params}`)
        
        if (page === 1) {
          setDeals(response.data || [])
        } else {
          setDeals(prev => [...prev, ...(response.data || [])])
        }
        
        setHasMore(response.current_page < response.last_page)
      } catch (err) {
        console.error('Failed to fetch deals:', err)
        setError('Failed to load deals. Please try again.')
      } finally {
        setLoading(false)
      }
    }

    fetchDeals()
  }, [latitude, longitude, radius, sortBy, selectedCategory, page])

  const handleFilterChange = (newCategory: string) => {
    setSelectedCategory(newCategory)
    setPage(1)
  }

  const handleSortChange = (newSort: string) => {
    setSortBy(newSort)
    setPage(1)
  }

  const handleRadiusChange = (newRadius: number) => {
    setRadius(newRadius)
    setPage(1)
  }

  const handleRefresh = () => {
    setPage(1)
  }

  if (geoLoading) {
    return (
      <ProtectedRoute>
        <div className="min-h-screen bg-gradient-to-b from-amber-50 to-orange-50 flex items-center justify-center">
          <div className="text-center">
            <Navigation className="w-12 h-12 text-amber-500 animate-pulse mx-auto mb-4" />
            <p className="text-gray-600">Getting your location...</p>
          </div>
        </div>
      </ProtectedRoute>
    )
  }

  if (geoError) {
    return (
      <ProtectedRoute>
        <div className="min-h-screen bg-gradient-to-b from-amber-50 to-orange-50">
          <AppHeader />
          <div className="max-w-2xl mx-auto px-4 py-12 text-center">
            <MapPin className="w-16 h-16 text-red-400 mx-auto mb-4" />
            <h2 className="text-xl font-semibold text-gray-900 mb-2">Location Required</h2>
            <p className="text-gray-600 mb-6">
              We need your location to show nearby deals. Please enable location access in your browser.
            </p>
            <Link 
              href="/home"
              className="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition"
            >
              <ArrowLeft className="w-4 h-4" />
              Back to Home
            </Link>
          </div>
        </div>
      </ProtectedRoute>
    )
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gradient-to-b from-amber-50 to-orange-50">
        <AppHeader />
        
        <main className="max-w-4xl mx-auto px-4 py-6">
          {/* Header */}
          <div className="flex items-center justify-between mb-6">
            <div className="flex items-center gap-3">
              <Link 
                href="/home" 
                className="p-2 -ml-2 hover:bg-amber-100 rounded-lg transition"
              >
                <ArrowLeft className="w-5 h-5 text-gray-600" />
              </Link>
              <div>
                <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                  <Tag className="w-7 h-7 text-amber-500" />
                  Local Deals
                </h1>
                <p className="text-sm text-gray-500">
                  Exclusive offers from nearby merchants
                </p>
              </div>
            </div>
            <button
              onClick={handleRefresh}
              className="p-2 hover:bg-amber-100 rounded-lg transition"
              title="Refresh deals"
            >
              <RefreshCw className={`w-5 h-5 text-gray-600 ${loading ? 'animate-spin' : ''}`} />
            </button>
          </div>

          {/* Filters Toggle */}
          <button
            onClick={() => setShowFilters(!showFilters)}
            className="w-full mb-4 flex items-center justify-between px-4 py-3 bg-white rounded-xl shadow-sm border border-amber-100"
          >
            <div className="flex items-center gap-2">
              <Filter className="w-5 h-5 text-amber-500" />
              <span className="font-medium text-gray-700">Filters</span>
              {selectedCategory !== 'all' && (
                <span className="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs rounded-full">
                  {selectedCategory}
                </span>
              )}
            </div>
            <ChevronDown className={`w-5 h-5 text-gray-400 transition-transform ${showFilters ? 'rotate-180' : ''}`} />
          </button>

          {/* Filters Panel */}
          {showFilters && (
            <div className="mb-6 p-4 bg-white rounded-xl shadow-sm border border-amber-100 space-y-4">
              {/* Categories */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <div className="flex flex-wrap gap-2">
                  <button
                    onClick={() => handleFilterChange('all')}
                    className={`px-3 py-1.5 rounded-full text-sm font-medium transition ${
                      selectedCategory === 'all'
                        ? 'bg-amber-500 text-white'
                        : 'bg-amber-50 text-amber-700 hover:bg-amber-100'
                    }`}
                  >
                    All
                  </button>
                  {categories.map(cat => (
                    <button
                      key={cat}
                      onClick={() => handleFilterChange(cat)}
                      className={`px-3 py-1.5 rounded-full text-sm font-medium transition ${
                        selectedCategory === cat
                          ? 'bg-amber-500 text-white'
                          : 'bg-amber-50 text-amber-700 hover:bg-amber-100'
                      }`}
                    >
                      {cat}
                    </button>
                  ))}
                </div>
              </div>

              {/* Radius */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Search Radius</label>
                <div className="flex flex-wrap gap-2">
                  {radiusOptions.map(opt => (
                    <button
                      key={opt.value}
                      onClick={() => handleRadiusChange(opt.value)}
                      className={`px-3 py-1.5 rounded-full text-sm font-medium transition ${
                        radius === opt.value
                          ? 'bg-amber-500 text-white'
                          : 'bg-amber-50 text-amber-700 hover:bg-amber-100'
                      }`}
                    >
                      {opt.label}
                    </button>
                  ))}
                </div>
              </div>

              {/* Sort */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                <div className="flex flex-wrap gap-2">
                  {sortOptions.map(opt => (
                    <button
                      key={opt.value}
                      onClick={() => handleSortChange(opt.value)}
                      className={`px-3 py-1.5 rounded-full text-sm font-medium transition ${
                        sortBy === opt.value
                          ? 'bg-amber-500 text-white'
                          : 'bg-amber-50 text-amber-700 hover:bg-amber-100'
                      }`}
                    >
                      {opt.label}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* Stats Bar */}
          <div className="flex items-center gap-4 mb-6 text-sm text-gray-500">
            <span className="flex items-center gap-1">
              <MapPin className="w-4 h-4" />
              Within {radius >= 1000 ? `${radius / 1000} km` : `${radius} m`}
            </span>
            <span>•</span>
            <span>{deals.length} deal{deals.length !== 1 ? 's' : ''} found</span>
          </div>

          {/* Deals Grid */}
          {loading && deals.length === 0 ? (
            <div className="grid gap-4 sm:grid-cols-2">
              {[...Array(4)].map((_, i) => (
                <div key={i} className="bg-white rounded-xl p-4 shadow-sm animate-pulse">
                  <div className="flex gap-3">
                    <div className="w-12 h-12 bg-amber-100 rounded-lg" />
                    <div className="flex-1 space-y-2">
                      <div className="h-4 bg-gray-200 rounded w-3/4" />
                      <div className="h-3 bg-gray-100 rounded w-1/2" />
                    </div>
                  </div>
                  <div className="mt-3 h-10 bg-gray-100 rounded" />
                </div>
              ))}
            </div>
          ) : error ? (
            <div className="text-center py-12">
              <p className="text-red-500 mb-4">{error}</p>
              <button
                onClick={handleRefresh}
                className="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition"
              >
                Try Again
              </button>
            </div>
          ) : deals.length === 0 ? (
            <div className="text-center py-12">
              <Gift className="w-16 h-16 text-amber-300 mx-auto mb-4" />
              <h3 className="text-lg font-semibold text-gray-900 mb-2">No Deals Nearby</h3>
              <p className="text-gray-500 mb-4">
                Try expanding your search radius or check back later for new offers.
              </p>
              <button
                onClick={() => handleRadiusChange(25000)}
                className="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition"
              >
                Search 25 km
              </button>
            </div>
          ) : (
            <>
              <div className="grid gap-4 sm:grid-cols-2">
                {deals.map(deal => {
                  const CategoryIcon = getCategoryIcon(deal.merchant.category)
                  const timeLeft = getTimeRemaining(deal.expires_at)
                  const isExpiringSoon = timeLeft.includes('h') || timeLeft.includes('soon')

                  return (
                    <div
                      key={deal.id}
                      className="bg-white rounded-xl shadow-sm border border-amber-100 overflow-hidden hover:shadow-md transition"
                    >
                      {/* Deal Header */}
                      <div className="p-4">
                        <div className="flex items-start gap-3">
                          <div className="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-lg flex items-center justify-center flex-shrink-0">
                            <CategoryIcon className="w-6 h-6 text-white" />
                          </div>
                          <div className="flex-1 min-w-0">
                            <h3 className="font-semibold text-gray-900 truncate">{deal.title}</h3>
                            <p className="text-sm text-gray-500 flex items-center gap-1">
                              <Store className="w-3.5 h-3.5" />
                              {deal.merchant.business_name}
                            </p>
                          </div>
                          <div className="bg-red-500 text-white px-2 py-1 rounded-lg text-sm font-bold flex items-center gap-1">
                            <Percent className="w-3.5 h-3.5" />
                            {deal.discount_value}%
                          </div>
                        </div>

                        {/* Description */}
                        <p className="mt-3 text-sm text-gray-600 line-clamp-2">
                          {deal.description}
                        </p>

                        {/* Meta Info */}
                        <div className="mt-3 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                          {deal.merchant.address && (
                            <span className="flex items-center gap-1">
                              <MapPin className="w-3.5 h-3.5" />
                              {deal.merchant.address}
                            </span>
                          )}
                          <span className={`flex items-center gap-1 ${isExpiringSoon ? 'text-red-500 font-medium' : ''}`}>
                            <Clock className="w-3.5 h-3.5" />
                            {timeLeft}
                          </span>
                        </div>

                        {/* Promo Code */}
                        {deal.promo_code && (
                          <div className="mt-3 p-2 bg-amber-50 rounded-lg border border-dashed border-amber-300">
                            <div className="flex items-center justify-between">
                              <span className="text-xs text-amber-600 font-medium">PROMO CODE</span>
                              <span className="font-mono font-bold text-amber-700">{deal.promo_code}</span>
                            </div>
                          </div>
                        )}

                        {/* Token Cost */}
                        {deal.token_cost > 0 && (
                          <div className="mt-3 flex items-center gap-1 text-sm">
                            <Sparkles className="w-4 h-4 text-yellow-500" />
                            <span className="text-gray-600">
                              Costs <span className="font-semibold text-yellow-600">{deal.token_cost} tokens</span> to unlock
                            </span>
                          </div>
                        )}
                      </div>
                    </div>
                  )
                })}
              </div>

              {/* Load More */}
              {hasMore && (
                <div className="mt-6 text-center">
                  <button
                    onClick={() => setPage(p => p + 1)}
                    disabled={loading}
                    className="px-6 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition disabled:opacity-50"
                  >
                    {loading ? 'Loading...' : 'Load More Deals'}
                  </button>
                </div>
              )}
            </>
          )}

          {/* Merchant CTA */}
          <div className="mt-8 p-4 bg-gradient-to-r from-amber-500 to-orange-500 rounded-xl text-white">
            <div className="flex items-center gap-3">
              <Store className="w-10 h-10" />
              <div className="flex-1">
                <h3 className="font-semibold">Are you a local business?</h3>
                <p className="text-sm text-amber-100">Create promotions and reach nearby customers</p>
              </div>
              <Link
                href="/merchant/register"
                className="px-4 py-2 bg-white text-amber-600 rounded-lg font-medium hover:bg-amber-50 transition"
              >
                Get Started
              </Link>
            </div>
          </div>
        </main>
      </div>
    </ProtectedRoute>
  )
}
