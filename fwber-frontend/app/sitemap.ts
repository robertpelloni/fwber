import { MetadataRoute } from 'next'

export default function sitemap(): MetadataRoute.Sitemap {
  const baseUrl = 'https://fwber.me'
  
  // Static routes
  const routes = [
    '',
    '/auth/login',
    '/auth/register',
    '/about',
    '/privacy',
    '/terms',
  ].map((route) => ({
    url: `${baseUrl}${route}`,
    lastModified: new Date(),
    changeFrequency: 'monthly' as const,
    priority: route === '' ? 1 : 0.8,
  }))

  // In a real implementation, we would fetch dynamic content here
  // e.g. viral content, blog posts, etc.
  // const viralContent = await fetchViralContent()
  // const viralRoutes = viralContent.map(...)

  return [...routes]
}
