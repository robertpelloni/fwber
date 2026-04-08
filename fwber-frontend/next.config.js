// This file configures the initialization of Sentry for edge features (middleware, edge routes, and pages router).
// The config you add here will be used whenever one of the edge features is loaded.
// Note that this config is unrelated to the Vercel Edge Runtime and is also required when running locally.
// https://docs.sentry.io/platforms/javascript/guides/nextjs/

const fs = require('fs');
const path = require('path');

// Read versions
const getVersion = () => {
  try {
    return fs.readFileSync(path.join(__dirname, '../VERSION'), 'utf8').trim();
  } catch (e) {
    return '0.0.0-unknown';
  }
};

const getBackendVersion = () => {
  try {
    const pkg = JSON.parse(fs.readFileSync(path.join(__dirname, '../fwber-backend/package.json'), 'utf8'));
    return pkg.version;
  } catch (e) {
    return '0.0.0-unknown';
  }
};

const getFrontendVersion = () => {
  try {
    const pkg = JSON.parse(fs.readFileSync(path.join(__dirname, 'package.json'), 'utf8'));
    return pkg.version;
  } catch (e) {
    return '0.0.0-unknown';
  }
};

const { withSentryConfig } = require('@sentry/nextjs');
const withPWA = (config) => config; // Passthrough until PWA is fixed

const withBundleAnalyzer = require('@next/bundle-analyzer')({
  enabled: process.env.ANALYZE === 'true',
});

/** @type {import('next').NextConfig} */
const nextConfig = {
  // Fix multiple lockfile warning in monorepo setup
  outputFileTracingRoot: path.join(__dirname, '../'),

  // Performance optimizations
  compress: true,
  poweredByHeader: false,
  productionBrowserSourceMaps: false,
  
  // Environment variables
  env: {
    NEXT_PUBLIC_PROJECT_VERSION: getVersion(),
    NEXT_PUBLIC_BACKEND_VERSION: getBackendVersion(),
    NEXT_PUBLIC_FRONTEND_VERSION: getFrontendVersion(),
  },

  // Experimental features for better performance
  experimental: {
    optimizeCss: false,
    optimizePackageImports: ['@tanstack/react-query', 'lucide-react'],
  },
  eslint: {
    ignoreDuringBuilds: true,
  },
  typescript: {
    ignoreBuildErrors: true,
  },
  
  // Image optimization
  images: {
    remotePatterns: [
      { protocol: 'https', hostname: '**' },
      { protocol: 'http', hostname: '127.0.0.1', port: '8002', pathname: '/files/**' },
      { protocol: 'http', hostname: '127.0.0.1', port: '8002', pathname: '/storage/**' },
      { protocol: 'http', hostname: 'localhost', port: '8002', pathname: '/files/**' },
      { protocol: 'http', hostname: 'localhost', port: '8002', pathname: '/storage/**' },
      { protocol: 'http', hostname: 'localhost', port: '8002', pathname: '/images/**' },
      { protocol: 'http', hostname: 'localhost', port: '8000', pathname: '/**' },
    ],
    formats: ['image/webp', 'image/avif'],
    minimumCacheTTL: 60,
    dangerouslyAllowSVG: true,
    contentSecurityPolicy: "default-src 'self'; script-src 'none'; sandbox;",
    unoptimized: process.env.NODE_ENV === 'development',
  },
  
  // Bundle optimization
  webpack: (config, { dev, isServer }) => {
    if (!isServer) {
      config.resolve.fallback = {
        ...config.resolve.fallback,
        fs: false,
        path: false,
        crypto: false,
        encoding: false,
      };
    }

    config.ignoreWarnings = [
      ...(config.ignoreWarnings || []),
      { module: /node_modules\/@vladmandic\/face-api/ },
      { module: /node_modules\/zustand/ }
    ];

    return config;
  },
  
  // Unified Headers Method
  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          { key: 'X-DNS-Prefetch-Control', value: 'on' },
          { key: 'Strict-Transport-Security', value: 'max-age=63072000; includeSubDomains; preload' },
          { key: 'Content-Security-Policy', value: "frame-ancestors *" },
          { key: 'X-Content-Type-Options', value: '' }, // Neutralize legacy block
          { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
          { key: 'X-XSS-Protection', value: '1; mode=block' },
          { key: 'Permissions-Policy', value: 'camera=(self), microphone=(self), geolocation=(self), interest-cohort=()' }
        ],
      },
      {
        source: '/static/(.*)',
        headers: [
          { key: 'Cache-Control', value: 'public, max-age=31536000, immutable' },
        ],
      },
      {
        source: '/_next/static/(.*)',
        headers: [
          { key: 'Cache-Control', value: 'public, max-age=31536000, immutable' },
        ],
      },
      {
        source: '/fonts/(.*)',
        headers: [
          { key: 'Cache-Control', value: 'public, max-age=31536000, immutable' },
        ],
      },
      {
        source: '/images/(.*)',
        headers: [
          { key: 'Cache-Control', value: 'public, max-age=86400, must-revalidate' },
        ],
      },
    ];
  },

  async rewrites() {
    return [
      {
        source: '/api/:path*', 
        destination: 'https://api.fwber.me/api/:path*',
      },
      {
        source: '/.well-known/webfinger',
        destination: 'https://api.fwber.me/.well-known/webfinger',
      },
      {
        source: '/.well-known/nodeinfo',
        destination: 'https://api.fwber.me/.well-known/nodeinfo',
      },
    ];
  },
}

const sentryWebpackPluginOptions = {
  org: process.env.SENTRY_ORG,
  project: process.env.SENTRY_PROJECT,
  silent: !process.env.CI,
  widenClientFileUpload: true,
  hideSourceMaps: true,
};

module.exports = withSentryConfig(withPWA(withBundleAnalyzer(nextConfig)), sentryWebpackPluginOptions);
