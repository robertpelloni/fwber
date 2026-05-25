'use client';

import { useAnalytics } from '@/lib/hooks/use-analytics';

export default function AnalyticsProvider() {
    useAnalytics();
    return null;
}
