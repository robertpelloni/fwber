'use client';

import React from 'react';
import Link from 'next/link';
import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { GroupCard } from '@/components/GroupCard';
import { useGroups, useMyGroups } from '@/lib/hooks/use-groups';

export default function GroupsPage() {
  const { data: groupsData, isLoading: isLoadingGroups } = useGroups();
  const { data: myGroupsData, isLoading: isLoadingMyGroups } = useMyGroups();
  const [search, setSearch] = React.useState('');

  // Handle potential API response wrapping (e.g. { data: [...] })
  const groups = Array.isArray(groupsData) ? groupsData : (groupsData as any)?.data || [];
  const myGroups = Array.isArray(myGroupsData) ? myGroupsData : (myGroupsData as any)?.data || [];

  const filteredGroups = groups?.filter((group: any) => 
    group.name.toLowerCase().includes(search.toLowerCase()) ||
    group.description?.toLowerCase().includes(search.toLowerCase())
  );

  const myGroupIds = new Set(myGroups?.map(g => g.id));

  if (isLoadingGroups || isLoadingMyGroups) {
    return <div className="p-8 text-center">Loading groups...</div>;
  }

  return (
    <div className="container mx-auto py-8 px-4">
      <div className="flex justify-between items-center mb-8">
        <h1 className="text-3xl font-bold">Groups</h1>
        <Link href="/groups/create">
          <Button>
            <Plus className="w-4 h-4 mr-2" />
            Create Group
          </Button>
        </Link>
      </div>

      <div className="mb-8">
        <Input 
          placeholder="Search groups..." 
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="max-w-md"
        />
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filteredGroups?.map(group => (
          <GroupCard 
            key={group.id} 
            group={{
              ...group,
              is_member: myGroupIds.has(group.id)
            }} 
          />
        ))}
      </div>

      {filteredGroups?.length === 0 && (
        <div className="text-center py-12 text-muted-foreground">
          No groups found matching your search.
        </div>
      )}
    </div>
  );
}
