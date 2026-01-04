import { ImageResponse } from 'next/og';

export const runtime = 'edge';

// We reuse the cached font if possible, but for simplicity here we just use system fonts
// or fetch a font if needed. For Edge Runtime, fetch is standard.

export const alt = 'fwber Viral Content';
export const size = {
  width: 1200,
  height: 630,
};

export const contentType = 'image/png';

export default async function Image({ params }: { params: { id: string } }) {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
  
  let data;
  try {
    const res = await fetch(`${apiUrl}/viral-content/${params.id}`, { cache: 'no-store' });
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
            background: '#000000',
            width: '100%',
            height: '100%',
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            fontFamily: 'sans-serif',
          }}
        >
          <div style={{
            background: 'linear-gradient(90deg, #ec4899 0%, #a855f7 100%)',
            backgroundClip: 'text',
            color: 'transparent',
            fontSize: 120,
            fontWeight: 800,
            marginBottom: 20,
            letterSpacing: '-0.05em',
          }}>
            fwber
          </div>
          <div style={{ fontSize: 36, color: '#9ca3af', fontWeight: 500 }}>
            The Definitive Social Network for Adults
          </div>
        </div>
      ),
      { ...size }
    );
  }

  const { type, content, user_name } = data;
  let title = 'fwber Analysis';
  let text = '';
  // Default to branding colors
  let bgColor = '#0f0f0f'; 
  let accentColor = '#ec4899';
  let secondaryColor = '#a855f7';
  let icon = '🔥';
  let gradient = 'linear-gradient(135deg, #1a0500 0%, #000000 100%)';

  switch (type) {
    case 'roast':
      title = user_name ? `Roast of ${user_name}` : 'Profile Roast';
      text = content.text;
      bgColor = '#1a0500'; 
      accentColor = '#f97316'; // Orange
      secondaryColor = '#ef4444'; // Red
      icon = '🔥';
      gradient = 'linear-gradient(135deg, #2a0a00 0%, #000000 100%)';
      break;
    case 'hype':
      title = user_name ? `${user_name}'s Hype` : 'Profile Hype';
      text = content.text;
      bgColor = '#050a1a'; 
      accentColor = '#3b82f6'; // Blue
      secondaryColor = '#06b6d4'; // Cyan
      icon = '✨';
      gradient = 'linear-gradient(135deg, #050a2a 0%, #000000 100%)';
      break;
    case 'fortune':
      title = user_name ? `${user_name}'s Fortune` : 'Dating Fortune';
      text = content.text;
      bgColor = '#1a1205'; 
      accentColor = '#eab308'; // Yellow
      secondaryColor = '#f59e0b'; // Amber
      icon = '🔮';
      gradient = 'linear-gradient(135deg, #2a1a00 0%, #000000 100%)';
      break;
    case 'nemesis':
      title = 'Scientific Nemesis';
      text = `${content.nemesis_type}: ${content.why_it_would_fail}`;
      bgColor = '#1a0505'; 
      accentColor = '#ef4444'; // Red
      secondaryColor = '#b91c1c'; // Dark Red
      icon = '🧬';
      gradient = 'linear-gradient(135deg, #2a0505 0%, #000000 100%)';
      break;
    case 'vibe':
      title = user_name ? `${user_name}'s Vibe Check` : 'Vibe Check';
      const green = content.green_flags?.[0] || 'No green flags';
      const red = content.red_flags?.[0] || 'No red flags';
      text = `🟢 ${green}\n🚩 ${red}`;
      bgColor = '#1a051a'; 
      accentColor = '#a855f7'; // Purple
      secondaryColor = '#d946ef'; // Fuchsia
      icon = '🚩';
      gradient = 'linear-gradient(135deg, #1a051a 0%, #000000 100%)';
      break;
  }

  // Truncate text for image
  if (text.length > 140) {
    text = text.substring(0, 140) + '...';
  }

  return new ImageResponse(
    (
      <div
        style={{
          width: '100%',
          height: '100%',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'space-between',
          padding: '60px',
          background: bgColor,
          backgroundImage: gradient,
          position: 'relative',
          overflow: 'hidden',
          fontFamily: 'sans-serif',
        }}
      >
        {/* Decorative background elements */}
        <div style={{
          position: 'absolute',
          top: -100,
          left: -100,
          width: 400,
          height: 400,
          background: accentColor,
          opacity: 0.15,
          filter: 'blur(100px)',
          borderRadius: '50%',
        }} />
        <div style={{
          position: 'absolute',
          bottom: -100,
          right: -100,
          width: 400,
          height: 400,
          background: secondaryColor,
          opacity: 0.15,
          filter: 'blur(100px)',
          borderRadius: '50%',
        }} />

        {/* Wanted Poster / Card Border Effect */}
        <div style={{
          position: 'absolute',
          top: 20,
          left: 20,
          right: 20,
          bottom: 20,
          border: `2px solid ${accentColor}40`,
          borderRadius: 20,
          pointerEvents: 'none',
        }} />

        {/* Header */}
        <div style={{ 
          display: 'flex', 
          alignItems: 'center',
          gap: 20,
          width: '100%',
          justifyContent: 'center',
          zIndex: 10,
        }}>
          <div style={{ fontSize: 72 }}>{icon}</div>
          <div style={{ 
            fontSize: 60, 
            fontWeight: 900, 
            color: 'white',
            textTransform: 'uppercase',
            letterSpacing: '0.05em',
            textShadow: '0 4px 12px rgba(0,0,0,0.5)',
            backgroundImage: `linear-gradient(to right, ${accentColor}, ${secondaryColor})`,
            backgroundClip: 'text',
          }}>
            {title}
          </div>
        </div>

        {/* Main Content Card */}
        <div style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          background: 'rgba(20, 20, 20, 0.8)',
          padding: '40px 60px',
          borderRadius: 32,
          border: `2px solid ${accentColor}40`,
          width: '90%',
          flex: 1,
          margin: '30px 0',
          boxShadow: `0 20px 50px -10px ${accentColor}20`,
          zIndex: 10,
          backdropFilter: 'blur(12px)',
        }}>
          <div style={{
            fontSize: 48,
            color: '#f3f4f6',
            textAlign: 'center',
            lineHeight: 1.3,
            fontWeight: 600,
            fontStyle: 'italic',
            display: 'flex',
            whiteSpace: 'pre-wrap',
          }}>
            &quot;{text}&quot;
          </div>
        </div>

        {/* Footer */}
        <div style={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          width: '100%',
          marginTop: 10,
          zIndex: 10,
          padding: '0 20px',
        }}>
          <div style={{
            display: 'flex',
            alignItems: 'center',
            gap: 12,
          }}>
             <div style={{
              width: 40,
              height: 40,
              borderRadius: 8,
              background: `linear-gradient(135deg, ${accentColor}, ${secondaryColor})`,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: 'white',
              fontWeight: 'bold',
              fontSize: 24
             }}>F</div>
             <div style={{ fontSize: 32, color: 'white', fontWeight: 700 }}>fwber</div>
          </div>

          <div style={{
            fontSize: 24,
            color: '#9ca3af',
            fontWeight: 500,
          }}>
            Get yours at fwber.me
          </div>
        </div>
      </div>
    ),
    { ...size }
  );
}
