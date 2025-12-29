import { Metadata } from 'next';
import { ShareContent } from './share-content';

// This is a Server Component
export default async function SharePage(props: { params: Promise<{ id: string }> }) {
  const params = await props.params;
  return <ShareContent id={params.id} />;
}

// Generate dynamic metadata for social sharing
export async function generateMetadata(props: { params: Promise<{ id: string }> }): Promise<Metadata> {
  const params = await props.params;
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
  
  try {
    const res = await fetch(`${apiUrl}/viral-content/${params.id}`);
    
    if (!res.ok) {
        return {
            title: 'FWBer - Viral Profile Analysis',
            description: 'Check out this AI-generated profile analysis on FWBer.',
        };
    }

    const data = await res.json();
    const userName = data.user_name || 'Someone';
    
    let title = 'FWBer Analysis';
    let description = 'See what AI thinks about this profile.';

    switch (data.type) {
      case 'roast':
        title = `Roast of ${userName} 🔥`;
        description = `Read this brutal AI roast of ${userName}'s dating profile.`;
        break;
      case 'hype':
        title = `${userName}'s Hype ✨`;
        description = `See why ${userName} is a catch according to AI.`;
        break;
      case 'fortune':
        title = `${userName}'s Dating Fortune 🔮`;
        description = `See what the future holds for ${userName}'s love life.`;
        break;
      case 'nemesis':
        title = `Scientific Nemesis 🧬`;
        description = `Find out who ${userName} should absolutely avoid dating.`;
        break;
      case 'vibe':
        title = `${userName}'s Vibe Check 🚩`;
        description = `Green flags, red flags, and everything in between.`;
        break;
    }

    return {
      title,
      description,
      openGraph: {
        title,
        description,
        type: 'article',
      },
      twitter: {
        card: 'summary_large_image',
        title,
        description,
      }
    };

  } catch (error) {
    return {
      title: 'FWBer - Viral Profile Analysis',
      description: 'Check out this AI-generated profile analysis on FWBer.',
    };
  }
}
