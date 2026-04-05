'use client';

import { useEffect, useMemo, useState } from 'react';
import { Lock, RotateCcw, SlidersHorizontal } from 'lucide-react';
import Link from 'next/link';
import { useAuth } from '@/lib/auth-context';
import { getTopics } from '@/lib/api/topics';

interface MatchFilterProps {
  onFilterChange: (filters: Record<string, string | number | boolean | string[]>) => void;
}

type InterestOption = {
  slug: string;
  label: string;
  emoji?: string | null;
};

type MatchFilters = {
  age_min: string;
  age_max: string;
  max_distance: string;
  smoking: string;
  drinking: string;
  body_type: string;
  height_min: string;
  interests: string[];
  has_bio: boolean;
  verified_only: boolean;
  cannabis: string;
  diet: string;
  has_pets: string;
  has_children: string;
  wants_children: string;
  politics: string;
  religion: string;
  zodiac: string;
};

const FALLBACK_INTEREST_OPTIONS: InterestOption[] = [
  { slug: 'music', label: 'Music' },
  { slug: 'movies', label: 'Movies' },
  { slug: 'sports', label: 'Sports' },
  { slug: 'gaming', label: 'Gaming' },
  { slug: 'travel', label: 'Travel' },
  { slug: 'cooking', label: 'Cooking' },
  { slug: 'reading', label: 'Reading' },
  { slug: 'art', label: 'Art' },
  { slug: 'fitness', label: 'Fitness' },
  { slug: 'outdoors', label: 'Outdoors' },
  { slug: 'tech', label: 'Tech' },
  { slug: 'nightlife', label: 'Nightlife' },
] as const;

const DEFAULT_FILTERS: MatchFilters = {
  age_min: '',
  age_max: '',
  max_distance: '50',
  smoking: '',
  drinking: '',
  body_type: '',
  height_min: '',
  interests: [],
  has_bio: false,
  verified_only: false,
  cannabis: '',
  diet: '',
  has_pets: '',
  has_children: '',
  wants_children: '',
  politics: '',
  religion: '',
  zodiac: '',
};

function buildFilterPayload(filters: MatchFilters): Record<string, string | number | boolean | string[]> {
  const payload: Record<string, string | number | boolean | string[]> = {};

  if (filters.age_min) payload.age_min = Number(filters.age_min);
  if (filters.age_max) payload.age_max = Number(filters.age_max);
  if (filters.max_distance && filters.max_distance !== '50') payload.max_distance = Number(filters.max_distance);
  if (filters.smoking) payload.smoking = filters.smoking;
  if (filters.drinking) payload.drinking = filters.drinking;
  if (filters.body_type) payload.body_type = filters.body_type;
  if (filters.height_min) payload.height_min = Number(filters.height_min);
  if (filters.interests.length > 0) payload.interests = filters.interests;
  if (filters.has_bio) payload.has_bio = true;
  if (filters.verified_only) payload.verified_only = true;
  if (filters.cannabis) payload.cannabis = filters.cannabis;
  if (filters.diet) payload.diet = filters.diet;
  if (filters.has_pets) payload.has_pets = filters.has_pets;
  if (filters.has_children) payload.has_children = filters.has_children;
  if (filters.wants_children) payload.wants_children = filters.wants_children;
  if (filters.politics) payload.politics = filters.politics;
  if (filters.religion) payload.religion = filters.religion;
  if (filters.zodiac) payload.zodiac = filters.zodiac;

  return payload;
}

