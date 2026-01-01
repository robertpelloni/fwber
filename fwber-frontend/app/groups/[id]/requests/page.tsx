'use client';

import { useParams, useRouter } from 'next/navigation';
import { useGroup, useGroupMatchRequests, useAcceptMatchRequest, useRejectMatchRequest } from '@/lib/hooks/use-groups';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Users, Lock, Globe, ArrowLeft, Loader2, Check, X, Clock } from 'lucide-react';
import Link from 'next/link';
import { toast } from 'sonner';
import React from 'react';

export default function GroupRequestsPage() {
  const params = useParams();
  const router = useRouter();
  const groupId = parseInt(params.id as string);
  
  const { data: group, isLoading: isLoadingGroup } = useGroup(groupId);
  const { data: requests, isLoading: isLoadingRequests, refetch } = useGroupMatchRequests(groupId);
  
  const { mutate: acceptRequest, isPending: isAccepting } = useAcceptMatchRequest(groupId);
  const { mutate: rejectRequest, isPending: isRejecting } = useRejectMatchRequest(groupId);

  const handleAccept = (matchId: number) => {
    acceptRequest(matchId, {
      onSuccess: () => {
        toast.success('Match accepted!');
        refetch();
      },
      onError: (err: any) => toast.error(err.response?.data?.error || 'Failed to accept')
    });
  };

  const handleReject = (matchId: number) => {
    rejectRequest(matchId, {
      onSuccess: () => {
        toast.success('Match rejected');
        refetch();
      },
      onError: (err: any) => toast.error(err.response?.data?.error || 'Failed to reject')
    });
  };

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
        
        <h1 className="text-3xl font-bold flex items-center gap-2">
          Match Requests
        </h1>
        <p className="text-muted-foreground mt-1">
          Manage incoming and outgoing connection requests for {group.name}.
        </p>
      </div>

      <Tabs defaultValue="incoming" className="w-full">
        <TabsList className="mb-4">
          <TabsTrigger value="incoming">Incoming Requests</TabsTrigger>
          <TabsTrigger value="outgoing">Outgoing Requests</TabsTrigger>
        </TabsList>
        
        <TabsContent value="incoming">
          {isLoadingRequests ? (
             <div className="space-y-4">
                {[1, 2].map(i => <Card key={i} className="animate-pulse h-32 bg-muted/50" />)}
             </div>
          ) : requests?.incoming && requests.incoming.length > 0 ? (
            <div className="space-y-4">
              {requests.incoming.map((req: any) => (
                <Card key={req.id}>
                  <CardContent className="flex flex-col md:flex-row items-start md:items-center justify-between p-6 gap-4">
                    <div className="flex items-center gap-4">
                      <div className="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center text-2xl">
                         {req.other_group.icon || '👥'}
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold flex items-center gap-2">
                          {req.other_group.name}
                          {req.other_group.privacy === 'private' ? 
                            <Badge variant="secondary" className="text-xs"><Lock className="w-3 h-3 mr-1" /> Private</Badge> : 
                            <Badge variant="outline" className="text-xs"><Globe className="w-3 h-3 mr-1" /> Public</Badge>
                          }
                        </h3>
                        <p className="text-sm text-muted-foreground line-clamp-1">
                          {req.other_group.description || 'No description'}
                        </p>
                        <div className="flex gap-2 mt-1">
                           <Badge variant="secondary" className="text-[10px] capitalize">{req.other_group.category}</Badge>
                           <span className="text-xs text-muted-foreground flex items-center">
                              <Users className="w-3 h-3 mr-1" /> {req.other_group.member_count} members
                           </span>
                        </div>
                      </div>
                    </div>
                    
                    <div className="flex gap-2 w-full md:w-auto">
                        <Button 
                            variant="default" 
                            className="flex-1 md:flex-none bg-green-600 hover:bg-green-700"
                            onClick={() => handleAccept(req.id)}
                            disabled={isAccepting}
                        >
                            {isAccepting ? <Loader2 className="w-4 h-4 animate-spin" /> : <Check className="w-4 h-4 mr-2" />}
                            Accept
                        </Button>
                        <Button 
                            variant="outline" 
                            className="flex-1 md:flex-none text-red-600 hover:text-red-700 hover:bg-red-50"
                            onClick={() => handleReject(req.id)}
                            disabled={isRejecting}
                        >
                            {isRejecting ? <Loader2 className="w-4 h-4 animate-spin" /> : <X className="w-4 h-4 mr-2" />}
                            Reject
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link href={`/groups/${req.other_group.id}`}>View Group</Link>
                        </Button>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          ) : (
            <div className="text-center py-12 border-2 border-dashed rounded-lg text-muted-foreground">
                No incoming match requests.
            </div>
          )}
        </TabsContent>

        <TabsContent value="outgoing">
        {isLoadingRequests ? (
             <div className="space-y-4">
                {[1, 2].map(i => <Card key={i} className="animate-pulse h-32 bg-muted/50" />)}
             </div>
          ) : requests?.outgoing && requests.outgoing.length > 0 ? (
            <div className="space-y-4">
              {requests.outgoing.map((req: any) => (
                <Card key={req.id}>
                  <CardContent className="flex flex-col md:flex-row items-center justify-between p-6 gap-4">
                    <div className="flex items-center gap-4">
                      <div className="w-12 h-12 rounded-lg bg-muted flex items-center justify-center text-2xl grayscale">
                         {req.other_group.icon || '👥'}
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold">{req.other_group.name}</h3>
                        <p className="text-sm text-muted-foreground">Status: Pending approval</p>
                      </div>
                    </div>
                    <Button variant="outline" disabled>
                        <Clock className="w-4 h-4 mr-2" /> Pending
                    </Button>
                  </CardContent>
                </Card>
              ))}
            </div>
          ) : (
            <div className="text-center py-12 border-2 border-dashed rounded-lg text-muted-foreground">
                No outgoing requests found. Go to <Link href={`/groups/${groupId}/matches`} className="text-primary hover:underline">Matches</Link> to find groups!
            </div>
          )}
        </TabsContent>
      </Tabs>
    </div>
  );
}
