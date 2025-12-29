import { Metadata } from 'next'
import { VouchClient } from './vouch-client'

interface Props {
  params: Promise<{ code: string }>
}

export async function generateMetadata(props: Props): Promise<Metadata> {
  const params = await props.params
  const code = params.code

  // Try to fetch user details for viral metadata
  try {
    const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api'
    const res = await fetch(`${apiUrl}/auth/referral/${code}`, { next: { revalidate: 60 } })

    if (res.ok) {
        const data = await res.json()
        if (data.valid) {
            return {
                title: `Do you trust ${data.referrer_name}? - FWBer`,
                description: `Vouch for ${data.referrer_name} on FWBer. Is this person Safe, Fun, or Hot? Verify their reputation now.`,
                openGraph: {
                    title: `Do you trust ${data.referrer_name}?`,
                    description: `Vouch for ${data.referrer_name} on FWBer. Verify their reputation now.`,
                    images: data.referrer_avatar ? [data.referrer_avatar] : [],
                },
                twitter: {
                    card: 'summary_large_image',
                    title: `Do you trust ${data.referrer_name}?`,
                    description: `Vouch for ${data.referrer_name} on FWBer.`,
                    images: data.referrer_avatar ? [data.referrer_avatar] : [],
                }
            }
        }
    }
  } catch (e) {
    console.error('Metadata fetch error', e)
  }

  return {
    title: 'Vouch for a Friend - FWBer',
    description: 'Help your friends build their reputation on FWBer.',
  }
}

export default async function VouchPage(props: Props) {
  const params = await props.params;
  return <VouchClient code={params.code} />
}
