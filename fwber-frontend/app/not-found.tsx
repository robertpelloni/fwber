import Link from 'next/link'

export default function NotFound() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
      <div className="max-w-md w-full text-center">
        <h2 className="text-6xl font-bold text-gray-900 mb-4">404</h2>
        <h3 className="text-2xl font-semibold text-gray-700 mb-2">Page Not Found</h3>
        <p className="text-gray-600 mb-6">
          Sorry, we couldn&apos;t find the page you&apos;re looking for.
        </p>
        <Link
          href="/"
          className="inline-block px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          Go back home
        </Link>
      </div>
    </div>
  )
}
