import nextDynamic from 'next/dynamic';

export const dynamic = 'force-dynamic';

const BulletinBoardsPageClient = nextDynamic(
  () => import('@/components/pages/BulletinBoardsPageClient'),
  { ssr: false }
);

export default function BulletinBoardsPage() {
  return <BulletinBoardsPageClient />;
}
