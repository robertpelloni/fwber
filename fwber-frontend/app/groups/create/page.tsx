'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { useCreateGroup } from '@/lib/hooks/use-groups';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const formSchema = z.object({
  name: z.string().min(3, 'Name must be at least 3 characters'),
  description: z.string().optional(),
  icon: z.string().optional(),
  privacy: z.enum(['public', 'private']),
  token_entry_fee: z.string().optional().transform(val => val ? parseFloat(val) : 0),
});

export default function CreateGroupPage() {
  const router = useRouter();
  const createGroup = useCreateGroup();

  const { register, handleSubmit, formState: { errors } } = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: '',
      description: '',
      icon: '👥',
      privacy: 'public',
    },
  });

  function onSubmit(values: z.infer<typeof formSchema>) {
    createGroup.mutate(values, {
      onSuccess: (group) => {
        router.push(`/groups/${group.id}`);
      },
    });
  }

  return (
    <div className="container mx-auto py-8 px-4 max-w-2xl">
      <Card>
        <CardHeader>
          <CardTitle>Create New Group</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            <div className="space-y-2">
              <Label htmlFor="name">Group Name</Label>
              <Input id="name" placeholder="Hiking Enthusiasts" {...register('name')} />
              {errors.name && <p className="text-sm text-red-500">{errors.name.message}</p>}
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea id="description" placeholder="A group for people who love hiking..." {...register('description')} />
              {errors.description && <p className="text-sm text-red-500">{errors.description.message}</p>}
            </div>

            <div className="space-y-2">
              <Label htmlFor="icon">Icon (Emoji)</Label>
              <Input id="icon" placeholder="⛰️" {...register('icon')} />
              <p className="text-xs text-muted-foreground">Enter an emoji to represent your group.</p>
              {errors.icon && <p className="text-sm text-red-500">{errors.icon.message}</p>}
            </div>

            <div className="space-y-2">
              <Label htmlFor="privacy">Privacy</Label>
              <select 
                id="privacy" 
                className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                {...register('privacy')}
              >
                <option value="public">Public - Anyone can join</option>
                <option value="private">Private - Invite only</option>
              </select>
              {errors.privacy && <p className="text-sm text-red-500">{errors.privacy.message}</p>}
            </div>

            <div className="space-y-2">
              <Label htmlFor="token_entry_fee">Entry Fee (Tokens)</Label>
              <Input
                id="token_entry_fee"
                type="number"
                step="0.1"
                placeholder="0"
                {...register('token_entry_fee')}
              />
              <p className="text-xs text-muted-foreground">Optional: Charge tokens to join this group.</p>
              {errors.token_entry_fee && <p className="text-sm text-red-500">{errors.token_entry_fee.message}</p>}
            </div>

            <div className="flex justify-end gap-4">
              <Button type="button" variant="outline" onClick={() => router.back()}>
                Cancel
              </Button>
              <Button type="submit" disabled={createGroup.isPending}>
                {createGroup.isPending ? 'Creating...' : 'Create Group'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
