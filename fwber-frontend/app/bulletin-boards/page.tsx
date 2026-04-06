'use client';

import nextDynamic from 'next/dynamic';

const BulletinBoardsPageClient = nextDynamic(
  () => import('@/components/pages/BulletinBoardsPageClient'),
  { ssr: false }
);

export default function BulletinBoardsPage() {
  return <BulletinBoardsPageClient />;
}
