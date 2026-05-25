import { MetadataRoute } from 'next'

export default function robots(): MetadataRoute.Robots {
  const baseUrl = process.env.NEXT_PUBLIC_APP_URL || 'https://fwber.me'
  
  return {
    rules: {
      userAgent: '*',
      allow: [
        '/',
        '/share/', // Viral content
        '/venue/', // Venue portal
      ],
      disallow: [
        '/api/',
        '/admin/',
        '/dashboard/',
        '/messages/',
        '/settings/',
        '/matches/',
        '/private/',
      ],
    },
    sitemap: `${baseUrl}/sitemap.xml`,
  }
}
