'use client';

import nextDynamic from 'next/dynamic';

const WebSocketPageClient = nextDynamic(
  () => import('@/components/pages/WebSocketPageClient'),
  { ssr: false }
);

export default function WebSocketPage() {
  return <WebSocketPageClient />;
}
