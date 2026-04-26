'use client'

import { useState, useMemo } from 'react'
import { Globe, MapPin, Navigation, Building2 } from 'lucide-react'

// ─── Data ────────────────────────────────────────────────────────────────────

type MatchScope = 'local' | 'regional' | 'national' | 'global'

const DISTANCE_PRESETS: Record<string, number> = {
  '5 mi': 5,
  '10 mi': 10,
  '25 mi': 25,
  '50 mi': 50,
  '100 mi': 100,
  '250 mi': 250,
}

const COUNTRIES: Record<string, { label: string; cities: string[] }> = {
  'US': {
    label: '🇺🇸 United States',
    cities: ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'Austin', 'San Francisco', 'Seattle', 'Denver', 'Washington DC', 'Nashville', 'Portland', 'Las Vegas', 'Atlanta', 'Miami', 'Boston', 'Orlando', 'Minneapolis', 'Detroit', 'Philadelphia', 'Charlotte'],
  },
  'GB': {
    label: '🇬🇧 United Kingdom',
    cities: ['London', 'Manchester', 'Birmingham', 'Leeds', 'Glasgow', 'Liverpool', 'Edinburgh', 'Bristol', 'Cardiff', 'Belfast'],
  },
  'CA': {
    label: '🇨🇦 Canada',
    cities: ['Toronto', 'Vancouver', 'Montreal', 'Calgary', 'Ottawa', 'Edmonton', 'Winnipeg', 'Halifax'],
  },
  'AU': {
    label: '🇦🇺 Australia',
    cities: ['Sydney', 'Melbourne', 'Brisbane', 'Perth', 'Adelaide', 'Gold Coast', 'Canberra'],
  },
  'DE': {
    label: '🇩🇪 Germany',
    cities: ['Berlin', 'Munich', 'Hamburg', 'Frankfurt', 'Cologne', 'Stuttgart', 'Düsseldorf'],
  },
  'FR': {
    label: '🇫🇷 France',
    cities: ['Paris', 'Marseille', 'Lyon', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg'],
  },
  'JP': {
    label: '🇯🇵 Japan',
    cities: ['Tokyo', 'Osaka', 'Yokohama', 'Nagoya', 'Sapporo', 'Fukuoka', 'Kobe', 'Kyoto'],
  },
  'BR': {
    label: '🇧🇷 Brazil',
    cities: ['São Paulo', 'Rio de Janeiro', 'Brasília', 'Salvador', 'Fortaleza', 'Belo Horizonte', 'Manaus'],
  },
  'MX': {
    label: '🇲🇽 Mexico',
    cities: ['Mexico City', 'Guadalajara', 'Monterrey', 'Puebla', 'Cancún', 'Tijuana', 'Mérida'],
  },
  'KR': {
    label: '🇰🇷 South Korea',
    cities: ['Seoul', 'Busan', 'Incheon', 'Daegu', 'Daejeon', 'Gwangju'],
  },
  'IN': {
    label: '🇮🇳 India',
    cities: ['Mumbai', 'Delhi', 'Bangalore', 'Hyderabad', 'Chennai', 'Kolkata', 'Pune', 'Ahmedabad'],
  },
  'ES': {
    label: '🇪🇸 Spain',
    cities: ['Madrid', 'Barcelona', 'Valencia', 'Seville', 'Málaga', 'Bilbao'],
  },
  'IT': {
    label: '🇮🇹 Italy',
    cities: ['Rome', 'Milan', 'Naples', 'Florence', 'Venice', 'Turin', 'Bologna'],
  },
  'NL': {
    label: '🇳🇱 Netherlands',
    cities: ['Amsterdam', 'Rotterdam', 'The Hague', 'Utrecht', 'Eindhoven'],
  },
  'SE': {
    label: '🇸🇪 Sweden',
    cities: ['Stockholm', 'Gothenburg', 'Malmö', 'Uppsala', 'Linköping'],
  },
  'OTHER': {
    label: '🌍 Other',
    cities: [],
  },
}

// ─── Component ───────────────────────────────────────────────────────────────

interface LocationMatcherProps {
  formData: any
  handleLocationChange: (field: string, value: any) => void
}

