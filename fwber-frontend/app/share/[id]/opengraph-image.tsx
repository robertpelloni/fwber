import { ImageResponse } from 'next/og';

export const runtime = 'edge';

export const alt = 'FWBer Viral Content';
export const size = {
  width: 1200,
  height: 630,
};

export const contentType = 'image/png';

export default async function Image({ params }: { params: { id: string } }) {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
  
  let data;
  try {
    const res = await fetch(`${apiUrl}/viral-content/${params.id}`);
    if (res.ok) {
      data = await res.json();
    }
  } catch (e) {
    console.error('Failed to fetch viral content for OG image', e);
  }

  if (!data) {
    return new ImageResponse(
      (
        <div
          style={{
            fontSize: 64,
            background: 'black',
            width: '100%',
            height: '100%',
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            color: 'white',
            fontFamily: 'sans-serif',
          }}
        >
          <div style={{ 
            background: 'linear-gradient(to right, #ec4899, #a855f7)', 
            backgroundClip: 'text', 
            color: 'transparent',
            fontWeight: 'bold',
            fontSize: 96,
            marginBottom: 20
          }}>
            FWBer
          </div>
          <div style={{ fontSize: 32, color: '#9ca3af' }}>
            The Definitive Social Network for Adults
          </div>
        </div>
      ),
      { ...size }
    );
  }

  const { type, content } = data;
  let title = 'FWBer Analysis';
  let text = '';
  let bgColor = '#000000';
  let accentColor = '#ec4899';
  let icon = '🔥';

  switch (type) {
    case 'roast':
      title = 'Profile Roast';
      text = content.text;
      bgColor = '#1a0500'; // Dark orange/brown
      accentColor = '#f97316'; // Orange
      icon = '🔥';
      break;
    case 'hype':
      title = 'Profile Hype';
      text = content.text;
      bgColor = '#050a1a'; // Dark blue
      accentColor = '#3b82f6'; // Blue
      icon = '✨';
      break;
    case 'fortune':
      title = 'Dating Fortune';
      text = content.text;
      bgColor = '#1a1205'; // Dark yellow
      accentColor = '#eab308'; // Yellow
      icon = '🔮';
      break;
    case 'nemesis':
      title = 'Scientific Nemesis';
      text = `${content.nemesis_type}: ${content.why_it_would_fail}`;
      bgColor = '#1a0505'; // Dark red
      accentColor = '#ef4444'; // Red
      icon = '🧬';
      break;
    case 'vibe':
      title = 'Vibe Check';
      const green = content.green_flags?.[0] || 'No green flags';
      const red = content.red_flags?.[0] || 'No red flags';
      text = `🟢 ${green}\n🚩 ${red}`;
      bgColor = '#1a051a'; // Dark purple
      accentColor = '#a855f7'; // Purple
      icon = '🚩';
      break;
  }

  // Truncate text if too long
  if (text.length > 150) {
    text = text.substring(0, 150) + '...';
  }

  return new ImageResponse(
    (
      <div
        style={{
          background: bgColor,
          width: '100%',
          height: '100%',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          padding: 60,
          fontFamily: 'sans-serif',
          border: `20px solid ${accentColor}`,
        }}
      >
        <div style={{ 
          display: 'flex', 
          alignItems: 'center', 
          marginBottom: 40,
          gap: 20
        }}>
          <div style={{ fontSize: 64 }}>{icon}</div>
          <div style={{ 
            fontSize: 64, 
            fontWeight: 'bold', 
            color: 'white',
            textTransform: 'uppercase',
            letterSpacing: '2px'
          }}>
            {title}
          </div>
        </div>

        <div style={{
          display: 'flex',
          background: 'rgba(255,255,255,0.1)',
          padding: 40,
          borderRadius: 20,
          border: `2px solid ${accentColor}`,
          width: '100%',
          justifyContent: 'center',
        }}>
          <div style={{
            fontSize: 42,
            color: '#e5e7eb',
            textAlign: 'center',
            lineHeight: 1.4,
            fontStyle: 'italic',
            whiteSpace: 'pre-wrap',
          }}>
            "{text}"
          </div>
        </div>

        <div style={{
          marginTop: 40,
          fontSize: 32,
          color: accentColor,
          fontWeight: 'bold',
        }}>
          fwber.me
        </div>
      </div>
    ),
    { ...size }
  );
}
