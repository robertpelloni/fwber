'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import { Users, Lock, Globe, Send, Trash2, MessageCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useGroup, useGroupPosts, useCreateGroupPost, useJoinGroup, useLeaveGroup, useDeleteGroupPost, useMyGroups } from '@/lib/hooks/use-groups';

export default function GroupDetailPage() {
  const params = useParams();
  const router = useRouter();
  const groupId = parseInt(params.id as string);
  
  const { data: group, isLoading: isLoadingGroup } = useGroup(groupId);
  const { data: posts, isLoading: isLoadingPosts } = useGroupPosts(groupId);
  const { data: myGroupsData } = useMyGroups();
  
  const createPost = useCreateGroupPost(groupId);
  const deletePost = useDeleteGroupPost();
  const joinGroup = useJoinGroup();
  const leaveGroup = useLeaveGroup();
  
  const [postContent, setPostContent] = React.useState('');

  const isMember = myGroupsData?.data.some(g => g.id === groupId);

  const handleJoin = () => joinGroup.mutate(groupId);
  const handleLeave = () => leaveGroup.mutate(groupId);

  const handlePostSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!postContent.trim()) return;
    
    createPost.mutate({ content: postContent }, {
      onSuccess: () => setPostContent('')
    });
  };

  const handleDeletePost = (postId: number) => {
    if (confirm('Are you sure you want to delete this post?')) {
      deletePost.mutate(postId);
    }
  };

  if (isLoadingGroup) return <div className="p-8 text-center">Loading group...</div>;
  if (!group) return <div className="p-8 text-center">Group not found</div>;

  return (
    <div className="container mx-auto py-8 px-4">
      {/* Header */}
      <div className="bg-card rounded-lg shadow-sm border p-6 mb-8">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div className="flex items-center gap-4">
            <div className="text-4xl bg-muted p-4 rounded-full">
              {group.icon || '👥'}
            </div>
            <div>
              <h1 className="text-3xl font-bold flex items-center gap-2">
                {group.name}
                {group.privacy === 'private' ? (
                  <Badge variant="secondary"><Lock className="w-3 h-3 mr-1" /> Private</Badge>
                ) : (
                  <Badge variant="outline"><Globe className="w-3 h-3 mr-1" /> Public</Badge>
                )}
              </h1>
              <p className="text-muted-foreground mt-1">{group.description}</p>
              <div className="flex items-center gap-4 mt-2 text-sm text-muted-foreground">
                <span className="flex items-center">
                  <Users className="w-4 h-4 mr-1" />
                  {group.member_count} members
                </span>
                <span>Created by {group.created_by_user_id}</span>
              </div>
            </div>
          </div>
          
          <div className="flex gap-2">
            {isMember && group.chatroom_id && (
              <Button 
                variant="secondary"
                onClick={() => router.push(`/chatrooms/${group.chatroom_id}`)}
              >
                <MessageCircle className="w-4 h-4 mr-2" />
                Chat
              </Button>
            )}
            <Button 
              variant={isMember ? "outline" : "default"}
              onClick={isMember ? handleLeave : handleJoin}
              disabled={joinGroup.isPending || leaveGroup.isPending}
            >
              {isMember ? 'Leave Group' : 'Join Group'}
            </Button>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Main Feed */}
        <div className="lg:col-span-2 space-y-6">
          {isMember ? (
            <Card>
              <CardContent className="pt-6">
                <form onSubmit={handlePostSubmit} className="flex gap-4">
                  <Input 
                    placeholder="Share something with the group..." 
                    value={postContent}
                    onChange={(e) => setPostContent(e.target.value)}
                    className="flex-1"
                  />
                  <Button type="submit" disabled={createPost.isPending || !postContent.trim()}>
                    <Send className="w-4 h-4" />
                  </Button>
                </form>
              </CardContent>
            </Card>
          ) : (
            <Card className="bg-muted/50">
              <CardContent className="py-8 text-center text-muted-foreground">
                Join this group to view and create posts.
              </CardContent>
            </Card>
          )}

          {isLoadingPosts ? (
            <div className="text-center py-8">Loading posts...</div>
          ) : posts?.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">No posts yet. Be the first to share!</div>
          ) : (
            posts?.map(post => (
              <Card key={post.id}>
                <CardHeader className="pb-2">
                  <div className="flex justify-between items-start">
                    <div className="flex items-center gap-2">
                      <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                        {post.user?.name?.[0] || 'U'}
                      </div>
                      <div>
                        <div className="font-semibold">{post.user?.name || 'Unknown User'}</div>
                        <div className="text-xs text-muted-foreground">
                          {new Date(post.created_at).toLocaleDateString()}
                        </div>
                      </div>
                    </div>
                    {/* Allow deletion if it's my post (logic simplified here, ideally check user ID) */}
                    <Button variant="ghost" size="icon" onClick={() => handleDeletePost(post.id)}>
                      <Trash2 className="w-4 h-4 text-muted-foreground hover:text-destructive" />
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  <p className="whitespace-pre-wrap">{post.content}</p>
                </CardContent>
              </Card>
            ))
          )}
        </div>

        {/* Sidebar - Members */}
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Members</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {group.members?.slice(0, 5).map(member => (
                  <div key={member.id} className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-full bg-muted flex items-center justify-center">
                      {member.user?.name?.[0] || 'U'}
                    </div>
                    <div>
                      <div className="font-medium text-sm">{member.user?.name || 'User'}</div>
                      <div className="text-xs text-muted-foreground capitalize">{member.role}</div>
                    </div>
                  </div>
                ))}
                {group.members && group.members.length > 5 && (
                  <Button variant="link" className="w-full text-sm">
                    View all {group.member_count} members
                  </Button>
                )}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
