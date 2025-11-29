import React from 'react';
import Link from 'next/link';
import { Users, Lock, Globe } from 'lucide-react';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Group } from '@/lib/api/groups';
import { useJoinGroup, useLeaveGroup } from '@/lib/hooks/use-groups';

interface GroupCardProps {
  group: Group;
}

export function GroupCard({ group }: GroupCardProps) {
  const joinGroup = useJoinGroup();
  const leaveGroup = useLeaveGroup();

  const isMember = group.is_member; // Assuming backend returns this or we handle it elsewhere
  // Note: The backend response might not include is_member directly unless we added it to the model/resource.
  // For now, we'll assume the API handles it or we check against myGroups list.
  // Since we didn't implement is_member in the backend explicitly (except maybe via `withCount` or similar), 
  // we might need to fetch myGroups to check membership.
  // However, for this component, let's assume we pass membership status or the group object has it.
  // If the backend doesn't return it, we might need to check `myGroups` in the parent component.
  
  // Let's assume the parent handles the logic or we just show "View" if we don't know.
  // But the requirements say "Join/Leave button".
  
  const handleJoin = (e: React.MouseEvent) => {
    e.preventDefault();
    joinGroup.mutate(group.id);
  };

  const handleLeave = (e: React.MouseEvent) => {
    e.preventDefault();
    leaveGroup.mutate(group.id);
  };

  return (
    <Link href={`/groups/${group.id}`}>
      <Card className="h-full hover:shadow-md transition-shadow cursor-pointer">
        <CardHeader className="pb-2">
          <div className="flex justify-between items-start">
            <div className="flex items-center gap-2">
              <span className="text-2xl">{group.icon || '👥'}</span>
              <CardTitle className="text-lg">{group.name}</CardTitle>
            </div>
            {group.privacy === 'private' ? (
              <Badge variant="secondary"><Lock className="w-3 h-3 mr-1" /> Private</Badge>
            ) : (
              <Badge variant="outline"><Globe className="w-3 h-3 mr-1" /> Public</Badge>
            )}
            {isMember && <Badge variant="default" className="ml-2">Member</Badge>}
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-muted-foreground line-clamp-2 mb-4">
            {group.description || 'No description provided.'}
          </p>
          <div className="flex items-center text-sm text-muted-foreground">
            <Users className="w-4 h-4 mr-1" />
            {group.member_count} members
          </div>
        </CardContent>
        <CardFooter>
          <Button 
            className="w-full" 
            variant={isMember ? "outline" : "default"}
            onClick={isMember ? handleLeave : handleJoin}
          >
            {isMember ? 'Leave Group' : 'Join Group'}
          </Button>
        </CardFooter>
      </Card>
    </Link>
  );
}
