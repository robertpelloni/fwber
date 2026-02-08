'use client'

import Link from 'next/link'
import { notFound, useParams } from 'next/navigation'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ArrowLeft, ThumbsUp, ThumbsDown } from 'lucide-react'

// This would typically come from a CMS or markdown files
// For MVP, we'll store content in a const map
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
          <li>Click the "Sign Up" button on the homepage.</li>
          <li>Enter your email address and create a strong password.</li>
          <li>Alternatively, you can use "Sign in with Solana" if you have a crypto wallet.</li>
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
          <li><strong>Avatars:</strong> Use our AI tools to generate a privacy-preserving avatar.</li>
        </ul>
      </div>
    )
  },
  'avatars': {
    category: 'Getting Started',
    title: 'Understanding Avatars',
    content: (
      <div className="space-y-4">
        <p>fwber prioritizes privacy by encouraging the use of AI-generated avatars instead of real photos for initial interactions.</p>
        <h3 className="text-lg font-semibold">How it works</h3>
        <p>You upload a reference photo, and our AI generates a stylized avatar that maintains your likeness without revealing your exact identity. This allows you to browse and match with confidence.</p>
      </div>
    )
  },

  // Wallet & Crypto
  'fwb-tokens': {
    category: 'Wallet & Crypto',
    title: 'How FWB Tokens work',
    content: (
      <div className="space-y-4">
        <p>FWB Tokens are the internal currency of the platform. They are used for various premium features.</p>
        <h3 className="text-lg font-semibold">Uses for Tokens</h3>
        <ul className="list-disc pl-5 space-y-2">
          <li>Unlocking private photos</li>
          <li>Joining paid groups</li>
          <li>Sending virtual gifts</li>
          <li>Boosting your profile visibility</li>
        </ul>
        <p>You can earn tokens by referring friends, logging in daily, or completing achievements.</p>
      </div>
    )
  },
  'deposits': {
    category: 'Wallet & Crypto',
    title: 'Depositing from Solana',
    content: (
      <div className="space-y-4">
        <p>You can top up your FWB balance by depositing tokens from the Solana blockchain.</p>
        <h3 className="text-lg font-semibold">How to Deposit</h3>
        <ol className="list-decimal pl-5 space-y-2">
          <li>Go to your <strong>Wallet</strong> page.</li>
          <li>Click "Deposit".</li>
          <li>Copy your unique deposit address or scan the QR code.</li>
          <li>Send SOL or SPL tokens from your external wallet (Phantom, Solflare).</li>
          <li>The tokens will appear in your internal balance after a few confirmations.</li>
        </ol>
      </div>
    )
  },
  'withdrawals': {
    category: 'Wallet & Crypto',
    title: 'Withdrawing funds',
    content: (
      <div className="space-y-4">
        <p>You can withdraw your accumulated FWB tokens back to your Solana wallet.</p>
        <h3 className="text-lg font-semibold">Withdrawal Process</h3>
        <ol className="list-decimal pl-5 space-y-2">
          <li>Go to your <strong>Wallet</strong> page.</li>
          <li>Select "Withdraw".</li>
          <li>Enter the amount and your destination wallet address.</li>
          <li>Confirm the transaction. Withdrawals are processed typically within 24 hours.</li>
        </ol>
      </div>
    )
  },
  'ledger-vs-chain': {
    category: 'Wallet & Crypto',
    title: 'Internal Ledger vs On-Chain',
    content: (
      <div className="space-y-4">
        <p>It is important to understand the difference between your Internal Ledger balance and On-Chain assets.</p>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="p-4 border rounded-lg">
            <h4 className="font-bold">Internal Ledger</h4>
            <ul className="list-disc pl-5 text-sm mt-2">
              <li>Instant transactions</li>
              <li>Zero gas fees</li>
              <li>Used for all app interactions (tips, unlocks)</li>
            </ul>
          </div>
          <div className="p-4 border rounded-lg">
            <h4 className="font-bold">On-Chain (Solana)</h4>
            <ul className="list-disc pl-5 text-sm mt-2">
              <li>Self-custody in your wallet</li>
              <li>Requires gas fees (SOL)</li>
              <li>Used for deposits and withdrawals</li>
            </ul>
          </div>
        </div>
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
        <p>Photos can be revealed in two ways:</p>
        <ul className="list-disc pl-5 space-y-2">
          <li><strong>Mutual Match:</strong> Once you match with someone, basic photos may be revealed.</li>
          <li><strong>Token Unlock:</strong> You can pay a small token fee to unlock private galleries if the user allows it.</li>
        </ul>
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
        <p>We use "fuzzy location" logic to show approximate distance (e.g., "Less than 1 mile away") without pinpointing your coordinates.</p>
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
  'chatrooms-guide': {
    category: 'Platform Features',
    title: 'Proximity Chatrooms',
    content: (
      <div className="space-y-4">
        <p>Connect with people in your immediate vicinity through Proximity Chatrooms.</p>
        <h3 className="text-lg font-semibold">How it works</h3>
        <p>These are geo-fenced chat groups (e.g., "Downtown Bar Scene", "Central Park"). You must be physically located within the radius to join and chat.</p>
      </div>
    )
  },
  'groups-guide': {
    category: 'Platform Features',
    title: 'Creating & Joining Groups',
    content: (
      <div className="space-y-4">
        <p>Groups allow communities to form around shared interests.</p>
        <ul className="list-disc pl-5 space-y-2">
          <li><strong>Public Groups:</strong> Open to everyone.</li>
          <li><strong>Private Groups:</strong> Invite-only.</li>
          <li><strong>Paid Groups:</strong> Require a token entry fee to join, ensuring high-quality exclusive communities.</li>
        </ul>
      </div>
    )
  },
  'achievements-guide': {
    category: 'Platform Features',
    title: 'Achievements & Rewards',
    content: (
      <div className="space-y-4">
        <p>Earn badges and token rewards by being active on the platform.</p>
        <p>Visit the <strong>Achievements</strong> page to track your progress on goals like "Viral Star", "Social Butterfly", and more.</p>
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
