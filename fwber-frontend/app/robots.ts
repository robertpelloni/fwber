import { MetadataRoute } from 'next'

export default function robots(): MetadataRoute.Robots {
  const baseUrl = 'https://fwber.me'
  
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