export default function MatchFilter({ onFilterChange }: MatchFilterProps) {
  const { user } = useAuth();
  const PREMIUM_THRESHOLD = 100;
  const isPremium = (user?.token_balance || 0) >= PREMIUM_THRESHOLD;
  const [filters, setFilters] = useState<MatchFilters>(DEFAULT_FILTERS);
  const [topicOptions, setTopicOptions] = useState<InterestOption[]>([]);

  useEffect(() => {
    let isMounted = true;

    const loadTopics = async () => {
      try {
        const featuredTopics = await getTopics({ featured: true });
        const topics = featuredTopics.length > 0 ? featuredTopics : await getTopics();

        if (isMounted) {
          setTopicOptions(
            topics.slice(0, 12).map((topic) => ({
              slug: topic.slug,
              label: topic.label,
              emoji: topic.emoji,
            })),
          );
        }
      } catch {
        if (isMounted) {
          setTopicOptions([]);
        }
      }
    };

    void loadTopics();

    return () => {
      isMounted = false;
    };
  }, []);

  const interestOptions = useMemo(() => {
    return topicOptions.length > 0 ? topicOptions : [...FALLBACK_INTEREST_OPTIONS];
  }, [topicOptions]);

  const activeFilterCount = useMemo(() => {
    return Object.entries(buildFilterPayload(filters)).length;
  }, [filters]);

  const handleInputChange = (field: keyof MatchFilters, value: string | boolean) => {
    setFilters((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const handleInterestToggle = (interest: string) => {
    setFilters((prev) => ({
      ...prev,
      interests: prev.interests.includes(interest)
        ? prev.interests.filter((value) => value !== interest)
        : [...prev.interests, interest],
    }));
  };

  const handleApplyFilters = () => {
    onFilterChange(buildFilterPayload(filters));
  };

  const handleResetFilters = () => {
    setFilters(DEFAULT_FILTERS);
    onFilterChange({});
  };

  const PremiumOverlay = () => (
    <div className="absolute inset-0 z-10 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-100/70 backdrop-blur-[1px]">
      <Lock className="mb-2 h-8 w-8 text-gray-500" />
      <p className="text-sm font-semibold text-gray-700">Premium Filters</p>
      <p className="mb-3 px-4 text-center text-xs text-gray-500">
        Hold {PREMIUM_THRESHOLD}+ FWB tokens to unlock advanced lifestyle and compatibility filters.
      </p>
      <Link href="/wallet" className="rounded-full bg-indigo-600 px-3 py-1.5 text-xs text-white transition-colors hover:bg-indigo-700">
        Get Tokens
      </Link>
    </div>
  );

  return (
    <div className="space-y-6">
      <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div className="mb-4 flex flex-col gap-3 border-b pb-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h3 className="flex items-center gap-2 text-lg font-medium text-gray-900 dark:text-gray-100">
              <SlidersHorizontal className="h-5 w-5" />
              Discovery Filters
            </h3>
            <p className="text-sm text-gray-500 dark:text-gray-400">
              Tune nearby discovery without leaving the swipe flow.
            </p>
          </div>
          <div className="flex items-center gap-2">
            <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
              {activeFilterCount} active
            </span>
            <button
              type="button"
              onClick={handleResetFilters}
              className="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 transition hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:text-white"
            >
              <RotateCcw className="h-4 w-4" />
              Reset
            </button>
          </div>
        </div>

        <h4 className="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Basic Preferences</h4>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
          <div>
            <label htmlFor="age_min" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Min Age</label>
            <input id="age_min" type="number" min="18" max="100" value={filters.age_min} onChange={(e) => handleInputChange('age_min', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
          </div>
          <div>
            <label htmlFor="age_max" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Age</label>
            <input id="age_max" type="number" min="18" max="100" value={filters.age_max} onChange={(e) => handleInputChange('age_max', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
          </div>
          <div>
            <label htmlFor="max_distance" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Distance (mi)</label>
            <input id="max_distance" type="number" min="1" max="500" value={filters.max_distance} onChange={(e) => handleInputChange('max_distance', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
          </div>
          <div>
            <label htmlFor="height_min" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Min Height (cm)</label>
            <input id="height_min" type="number" min="120" max="250" value={filters.height_min} onChange={(e) => handleInputChange('height_min', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
          </div>
          <div>
            <label htmlFor="smoking" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Smoking</label>
            <select id="smoking" value={filters.smoking} onChange={(e) => handleInputChange('smoking', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
              <option value="">Any</option>
              <option value="non-smoker">Non-smoker</option>
              <option value="occasional">Occasional</option>
              <option value="regular">Regular</option>
              <option value="social">Social</option>
              <option value="trying-to-quit">Trying to quit</option>
            </select>
          </div>
          <div>
            <label htmlFor="drinking" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Drinking</label>
            <select id="drinking" value={filters.drinking} onChange={(e) => handleInputChange('drinking', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
              <option value="">Any</option>
              <option value="non-drinker">Non-drinker</option>
              <option value="occasional">Occasional</option>
              <option value="regular">Regular</option>
              <option value="social">Social</option>
              <option value="sober">Sober</option>
            </select>
          </div>
          <div>
            <label htmlFor="body_type" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Body Type</label>
            <select id="body_type" value={filters.body_type} onChange={(e) => handleInputChange('body_type', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
              <option value="">Any</option>
              <option value="slim">Slim</option>
              <option value="athletic">Athletic</option>
              <option value="average">Average</option>
              <option value="curvy">Curvy</option>
              <option value="plus-size">Plus size</option>
              <option value="muscular">Muscular</option>
            </select>
          </div>
          <div className="flex flex-col justify-end gap-3 rounded-lg border border-dashed border-gray-200 p-3 dark:border-gray-700">
            <label className="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
              <input type="checkbox" checked={filters.has_bio} onChange={(e) => handleInputChange('has_bio', e.target.checked)} className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
              Bio required
            </label>
            <label className="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
              <input type="checkbox" checked={filters.verified_only} onChange={(e) => handleInputChange('verified_only', e.target.checked)} className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
              Verified only
            </label>
          </div>
        </div>

        <div className="mt-4">
          <div className="mb-2 flex items-center justify-between gap-3">
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Shared interests</label>
            <span className="text-xs text-gray-500">Boost profiles that overlap with your vibe</span>
          </div>
          <div className="flex flex-wrap gap-2">
            {interestOptions.map((interest) => {
              const selected = filters.interests.includes(interest.slug);

              return (
                <button
                  key={interest.slug}
                  type="button"
                  onClick={() => handleInterestToggle(interest.slug)}
                  className={`rounded-full border px-3 py-1.5 text-sm font-medium transition ${
                    selected
                      ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm'
                      : 'border-gray-300 bg-white text-gray-700 hover:border-indigo-300 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200'
                  }`}
                >
                  <span className="inline-flex items-center gap-1">
                    {interest.emoji ? <span aria-hidden="true">{interest.emoji}</span> : null}
                    <span>{interest.label}</span>
                  </span>
                </button>
              );
            })}
          </div>
        </div>
      </div>

      <div className="relative rounded-lg border border-indigo-100 bg-gradient-to-br from-indigo-50 to-purple-50 p-4 shadow-sm dark:border-indigo-900/40 dark:bg-gradient-to-br dark:from-indigo-950/40 dark:to-purple-950/30">
        {!isPremium && <PremiumOverlay />}

        <h4 className="mb-4 flex items-center justify-between border-b border-indigo-200 pb-2 text-sm font-semibold uppercase tracking-wide text-indigo-900 dark:border-indigo-900 dark:text-indigo-100">
          <span>Premium Lifestyle Filters</span>
          {isPremium && <span className="rounded-full border border-indigo-200 bg-indigo-100 px-2 py-1 text-[11px] font-semibold text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200">Unlocked</span>}
        </h4>

        <div className={`grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4 ${!isPremium ? 'pointer-events-none opacity-40 blur-[1px]' : ''}`}>
          <div>
            <label htmlFor="cannabis" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Cannabis</label>
            <select id="cannabis" value={filters.cannabis} onChange={(e) => handleInputChange('cannabis', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" disabled={!isPremium}>
              <option value="">Any</option>
              <option value="non-smoker">No</option>
              <option value="occasional">Occasional</option>
              <option value="regular">Regular</option>
            </select>
          </div>
          <div>
            <label htmlFor="diet" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Diet</label>
            <select id="diet" value={filters.diet} onChange={(e) => handleInputChange('diet', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" disabled={!isPremium}>
              <option value="">Any</option>
              <option value="omnivore">Omnivore</option>
              <option value="vegetarian">Vegetarian</option>
              <option value="vegan">Vegan</option>
              <option value="keto">Keto</option>
              <option value="paleo">Paleo</option>
              <option value="halal">Halal</option>
              <option value="kosher">Kosher</option>
            </select>
          </div>
          <div>
            <label htmlFor="politics" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Politics</label>
            <select id="politics" value={filters.politics} onChange={(e) => handleInputChange('politics', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" disabled={!isPremium}>
              <option value="">Any</option>
              <option value="liberal">Liberal</option>
              <option value="moderate">Moderate</option>
              <option value="conservative">Conservative</option>
              <option value="apolitical">Apolitical</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label htmlFor="religion" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Religion</label>
            <select id="religion" value={filters.religion} onChange={(e) => handleInputChange('religion', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" disabled={!isPremium}>
              <option value="">Any</option>
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
            <label htmlFor="zodiac" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Zodiac</label>
            <select id="zodiac" value={filters.zodiac} onChange={(e) => handleInputChange('zodiac', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" disabled={!isPremium}>
              <option value="">Any</option>
              <option value="aries">Aries</option>
              <option value="taurus">Taurus</option>
              <option value="gemini">Gemini</option>
              <option value="cancer">Cancer</option>
              <option value="leo">Leo</option>
              <option value="virgo">Virgo</option>
              <option value="libra">Libra</option>
              <option value="scorpio">Scorpio</option>
              <option value="sagittarius">Sagittarius</option>
              <option value="capricorn">Capricorn</option>
              <option value="aquarius">Aquarius</option>
              <option value="pisces">Pisces</option>
            </select>
          </div>
          <div>
            <label htmlFor="has_pets" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Has Pets</label>
            <select id="has_pets" value={filters.has_pets} onChange={(e) => handleInputChange('has_pets', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" disabled={!isPremium}>
              <option value="">Any</option>
              <option value="yes">Yes</option>
              <option value="no">No</option>
            </select>
          </div>
          <div>
            <label htmlFor="has_children" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Has Children</label>
            <select id="has_children" value={filters.has_children} onChange={(e) => handleInputChange('has_children', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" disabled={!isPremium}>
              <option value="">Any</option>
              <option value="yes">Yes</option>
              <option value="no">No</option>
            </select>
          </div>
          <div>
            <label htmlFor="wants_children" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Wants Children</label>
            <select id="wants_children" value={filters.wants_children} onChange={(e) => handleInputChange('wants_children', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" disabled={!isPremium}>
              <option value="">Any</option>
              <option value="yes">Yes</option>
              <option value="no">No</option>
            </select>
          </div>
        </div>
      </div>

      <div className="flex justify-end pt-2">
        <button
          type="button"
          onClick={handleApplyFilters}
          className="rounded-lg bg-indigo-600 px-6 py-2.5 font-bold text-white shadow-md transition duration-200 hover:-translate-y-0.5 hover:bg-indigo-700"
        >
          Apply Filters
        </button>
      </div>
    </div>
  );
}
