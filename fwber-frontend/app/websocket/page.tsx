import nextDynamic from 'next/dynamic';

export const dynamic = 'force-dynamic';

const WebSocketPageClient = nextDynamic(
  () => import('@/components/pages/WebSocketPageClient'),
  { ssr: false }
);

export default function WebSocketPage() {
  return <WebSocketPageClient />;
}
