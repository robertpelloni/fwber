'use client'

import React, { useEffect, useState } from 'react'
import axios from 'axios'
import Link from 'next/link'
import { Shield, Flame, PartyPopper, CheckCircle2, User, ArrowRight, Loader2 } from 'lucide-react'
import { useParams, useRouter } from 'next/navigation'

export default function VouchPage() {
  const params = useParams()
  const code = params.code as string
  const router = useRouter()

  const [referrer, setReferrer] = useState<{name: string, avatar: string | null} | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(false)
  const [vouched, setVouched] = useState<string | null>(null)

  useEffect(() => {
    if (code) {
      const checkCode = async () => {
        try {
          const res = await axios.get(`${process.env.NEXT_PUBLIC_API_URL}/auth/referral/${code}`)
          if (res.data.valid) {
             setReferrer({
               name: res.data.referrer_name,
               avatar: res.data.referrer_avatar
             })
          } else {
             setError(true)
          }
        } catch (e) {
          setError(true)
        } finally {
          setLoading(false)
        }
      }
      checkCode()
    }
  }, [code])

  const handleVouch = (type: string) => {
    setVouched(type)
    // In a full implementation, we would send this to the backend.
    // For now, we simulate the "Vouch" action as a hook to registration.
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <Loader2 className="w-8 h-8 text-purple-600 animate-spin" />
      </div>
    )
  }

  if (error || !referrer) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 p-4 text-center">
        <h1 className="text-2xl font-bold text-gray-900 mb-2">Link Expired or Invalid</h1>
        <p className="text-gray-600 mb-6">We couldn&apos;t find the user you&apos;re looking for.</p>
        <Link href="/" className="text-purple-600 font-bold hover:underline">Go Home</Link>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-600 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">

        {/* Header / Profile */}
        <div className="bg-gray-50 p-8 text-center border-b border-gray-100">
          <div className="w-24 h-24 mx-auto bg-purple-100 rounded-full flex items-center justify-center mb-4 relative">
            {referrer.avatar ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={referrer.avatar} alt={referrer.name} className="w-full h-full rounded-full object-cover" />
            ) : (
                <User className="w-12 h-12 text-purple-400" />
            )}
            <div className="absolute -bottom-2 -right-2 bg-green-500 text-white p-1.5 rounded-full border-4 border-white">
                <CheckCircle2 className="w-4 h-4" />
            </div>
          </div>
          <h1 className="text-2xl font-bold text-gray-900">Vouch for {referrer.name}</h1>
          <p className="text-gray-500 mt-2">Help them verify their reputation on FWBer.</p>
        </div>

        {/* Action Area */}
        <div className="p-8">
          {!vouched ? (
            <>
              <p className="text-center text-gray-700 font-medium mb-6">
                How do you know {referrer.name}?
              </p>

              <div className="space-y-3">
                <button
                  onClick={() => handleVouch('safe')}
                  className="w-full flex items-center p-4 rounded-xl border-2 border-gray-100 hover:border-green-500 hover:bg-green-50 transition-all group"
                >
                  <div className="p-2 bg-green-100 rounded-full mr-4 group-hover:bg-green-200">
                    <Shield className="w-6 h-6 text-green-600" />
                  </div>
                  <div className="text-left">
                    <span className="block font-bold text-gray-900">Trustworthy & Safe</span>
                    <span className="text-sm text-gray-500">I&apos;d trust them with my drink</span>
                  </div>
                </button>

                <button
                  onClick={() => handleVouch('fun')}
                  className="w-full flex items-center p-4 rounded-xl border-2 border-gray-100 hover:border-purple-500 hover:bg-purple-50 transition-all group"
                >
                  <div className="p-2 bg-purple-100 rounded-full mr-4 group-hover:bg-purple-200">
                    <PartyPopper className="w-6 h-6 text-purple-600" />
                  </div>
                  <div className="text-left">
                    <span className="block font-bold text-gray-900">Fun & Social</span>
                    <span className="text-sm text-gray-500">Life of the party</span>
                  </div>
                </button>

                <button
                  onClick={() => handleVouch('hot')}
                  className="w-full flex items-center p-4 rounded-xl border-2 border-gray-100 hover:border-orange-500 hover:bg-orange-50 transition-all group"
                >
                  <div className="p-2 bg-orange-100 rounded-full mr-4 group-hover:bg-orange-200">
                    <Flame className="w-6 h-6 text-orange-600" />
                  </div>
                  <div className="text-left">
                    <span className="block font-bold text-gray-900">Hot & Sexy</span>
                    <span className="text-sm text-gray-500">Total catch</span>
                  </div>
                </button>
              </div>
            </>
          ) : (
            <div className="text-center animate-in fade-in zoom-in duration-300">
               <div className="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                 <CheckCircle2 className="w-8 h-8" />
               </div>
               <h2 className="text-xl font-bold text-gray-900 mb-2">Vouch Recorded!</h2>
               <p className="text-gray-600 mb-6">
                 Thanks for verifying {referrer.name}. You&apos;ve unlocked a reward for yourself.
               </p>

               <div className="bg-gradient-to-r from-yellow-50 to-orange-50 border border-orange-100 rounded-xl p-4 mb-6">
                 <p className="font-bold text-orange-800">50 Free Tokens</p>
                 <p className="text-sm text-orange-600">Valid for 24 hours</p>
               </div>

               <Link
                 href={`/register?ref=${code}`}
                 className="block w-full py-4 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 transition-colors shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
               >
                 Claim Reward & Join
               </Link>

               <p className="mt-4 text-xs text-gray-400">
                 Join {referrer.name} and thousands of others on FWBer.
               </p>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
