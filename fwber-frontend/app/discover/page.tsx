import { redirect } from 'next/navigation';

export default function DiscoverPage() {
  // Redirect to recommendations page which seems to be the main discovery feed
  redirect('/recommendations');
}
