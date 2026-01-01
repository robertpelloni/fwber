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
  const [selectedCategory, setSelectedCategory] = React.useState<string>('');

  const groups = groupsData?.data || [];
  const myGroups = myGroupsData?.data || [];

  const filteredGroups = groups.filter((group) => {
    const matchesSearch = group.name.toLowerCase().includes(search.toLowerCase()) ||
    (group.description && group.description.toLowerCase().includes(search.toLowerCase())) ||
    (group.tags && group.tags.some(tag => tag.toLowerCase().includes(search.toLowerCase())));

    const matchesCategory = selectedCategory ? group.category === selectedCategory : true;

    return matchesSearch && matchesCategory;
  });

  const myGroupIds = new Set(myGroups.map(g => g.id));
  
  const categories = Array.from(new Set(groups.map(g => g.category).filter(Boolean)));

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

      <div className="mb-8 flex flex-col sm:flex-row gap-4">
        <Input 
          placeholder="Search groups by name, description, or tags..." 
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="max-w-md flex-1"
        />
        <select
            className="flex h-10 w-full sm:w-[200px] items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            value={selectedCategory}
            onChange={(e) => setSelectedCategory(e.target.value)}
        >
            <option value="">All Categories</option>
            {categories.map((category) => (
                <option key={category as string} value={category as string} className="capitalize">{category}</option>
            ))}
        </select>
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
