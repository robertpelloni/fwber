'use client';

import { useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useGroup, useGroupMatches, useRequestGroupMatch } from '@/lib/hooks/use-groups';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Users, Lock, Globe, ArrowLeft, Loader2, MapPin, CheckCircle, Clock } from 'lucide-react';
import Link from 'next/link';
import { toast } from 'sonner';

// Helper to determine why a match occurred (client-side approximation)
function getMatchReason(group: any, match: any) {
  if (group.category && match.category && group.category === match.category) {
    return `Matches category: ${group.category}`;
  }
  const matchingTags = group.tags?.filter((tag: string) => match.tags?.includes(tag));
  if (matchingTags?.length > 0) {
    return `Matches tags: ${matchingTags.join(', ')}`;
  }
  return 'Nearby group';
}

function ConnectButton({ groupId, targetGroupId }: { groupId: number, targetGroupId: number }) {
  const { mutate: requestMatch, isPending, isSuccess } = useRequestGroupMatch(groupId);
  const [hasRequested, setHasRequested] = useState(false);

  // If we just succeeded or if we have local state saying so
  if (isSuccess || hasRequested) {
    return (
      <Button className="flex-1" variant="outline" disabled>
        <Clock className="w-4 h-4 mr-2" /> Pending
      </Button>
    );
  }

  return (
    <Button 
      className="flex-1" 
      disabled={isPending}
      onClick={() => {
        requestMatch(targetGroupId, {
          onSuccess: () => {
            toast.success('Match request sent!');
            setHasRequested(true);
          },
          onError: (error: any) => {
            toast.error(error.response?.data?.error || 'Failed to send match request');
          }
        });
      }}
    >
      {isPending && <Loader2 className="w-4 h-4 mr-2 animate-spin" />}
      Connect
    </Button>
  );
}

export default function GroupMatchesPage() {
  const params = useParams();
  const router = useRouter();
  const groupId = parseInt(params.id as string);
  
  const { data: group, isLoading: isLoadingGroup } = useGroup(groupId);
  const { data: matches, isLoading: isLoadingMatches } = useGroupMatches(groupId);

  if (isLoadingGroup) {
    return (
      <div className="flex h-96 items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!group) {
    return <div className="p-8 text-center">Group not found</div>;
  }

  return (
    <div className="container mx-auto py-8 px-4">
      <div className="mb-6">
        <Button variant="ghost" onClick={() => router.back()} className="mb-4 pl-0 hover:pl-0">
          <ArrowLeft className="w-4 h-4 mr-2" /> Back to Group
        </Button>
        
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 className="text-3xl font-bold flex items-center gap-2">
              Matches for {group.name}
            </h1>
            <p className="text-muted-foreground mt-1">
              Groups that match your category, tags, and location.
            </p>
          </div>
        </div>
      </div>

      {!group.matching_enabled && (
        <Card className="mb-8 border-yellow-200 bg-yellow-50 dark:bg-yellow-900/10 dark:border-yellow-900/50">
          <CardContent className="pt-6">
            <div className="flex items-center gap-2 text-yellow-800 dark:text-yellow-200">
              <span className="font-semibold">Matching is disabled for this group.</span>
              <span>Enable matching in group settings to be discoverable by other groups.</span>
            </div>
          </CardContent>
        </Card>
      )}

      {isLoadingMatches ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {[1, 2, 3].map((i) => (
                <Card key={i} className="animate-pulse h-64 bg-muted/50" />
            ))}
        </div>
      ) : matches && matches.length > 0 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {matches.map((match) => {
            const matchReason = getMatchReason(group, match);
            return (
              <Card key={match.id} className="overflow-hidden hover:shadow-md transition-shadow">
                <CardHeader className="pb-4">
                  <div className="flex justify-between items-start">
                    <div className="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center text-2xl">
                      {match.icon || '👥'}
                    </div>
                    {match.privacy === 'private' ? (
                      <Badge variant="secondary"><Lock className="w-3 h-3 mr-1" /> Private</Badge>
                    ) : (
                      <Badge variant="outline"><Globe className="w-3 h-3 mr-1" /> Public</Badge>
                    )}
                  </div>
                  <CardTitle className="mt-4 text-xl">
                      <Link href={`/groups/${match.id}`} className="hover:underline decoration-primary">
                          {match.name}
                      </Link>
                  </CardTitle>
                  <div className="mt-2 text-xs font-medium text-primary bg-primary/10 px-2 py-1 rounded inline-block">
                    {matchReason}
                  </div>
                </CardHeader>
                <CardContent>
                  <p className="text-sm text-muted-foreground line-clamp-2 mb-4 h-10">
                    {match.description || 'No description provided.'}
                  </p>
                  
                  <div className="flex flex-wrap gap-2 mb-4 h-14 content-start">
                      {match.category && (
                          <Badge variant="outline" className="capitalize">{match.category}</Badge>
                      )}
                      {match.tags && match.tags.slice(0, 3).map(tag => (
                          <Badge key={tag} variant="secondary" className="text-xs">#{tag}</Badge>
                      ))}
                      {match.tags && match.tags.length > 3 && (
                          <Badge variant="secondary" className="text-xs">+{match.tags.length - 3}</Badge>
                      )}
                  </div>

                  <div className="flex items-center justify-between text-sm text-muted-foreground pt-4 border-t">
                    <span className="flex items-center">
                      <Users className="w-4 h-4 mr-1" />
                      {match.member_count} members
                    </span>
                  </div>
                  
                  <div className="flex gap-2 mt-4">
                    <Button variant="outline" className="flex-1" asChild>
                        <Link href={`/groups/${match.id}`}>View Group</Link>
                    </Button>
                    <ConnectButton groupId={groupId} targetGroupId={match.id} />
                  </div>
                </CardContent>
              </Card>
            );
          })}
        </div>
      ) : (
        <Card className="bg-muted/50 border-dashed">
          <CardContent className="py-12 text-center">
            <div className="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
              <Users className="w-8 h-8 text-muted-foreground" />
            </div>
            <h3 className="text-lg font-semibold mb-2">No Matches Found</h3>
            <p className="text-muted-foreground max-w-md mx-auto">
              We couldn&apos;t find any groups matching your criteria nearby.
              Try updating your group&apos;s category or tags to find more relevant communities.
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