export default function LocationMatcher({ formData, handleLocationChange }: LocationMatcherProps) {
  const location = formData.location || {}
  const maxDistance = location.max_distance ?? 25
  const matchScope = (location.match_scope as MatchScope) || 'local'
  const searchCountry = location.search_country || ''
  const searchCity = location.search_city || ''

  // Derive scope from distance for display
  const effectiveScope: MatchScope = matchScope

  // Available cities for selected country
  const availableCities = useMemo(() => {
    if (!searchCountry || !COUNTRIES[searchCountry]) return []
    return COUNTRIES[searchCountry].cities
  }, [searchCountry])

  const handleScopeChange = (scope: MatchScope) => {
    handleLocationChange('match_scope', scope)
    if (scope === 'global') {
      handleLocationChange('max_distance', 0)
    } else if (scope === 'national') {
      handleLocationChange('max_distance', 500)
    } else if (scope === 'regional') {
      handleLocationChange('max_distance', 100)
    } else {
      handleLocationChange('max_distance', 25)
    }
  }

  return (
    <div className="space-y-6">
      {/* ── Match Scope Selector ─────────────────────────────────────────── */}
      <div>
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          <Globe className="w-4 h-4 inline mr-1.5 -mt-0.5" />
          Match Scope
        </label>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
          {([
            { key: 'local', label: 'Local', icon: <Navigation className="w-4 h-4" />, desc: 'Nearby' },
            { key: 'regional', label: 'Regional', icon: <MapPin className="w-4 h-4" />, desc: 'Wider area' },
            { key: 'national', label: 'National', icon: <Building2 className="w-4 h-4" />, desc: 'Country-wide' },
            { key: 'global', label: 'Global', icon: <Globe className="w-4 h-4" />, desc: 'Anywhere' },
          ] as const).map(({ key, label, icon, desc }) => (
            <button
              key={key}
              type="button"
              onClick={() => handleScopeChange(key)}
              className={`flex flex-col items-center gap-1 p-3 rounded-xl border-2 transition-all text-center ${
                effectiveScope === key
                  ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 shadow-sm'
                  : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-600 dark:text-gray-400'
              }`}
            >
              {icon}
              <span className="text-sm font-medium">{label}</span>
              <span className="text-[10px] opacity-70">{desc}</span>
            </button>
          ))}
        </div>
      </div>

      {/* ── Distance Slider (Local / Regional) ──────────────────────────── */}
      {(effectiveScope === 'local' || effectiveScope === 'regional') && (
        <div>
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            <MapPin className="w-4 h-4 inline mr-1.5 -mt-0.5" />
            Maximum Distance: <span className="text-blue-600 dark:text-blue-400 font-semibold">{maxDistance} miles</span>
          </label>
          <input
            type="range"
            min={1}
            max={effectiveScope === 'regional' ? 500 : 100}
            value={maxDistance}
            onChange={(e) => handleLocationChange('max_distance', parseInt(e.target.value))}
            className="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full appearance-none cursor-pointer accent-blue-500"
          />
          <div className="flex justify-between mt-1.5">
            {Object.entries(DISTANCE_PRESETS).map(([label, val]) => (
              <button
                key={val}
                type="button"
                onClick={() => handleLocationChange('max_distance', val)}
                className={`text-[11px] px-2 py-0.5 rounded-full transition-colors ${
                  maxDistance === val
                    ? 'bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-200 font-medium'
                    : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
                }`}
              >
                {label}
              </button>
            ))}
          </div>
        </div>
      )}

      {/* ── National: Country Selector ───────────────────────────────────── */}
      {effectiveScope === 'national' && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              <Building2 className="w-4 h-4 inline mr-1.5 -mt-0.5" />
              Country
            </label>
            <select
              value={searchCountry}
              onChange={(e) => {
                handleLocationChange('search_country', e.target.value)
                handleLocationChange('search_city', '')
              }}
              className="w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-800 dark:text-white"
            >
              <option value="">Any country</option>
              {Object.entries(COUNTRIES).map(([code, { label }]) => (
                <option key={code} value={code}>{label}</option>
              ))}
            </select>
          </div>

          {availableCities.length > 0 && (
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                <MapPin className="w-4 h-4 inline mr-1.5 -mt-0.5" />
                City (optional)
              </label>
              <select
                value={searchCity}
                onChange={(e) => handleLocationChange('search_city', e.target.value)}
                className="w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-800 dark:text-white"
              >
                <option value="">Any city</option>
                {availableCities.map((city) => (
                  <option key={city} value={city}>{city}</option>
                ))}
              </select>
            </div>
          )}

          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Search radius: <span className="text-blue-600 dark:text-blue-400 font-semibold">{maxDistance} miles</span>
            </label>
            <input
              type="range"
              min={50}
              max={3000}
              step={50}
              value={maxDistance}
              onChange={(e) => handleLocationChange('max_distance', parseInt(e.target.value))}
              className="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full appearance-none cursor-pointer accent-blue-500"
            />
            <div className="flex justify-between text-[10px] text-gray-500 mt-1">
              <span>50 mi</span>
              <span>500 mi</span>
              <span>1500 mi</span>
              <span>3000 mi</span>
            </div>
          </div>
        </div>
      )}

      {/* ── Global: Worldwide ────────────────────────────────────────────── */}
      {effectiveScope === 'global' && (
        <div className="rounded-xl bg-gradient-to-br from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 border border-purple-200 dark:border-purple-800 p-4">
          <div className="flex items-center gap-3">
            <div className="p-2 rounded-full bg-purple-100 dark:bg-purple-800">
              <Globe className="w-5 h-5 text-purple-600 dark:text-purple-300" />
            </div>
            <div>
              <p className="font-medium text-purple-900 dark:text-purple-200">
                🌍 Matching worldwide
              </p>
              <p className="text-sm text-purple-700 dark:text-purple-400">
                You&apos;ll see people from anywhere in the world. No distance limit.
              </p>
            </div>
          </div>
        </div>
      )}

      {/* ── Your Location ────────────────────────────────────────────────── */}
      <div className="pt-2 border-t border-gray-200 dark:border-gray-700">
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          <Navigation className="w-4 h-4 inline mr-1.5 -mt-0.5" />
          Your Location
        </label>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
          <input
            type="text"
            placeholder="City"
            value={location.city || ''}
            onChange={(e) => handleLocationChange('city', e.target.value)}
            className="border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-800 dark:text-white"
          />
          <input
            type="text"
            placeholder="State / Region"
            value={location.state || ''}
            onChange={(e) => handleLocationChange('state', e.target.value)}
            className="border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-800 dark:text-white"
          />
        </div>
      </div>
    </div>
  )
}
