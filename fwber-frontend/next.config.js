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

// const { withSentryConfig } = require('@sentry/nextjs');
const withPWA = require('next-pwa')({
  dest: 'public',
  disable: process.env.NODE_ENV === 'development',
  register: true,
  skipWaiting: true,
  importScripts: ['/sw-push.js'],
  maximumFileSizeToCacheInBytes: 7000000, // 7MB to accommodate large source maps
});

const withBundleAnalyzer = require('@next/bundle-analyzer')({
  enabled: process.env.ANALYZE === 'true',
});

/** @type {import('next').NextConfig} */
const nextConfig = {
  // Performance optimizations
  compress: true,
  poweredByHeader: false,
  productionBrowserSourceMaps: false,
  // output: 'standalone', // Optimized for Docker/Shared Hosting
  
  // Environment variables
  env: {
    // NEXTAUTH_URL and API_BASE_URL are read from runtime environment
    NEXT_PUBLIC_PROJECT_VERSION: getVersion(),
    NEXT_PUBLIC_BACKEND_VERSION: getBackendVersion(),
    NEXT_PUBLIC_FRONTEND_VERSION: getFrontendVersion(),
  },

  // Experimental features for better performance
  experimental: {
    optimizeCss: false,
    optimizePackageImports: ['@tanstack/react-query', 'lucide-react'],
    // turbopack: false, // Removed invalid key
  },
  
  // Image optimization
  images: {
    remotePatterns: [
      {
        protocol: 'https',
        hostname: '**',
      },
      {
        protocol: 'http',
        hostname: '127.0.0.1',
        port: '8002',
        pathname: '/files/**',
      },
      {
        protocol: 'http',
        hostname: '127.0.0.1',
        port: '8002',
        pathname: '/storage/**',
      },
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '8002',
        pathname: '/files/**',
      },
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '8002',
        pathname: '/storage/**',
      },
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '8002',
        pathname: '/images/**',
      },
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '8000',
        pathname: '/**',
      },
    ],
    formats: ['image/webp', 'image/avif'],
    minimumCacheTTL: 60,
    dangerouslyAllowSVG: true,
    contentSecurityPolicy: "default-src 'self'; script-src 'none'; sandbox;",
  },
  
  // Bundle optimization
  webpack: (config, { dev, isServer }) => {
    // Fix for packages that use node modules
    if (!isServer) {
      config.resolve.fallback = {
        ...config.resolve.fallback,
        fs: false,
        path: false,
        crypto: false,
        encoding: false,
      };
    }

    // Ignore face-api.js critical dependency warning and zustand deprecation warning
    config.ignoreWarnings = [
      ...(config.ignoreWarnings || []),
      { module: /node_modules\/@vladmandic\/face-api/ },
      { module: /node_modules\/zustand/ }
    ];

    // Production optimizations
    if (!dev && !isServer) {
      config.optimization.splitChunks = {
        chunks: 'all',
        cacheGroups: {
          vendor: {
            test: /[\\/]node_modules[\\/]/,
            name: 'vendors',
            chunks: 'all',
          },
          common: {
            name: 'common',
            minChunks: 2,
            chunks: 'all',
            enforce: true,
          },
        },
      };
    }
    
    return config;
  },
  
  // Headers for better caching and security
  async headers() {
    const securityHeaders = [
      {
        key: 'X-DNS-Prefetch-Control',
        value: 'on'
      },
      {
        key: 'Strict-Transport-Security',
        value: 'max-age=63072000; includeSubDomains; preload'
      },
      // X-Frame-Options removed to allow embedding
      // {
      //   key: 'X-Frame-Options',
      //   value: 'DENY',
      // },
      {
        key: 'Content-Security-Policy',
        value: "frame-ancestors *",
      },
      {
        key: 'X-Content-Type-Options',
        value: 'nosniff',
      },
      {
        key: 'Referrer-Policy',
        value: 'strict-origin-when-cross-origin',
      },
      {
        key: 'X-XSS-Protection',
        value: '1; mode=block'
      },
      {
        key: 'Permissions-Policy',
        value: 'camera=(self), microphone=(self), geolocation=(self), interest-cohort=()'
      }
    ];

    return [
      {
        source: '/(.*)',
        headers: securityHeaders,
      },
      {
        source: '/static/(.*)',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=31536000, immutable',
          },
        ],
      },
    ];
  },

  async rewrites() {
    return [
    ];
  },
  
  // Environment variables
  env: {
    // NEXTAUTH_URL and API_BASE_URL are read from runtime environment
    NEXT_PUBLIC_PROJECT_VERSION: getVersion(),
    NEXT_PUBLIC_BACKEND_VERSION: getBackendVersion(),
    NEXT_PUBLIC_FRONTEND_VERSION: getFrontendVersion(),
  },
}

// Sentry configuration options
const sentryWebpackPluginOptions = {
  // For all available options, see:
  // https://github.com/getsentry/sentry-webpack-plugin#options

  org: process.env.SENTRY_ORG,
  project: process.env.SENTRY_PROJECT,

  // Only print logs for uploading source maps in CI
  silent: !process.env.CI,

  // For all available options, see:
  // https://docs.sentry.io/platforms/javascript/guides/nextjs/manual-setup/

  // Upload a larger set of source maps for prettier stack traces (increases build time)
  widenClientFileUpload: true,

  // Uncomment to route browser requests to Sentry through a Next.js rewrite to circumvent ad-blockers.
  // This can increase your server load as well as your hosting bill.
  // Note: Check that the configured route will not match with your Next.js middleware, otherwise reporting of client-
  // side errors will fail.
  // tunnelRoute: "/monitoring",

  // Hides source maps from generated client bundles
  hideSourceMaps: true,

  // Automatically tree-shake Sentry logger statements to reduce bundle size
  disableLogger: true,

  // Enables automatic instrumentation of Vercel Cron Monitors. (Does not yet work with App Router route handlers.)
  // See the following for more information:
  // https://docs.sentry.io/product/crons/
  // https://vercel.com/docs/cron-jobs
  // automaticVercelMonitors: false,
};

// Make sure adding Sentry options is the last code to run before exporting
// module.exports = withSentryConfig(withPWA(withBundleAnalyzer(nextConfig)), sentryWebpackPluginOptions);
module.exports = withPWA(withBundleAnalyzer(nextConfig));
