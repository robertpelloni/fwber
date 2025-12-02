'use client';

import { useQuery } from '@tanstack/react-query';
import { CheckCircle2, Circle, AlertTriangle } from 'lucide-react';
import axios from 'axios';
import Link from 'next/link';

interface CompletenessData {
  percentage: number;
  required_complete: boolean;
  missing_required: string[];
  missing_optional: string[];
  sections: {
    basic: boolean;
    location: boolean;
    preferences: boolean;
    interests: boolean;
    physical: boolean;
    lifestyle: boolean;
  };
}

export default function ProfileCompletenessWidget() {
  const { data, isLoading } = useQuery({
    queryKey: ['profile-completeness'],
    queryFn: async () => {
      const token = localStorage.getItem('fwber_token');
      const response = await axios.get<CompletenessData>(
        `${process.env.NEXT_PUBLIC_API_URL}/profile/completeness`,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      return response.data;
    },
    refetchInterval: 30000, // Refresh every 30 seconds
  });

  if (isLoading || !data) {
    return (
      <div className="bg-white rounded-xl border border-gray-200 p-6 animate-pulse">
        <div className="h-6 bg-gray-200 rounded w-3/4 mb-4"></div>
        <div className="h-4 bg-gray-200 rounded w-full mb-2"></div>
        <div className="h-4 bg-gray-200 rounded w-full"></div>
      </div>
    );
  }

  const { percentage = 0, required_complete = false, missing_required = [], missing_optional = [], sections } = data;

  const safeSections = sections || {
    basic: false,
    location: false,
    preferences: false,
    interests: false,
    physical: false,
    lifestyle: false,
  };

  const getStatusColor = (pct: number) => {
    if (pct >= 80) return 'text-green-600';
    if (pct >= 50) return 'text-yellow-600';
    return 'text-red-600';
  };

  const getBarColor = (pct: number) => {
    if (pct >= 80) return 'from-green-500 to-emerald-600';
    if (pct >= 50) return 'from-yellow-500 to-orange-600';
    return 'from-red-500 to-pink-600';
  };

  return (
    <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-semibold text-gray-900">Profile Completeness</h3>
        <span className={`text-2xl font-bold ${getStatusColor(percentage)}`}>
          {percentage}%
        </span>
      </div>

      {/* Progress Bar */}
      <div className="mb-6">
        <div className="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
          <div
            className={`h-full bg-gradient-to-r ${getBarColor(percentage)} transition-all duration-700 ease-out`}
            style={{ width: `${percentage}%` }}
          />
        </div>
      </div>

      {/* Status Message */}
      {!required_complete && (
        <div className="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg flex items-start gap-2">
          <AlertTriangle className="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
          <div className="text-sm">
            <p className="font-medium text-yellow-900">Required fields missing</p>
            <p className="text-yellow-700">Complete your profile to start matching</p>
          </div>
        </div>
      )}

      {/* Section Checklist */}
      <div className="space-y-2 mb-4">
        <SectionItem label="Basic Information" complete={safeSections.basic} />
        <SectionItem label="Location" complete={safeSections.location} />
        <SectionItem label="Dating Preferences" complete={safeSections.preferences} />
        <SectionItem label="Interests & Hobbies" complete={safeSections.interests} />
        <SectionItem label="Physical Attributes" complete={safeSections.physical} />
        <SectionItem label="Lifestyle" complete={safeSections.lifestyle} />
      </div>

      {/* Missing Fields */}
      {missing_required.length > 0 && (
        <div className="mb-4">
          <p className="text-sm font-medium text-gray-700 mb-2">Required fields:</p>
          <ul className="space-y-1">
            {missing_required.map(field => (
              <li key={field} className="text-sm text-red-600 flex items-center gap-2">
                <Circle className="w-3 h-3 fill-current" />
                {formatFieldName(field)}
              </li>
            ))}
          </ul>
        </div>
      )}

      {missing_optional.length > 0 && percentage >= 60 && (
        <div className="mb-4">
          <p className="text-sm font-medium text-gray-700 mb-2">Optional fields (improve matches):</p>
          <ul className="space-y-1">
            {missing_optional.slice(0, 3).map(field => (
              <li key={field} className="text-sm text-gray-600 flex items-center gap-2">
                <Circle className="w-3 h-3" />
                {formatFieldName(field)}
              </li>
            ))}
            {missing_optional.length > 3 && (
              <li className="text-sm text-gray-500 italic">
                +{missing_optional.length - 3} more...
              </li>
            )}
          </ul>
        </div>
      )}

      {/* Action Button */}
      <Link
        href="/profile/edit"
        className="block w-full text-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium"
      >
        {percentage < 100 ? 'Complete Profile' : 'Edit Profile'}
      </Link>

      {/* Completion Milestone */}
      {percentage === 100 && (
        <div className="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
          <div className="flex items-center gap-2 text-green-800">
            <CheckCircle2 className="w-5 h-5" />
            <span className="text-sm font-medium">Profile 100% complete! 🎉</span>
          </div>
        </div>
      )}
    </div>
  );
}

function SectionItem({ label, complete }: { label: string; complete: boolean }) {
  return (
    <div className="flex items-center gap-2">
      {complete ? (
        <CheckCircle2 className="w-5 h-5 text-green-600" />
      ) : (
        <Circle className="w-5 h-5 text-gray-300" />
      )}
      <span className={`text-sm ${complete ? 'text-gray-900 font-medium' : 'text-gray-500'}`}>
        {label}
      </span>
    </div>
  );
}

function formatFieldName(field: string): string {
  return field
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}
