import { redirect } from 'next/navigation';

export default function ProximityPage() {
  // Redirect to proximity feed
  redirect('/local-pulse');
}
