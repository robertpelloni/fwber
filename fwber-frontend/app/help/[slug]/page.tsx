'use client'

import Link from 'next/link'
import { notFound, useParams } from 'next/navigation'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ArrowLeft, ThumbsUp, ThumbsDown } from 'lucide-react'

const HELP_ARTICLES: Record<string, { title: string, content: React.ReactNode, category: string }> = {
  // Getting Started
  'create-account': {
    category: 'Getting Started',
    title: 'Creating your account',
    content: (
      <div className="space-y-4">
        <p>Creating an account on fwber is simple and privacy-focused.</p>
        <h3 className="text-lg font-semibold">Steps to Register</h3>
        <ol className="list-decimal pl-5 space-y-2">
          <li>Click the &quot;Sign Up&quot; button on the homepage.</li>
          <li>Enter your email address and create a strong password.</li>
          <li>Verify your email address via the link sent to your inbox.</li>
        </ol>
        <div className="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
          <p className="text-sm text-yellow-800 dark:text-yellow-200">
            <strong>Note:</strong> You must be 18+ to create an account. We enforce strict age verification policies.
          </p>
        </div>
      </div>
    )
  },
  'profile-setup': {
    category: 'Getting Started',
    title: 'Setting up your profile',
    content: (
      <div className="space-y-4">
        <p>Your profile is your identity on fwber. Here is how to make it stand out.</p>
        <h3 className="text-lg font-semibold">Profile Essentials</h3>
        <ul className="list-disc pl-5 space-y-2">
          <li><strong>Bio:</strong> Write a short description of yourself and what you are looking for.</li>
          <li><strong>Interests:</strong> Select tags that match your hobbies and lifestyle.</li>
          <li><strong>Photos:</strong> Upload clear photos to improve your match rate.</li>
        </ul>
      </div>
    )
  },

  // Safety
  'photo-privacy': {
    category: 'Safety & Privacy',
    title: 'Photo Privacy & Blurring',
    content: (
      <div className="space-y-4">
        <p>By default, real photos on fwber are blurred to protect user privacy.</p>
        <h3 className="text-lg font-semibold">Unlocking Photos</h3>
        <p>Photos are revealed when there is a <strong>Mutual Match</strong>. Once you both like each other, your basic photos are revealed to each other.</p>
      </div>
    )
  },
  'block-report': {
    category: 'Safety & Privacy',
    title: 'Blocking & Reporting',
    content: (
      <div className="space-y-4">
        <p>We have zero tolerance for harassment or abusive behavior.</p>
        <p>You can block or report any user from their profile page or chat window. Our moderation team reviews reports 24/7.</p>
      </div>
    )
  },
  'location-privacy': {
    category: 'Safety & Privacy',
    title: 'Location Privacy',
    content: (
      <div className="space-y-4">
        <p>Your exact location is never shared with other users.</p>
        <p>We use &quot;fuzzy location&quot; logic to show approximate distance (e.g., &quot;Less than 1 mile away&quot;) without pinpointing your coordinates. Background location tracking is used solely to alert you of nearby matches.</p>
      </div>
    )
  },

  // Features
  'matching': {
    category: 'Platform Features',
    title: 'How Matching works',
    content: (
      <div className="space-y-4">
        <p>Swipe right to Like, left to Pass. It is the classic mechanism you know, but improved.</p>
        <p>If you both Like each other, it is a Match! You can then start chatting for free.</p>
      </div>
    )
  },
  'proximity-guide': {
    category: 'Platform Features',
    title: 'Finding people nearby',
    content: (
      <div className="space-y-4">
        <p>fwber is built for real-world connections. Use the <strong>Nearby</strong> tab to see users currently in your vicinity.</p>
        <p>Ensure location permissions are enabled on your mobile device to get the most out of proximity alerts.</p>
      </div>
    )
  },

  // Tiered Privacy System
  'privacy-tiers-guide': {
    category: 'Tiered Privacy System',
    title: 'The 5 Privacy Tiers',
    content: (
      <div className="space-y-4">
        <p>fwber is built on the philosophy that attention should be earned. We enforce this through our 5-Tier Face Reveal system.</p>
        <ul className="list-disc pl-5 space-y-2">
          <li><strong>Tier 1:</strong> Extreme Blur.</li>
          <li><strong>Tier 2:</strong> Moderate Blur.</li>
          <li><strong>Tier 3:</strong> Light Blur.</li>
          <li><strong>Tier 4:</strong> Clear.</li>
          <li><strong>Tier 5:</strong> The Vault (Encrypted).</li>
        </ul>
      </div>
    )
  },
  'managing-reveals': {
    category: 'Tiered Privacy System',
    title: 'Managing Face Reveals',
    content: (
      <div className="space-y-4">
        <p>Keep track of who has access to your photos in the <strong>Settings</strong> panel.</p>
        <p>Under the Privacy section, you can view a list of every user who currently has an active face reveal unlock and revoke access if necessary.</p>
      </div>
    )
  },

  // Security & Media Vault
  'on-device-encryption': {
    category: 'Security & Media Vault',
    title: 'On-Device Encryption',
    content: (
      <div className="space-y-4">
        <p>Your messages and vault media are protected with military-grade End-to-End Encryption (E2EE).</p>
        <p>Encryption happens on your device before data is sent to our servers. Only you and your intended recipient hold the keys to decrypt the content.</p>
      </div>
    )
  },
  'key-recovery': {
    category: 'Security & Media Vault',
    title: 'Recovering Master Keys',
    content: (
      <div className="space-y-4">
        <div className="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
          <p className="text-sm font-bold text-red-800 dark:text-red-200">
            IMPORTANT: Back up your keys in Security Settings.
          </p>
        </div>
        <p className="mt-4">Because we use E2EE, we cannot reset your encryption keys. You must use the <strong>Backup Keys</strong> feature in Settings to create an encrypted backup on our server, which you can restore on other devices using your passphrase.</p>
      </div>
    )
  }
}

export default function HelpArticlePage() {
  const params = useParams()
  const slug = params?.slug as string
  const article = HELP_ARTICLES[slug]

  if (!article) {
    notFound()
  }

  return (
    <div className="max-w-3xl mx-auto">
      <div className="mb-6">
        <Link
          href="/help"
          className="inline-flex items-center text-sm text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
        >
          <ArrowLeft className="w-4 h-4 mr-2" />
          Back to Help Center
        </Link>
      </div>

      <Card>
        <CardHeader>
          <div className="text-sm font-medium text-primary mb-2">
            {article.category}
          </div>
          <CardTitle className="text-3xl">{article.title}</CardTitle>
        </CardHeader>
        <CardContent className="prose dark:prose-invert max-w-none">
          {article.content}
        </CardContent>

        {/* Feedback Section */}
        <div className="border-t border-gray-100 dark:border-gray-800 mt-8 pt-6">
          <div className="flex flex-col items-center justify-center space-y-4">
            <p className="text-sm text-gray-500 font-medium">Was this article helpful?</p>
            <div className="flex gap-4">
              <Button variant="outline" size="sm" className="gap-2">
                <ThumbsUp className="w-4 h-4" />
                Yes
              </Button>
              <Button variant="outline" size="sm" className="gap-2">
                <ThumbsDown className="w-4 h-4" />
                No
              </Button>
            </div>
          </div>
        </div>
      </Card>
    </div>
  )
}
