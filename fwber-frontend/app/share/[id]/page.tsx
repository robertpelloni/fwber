import { Metadata } from 'next';
import { ShareContent } from './share-content';

// This is a Server Component
export default function SharePage({ params }: { params: { id: string } }) {
  return <ShareContent id={params.id} />;
}

// Optional: Generate metadata for social sharing cards
export async function generateMetadata({ params }: { params: { id: string } }): Promise<Metadata> {
  return {
    title: 'Check out this FWBer Result!',
    description: 'See what AI thinks about this profile on FWBer.',
    openGraph: {
      title: 'FWBer - AI Dating Wingman',
      description: 'See what AI thinks about this profile on FWBer.',
      images: ['/images/og-share.png'], // You might want to add a generic share image
    },
  };
}
